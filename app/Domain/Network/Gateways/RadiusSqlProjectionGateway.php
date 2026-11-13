<?php

namespace App\Domain\Network\Gateways;

use App\Domain\Network\Contracts\NetworkAccessGateway;
use App\Domain\Network\Dto\NetworkAccessCommand;
use App\Domain\Network\Dto\NetworkAccessQuery;
use App\Domain\Network\Dto\NetworkAccessStatus;
use App\Domain\Network\Dto\NetworkCommandResult;
use App\Domain\Network\Dto\NetworkDisconnectCommand;
use App\Domain\Network\Exceptions\NetworkProjectionUnavailable;
use App\Domain\Network\Models\NetworkAccessAssignment;
use App\Domain\Network\Models\NetworkAccessProfile;
use App\Domain\Network\RouterOs\RouterOsClient;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Writes the versioned access policy into the FreeRADIUS SQL projection.
 *
 * Requires a dedicated RADIUS connection name (separate role/schema). Never
 * writes credential material: PPPoE secrets are enrolled through a separate
 * protected flow, this adapter only manages authorization policy and replies.
 *
 * All attribute names/values must be verified against the pinned FreeRADIUS
 * major version and the target RouterOS version in the lab.
 */
class RadiusSqlProjectionGateway implements NetworkAccessGateway
{
    public function __construct(
        private readonly RouterOsClient $routerOs,
    ) {
    }

    public function provision(NetworkAccessCommand $command): NetworkCommandResult
    {
        $this->applyReplyAttributes($command->username, $command->profileCode, $command->ipPool);
        $this->clearReject($command->username);

        return NetworkCommandResult::applied('active');
    }

    public function suspend(NetworkAccessCommand $command): NetworkCommandResult
    {
        $this->setReject($command->username);

        return NetworkCommandResult::applied('suspended');
    }

    public function restore(NetworkAccessCommand $command): NetworkCommandResult
    {
        $this->applyReplyAttributes($command->username, $command->profileCode, $command->ipPool);
        $this->clearReject($command->username);

        return NetworkCommandResult::applied('active');
    }

    public function changeProfile(NetworkAccessCommand $command): NetworkCommandResult
    {
        $this->applyReplyAttributes($command->username, $command->profileCode, $command->ipPool);

        return NetworkCommandResult::applied($command->desiredState);
    }

    public function disconnect(NetworkDisconnectCommand $command): NetworkCommandResult
    {
        // Throws NetworkCommandPending when the API is disabled/unreachable, so
        // the outbox never records a disconnect that did not happen.
        $this->routerOs->disconnectSession($command->username);

        return NetworkCommandResult::applied();
    }

    public function getStatus(NetworkAccessQuery $query): NetworkAccessStatus
    {
        $assignment = NetworkAccessAssignment::query()
            ->where('subscription_id', $query->subscriptionId)
            ->first();

        $suspended = $query->username !== null && $this->isRejected($query->username);

        return new NetworkAccessStatus(
            desiredState: $assignment?->desired_state ?? 'unknown',
            appliedState: $suspended ? 'suspended' : $assignment?->applied_state,
            policyVersion: (int) ($assignment?->policy_version ?? 0),
            lastSyncAt: $assignment?->last_sync_at,
            lastError: $assignment?->last_error,
        );
    }

    private function applyReplyAttributes(string $username, ?string $profileCode, ?string $ipPool): void
    {
        if ($profileCode !== null) {
            $profile = NetworkAccessProfile::query()->where('code', $profileCode)->first();

            if ($profile === null) {
                throw new NetworkProjectionUnavailable("Profil jaringan '{$profileCode}' tidak ditemukan.");
            }

            $this->upsertReply($username, 'Mikrotik-Rate-Limit', $profile->rateLimit());
        }

        if (filled($ipPool)) {
            $this->upsertReply($username, 'Framed-Pool', (string) $ipPool);
        }
    }

    private function upsertReply(string $username, string $attribute, string $value): void
    {
        $table = $this->table('reply');

        $this->connection()->table($table)->updateOrInsert(
            ['username' => $username, 'attribute' => $attribute],
            ['op' => ':=', 'value' => $value],
        );
    }

    private function setReject(string $username): void
    {
        $this->connection()->table($this->table('check'))->updateOrInsert(
            ['username' => $username, 'attribute' => 'Auth-Type'],
            ['op' => ':=', 'value' => 'Reject'],
        );
    }

    private function clearReject(string $username): void
    {
        $this->connection()->table($this->table('check'))
            ->where('username', $username)
            ->where('attribute', 'Auth-Type')
            ->where('value', 'Reject')
            ->delete();
    }

    private function isRejected(string $username): bool
    {
        return $this->connection()->table($this->table('check'))
            ->where('username', $username)
            ->where('attribute', 'Auth-Type')
            ->where('value', 'Reject')
            ->exists();
    }

    private function table(string $key): string
    {
        return (string) config("network.radius.tables.{$key}", $key);
    }

    private function connection(): ConnectionInterface
    {
        if (config('network.driver') !== 'radius') {
            throw new NetworkProjectionUnavailable('NETWORK_DRIVER bukan radius; proyeksi RADIUS ditolak.');
        }

        $name = config('network.radius.connection');

        if (blank($name)) {
            throw new NetworkProjectionUnavailable('RADIUS_DB_CONNECTION belum dikonfigurasi.');
        }

        try {
            return DB::connection($name);
        } catch (Throwable $exception) {
            throw new NetworkProjectionUnavailable('Koneksi database RADIUS tidak tersedia.', 0, $exception);
        }
    }
}
