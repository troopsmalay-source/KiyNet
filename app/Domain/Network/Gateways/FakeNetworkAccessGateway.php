<?php

namespace App\Domain\Network\Gateways;

use App\Domain\Network\Contracts\NetworkAccessGateway;
use App\Domain\Network\Dto\NetworkAccessCommand;
use App\Domain\Network\Dto\NetworkAccessQuery;
use App\Domain\Network\Dto\NetworkAccessStatus;
use App\Domain\Network\Dto\NetworkCommandResult;
use App\Domain\Network\Dto\NetworkDisconnectCommand;
use App\Domain\Network\Models\NetworkAccessAssignment;

/**
 * Default adapter for local/dev/CI: records intents in memory and performs no
 * RADIUS or RouterOS side effect.
 */
class FakeNetworkAccessGateway implements NetworkAccessGateway
{
    /** @var array<int, array{type: string, command: array<string, mixed>}> */
    private static array $applied = [];

    public function provision(NetworkAccessCommand $command): NetworkCommandResult
    {
        return $this->record('provision', $command->toPayload(), $command->desiredState);
    }

    public function suspend(NetworkAccessCommand $command): NetworkCommandResult
    {
        return $this->record('suspend', $command->toPayload(), 'suspended');
    }

    public function restore(NetworkAccessCommand $command): NetworkCommandResult
    {
        return $this->record('restore', $command->toPayload(), 'active');
    }

    public function changeProfile(NetworkAccessCommand $command): NetworkCommandResult
    {
        return $this->record('change_profile', $command->toPayload(), $command->desiredState);
    }

    public function disconnect(NetworkDisconnectCommand $command): NetworkCommandResult
    {
        return $this->record('disconnect', $command->toPayload(), null);
    }

    public function getStatus(NetworkAccessQuery $query): NetworkAccessStatus
    {
        $assignment = NetworkAccessAssignment::query()
            ->where('subscription_id', $query->subscriptionId)
            ->first();

        return new NetworkAccessStatus(
            desiredState: $assignment?->desired_state ?? 'unknown',
            appliedState: $assignment?->applied_state,
            policyVersion: (int) ($assignment?->policy_version ?? 0),
            lastSyncAt: $assignment?->last_sync_at,
            lastError: $assignment?->last_error,
        );
    }

    private function record(string $type, array $payload, ?string $appliedState): NetworkCommandResult
    {
        self::$applied[] = ['type' => $type, 'command' => $payload];

        return NetworkCommandResult::applied($appliedState);
    }

    /**
     * @return array<int, array{type: string, command: array<string, mixed>}>
     */
    public static function applied(): array
    {
        return self::$applied;
    }

    public static function reset(): void
    {
        self::$applied = [];
    }
}
