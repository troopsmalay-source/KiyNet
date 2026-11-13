<?php

namespace App\Domain\Network\Services;

use App\Domain\Network\Contracts\NetworkAccessGateway;
use App\Domain\Network\Dto\NetworkAccessCommand;
use App\Domain\Network\Dto\NetworkCommandResult;
use App\Domain\Network\Dto\NetworkDisconnectCommand;
use App\Domain\Network\Gateways\NetworkGatewayFactory;
use App\Domain\Network\Models\NetworkAccessAssignment;
use App\Domain\Network\Models\NetworkPolicyAudit;
use App\Domain\Network\Models\RadiusSyncOutbox;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Consumes radius_sync_outbox commands:
 *  - claims a command with a lock and increments attempts;
 *  - ignores commands superseded by a newer policy version;
 *  - applies through NetworkAccessGateway;
 *  - records success, retry backoff, or dead-letter — never a false success.
 */
final class NetworkOutboxProcessor
{
    private readonly NetworkAccessGateway $gateway;

    public function __construct(?NetworkAccessGateway $gateway = null)
    {
        $this->gateway = $gateway ?? NetworkGatewayFactory::make();
    }

    public function processPending(int $limit = 50): int
    {
        $commands = RadiusSyncOutbox::query()
            ->whereIn('status', [RadiusSyncOutbox::PENDING, RadiusSyncOutbox::FAILED])
            ->where(function ($query) {
                $query->whereNull('available_at')->orWhere('available_at', '<=', CarbonImmutable::now('UTC'));
            })
            ->orderBy('available_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $processed = 0;

        foreach ($commands as $command) {
            $this->process($command);
            $processed++;
        }

        return $processed;
    }

    public function process(RadiusSyncOutbox $command): RadiusSyncOutbox
    {
        $claimed = $this->claim($command);

        if ($claimed === null) {
            return $command->refresh();
        }

        if ($this->isSuperseded($claimed)) {
            $claimed->status = RadiusSyncOutbox::SUPERSEDED;
            $claimed->locked_at = null;
            $claimed->processed_at = CarbonImmutable::now('UTC');
            $claimed->save();

            $this->audit($claimed, 'command.superseded', 'superseded', 'policy_version_stale');

            return $claimed;
        }

        try {
            $result = $this->apply($claimed);
            $this->markSucceeded($claimed, $result);
        } catch (Throwable $exception) {
            $this->markFailed($claimed, $exception);
        }

        return $claimed->refresh();
    }

    public function reclaimStaleLocks(): int
    {
        $cutoff = CarbonImmutable::now('UTC')
            ->subMinutes((int) config('network.outbox.stale_lock_minutes', 10));

        return RadiusSyncOutbox::query()
            ->where('status', RadiusSyncOutbox::PROCESSING)
            ->where('locked_at', '<', $cutoff)
            ->update([
                'status' => RadiusSyncOutbox::PENDING,
                'locked_at' => null,
                'available_at' => CarbonImmutable::now('UTC'),
            ]);
    }

    private function claim(RadiusSyncOutbox $command): ?RadiusSyncOutbox
    {
        return DB::transaction(function () use ($command) {
            /** @var RadiusSyncOutbox|null $locked */
            $locked = RadiusSyncOutbox::query()->whereKey($command->getKey())->lockForUpdate()->first();

            if ($locked === null || ! $locked->isPending()) {
                return null;
            }

            if ($locked->available_at !== null && $locked->available_at->isFuture()) {
                return null;
            }

            $locked->status = RadiusSyncOutbox::PROCESSING;
            $locked->locked_at = CarbonImmutable::now('UTC');
            $locked->attempts = (int) $locked->attempts + 1;
            $locked->save();

            return $locked;
        });
    }

    private function isSuperseded(RadiusSyncOutbox $command): bool
    {
        if ($command->aggregate_type !== 'subscription' || $command->policy_version === 0) {
            return false;
        }

        $newerCommand = RadiusSyncOutbox::query()
            ->where('aggregate_type', $command->aggregate_type)
            ->where('aggregate_id', $command->aggregate_id)
            ->where('policy_version', '>', $command->policy_version)
            ->whereIn('status', [RadiusSyncOutbox::PENDING, RadiusSyncOutbox::PROCESSING, RadiusSyncOutbox::SUCCEEDED])
            ->exists();

        if ($newerCommand) {
            return true;
        }

        $assignment = NetworkAccessAssignment::query()
            ->where('subscription_id', $command->aggregate_id)
            ->first();

        return $assignment !== null && (int) $assignment->policy_version > (int) $command->policy_version;
    }

    private function apply(RadiusSyncOutbox $command): NetworkCommandResult
    {
        $payload = (array) $command->payload_json;

        return match ($command->command_type) {
            'provision' => $this->gateway->provision($this->accessCommand($command, $payload, 'active')),
            'suspend' => $this->gateway->suspend($this->accessCommand($command, $payload, 'suspended')),
            'restore' => $this->gateway->restore($this->accessCommand($command, $payload, 'active')),
            'change_profile' => $this->gateway->changeProfile($this->accessCommand($command, $payload, 'active')),
            'disconnect' => $this->gateway->disconnect(new NetworkDisconnectCommand(
                sessionId: (int) ($payload['session_id'] ?? $command->aggregate_id),
                username: (string) ($payload['pppoe_username'] ?? ''),
                nasDeviceId: isset($payload['nas_device_id']) ? (int) $payload['nas_device_id'] : null,
                reasonCode: (string) ($payload['reason_code'] ?? 'OPERATOR_REQUEST'),
                reasonNote: $payload['reason_note'] ?? null,
                correlationId: (string) ($command->correlation_id ?? ''),
                idempotencyKey: (string) $command->idempotency_key,
            )),
            default => throw new InvalidArgumentException("Unknown network command: {$command->command_type}"),
        };
    }

    private function accessCommand(RadiusSyncOutbox $command, array $payload, string $desiredState): NetworkAccessCommand
    {
        return NetworkAccessCommand::fromAssignment(
            payload: $payload,
            desiredState: $payload['desired_state'] ?? $desiredState,
            correlationId: (string) ($command->correlation_id ?? ''),
            idempotencyKey: (string) $command->idempotency_key,
        );
    }

    private function markSucceeded(RadiusSyncOutbox $command, NetworkCommandResult $result): void
    {
        DB::transaction(function () use ($command, $result) {
            $command->status = RadiusSyncOutbox::SUCCEEDED;
            $command->processed_at = CarbonImmutable::now('UTC');
            $command->locked_at = null;
            $command->last_error = null;
            $command->save();

            if ($command->aggregate_type === 'subscription') {
                NetworkAccessAssignment::query()
                    ->where('subscription_id', $command->aggregate_id)
                    ->update([
                        'applied_state' => $result->appliedState,
                        'last_sync_at' => CarbonImmutable::now('UTC'),
                        'last_error' => null,
                        'updated_at' => CarbonImmutable::now('UTC'),
                    ]);
            }

            $this->audit($command, 'command.'.$command->command_type, 'applied', null, $result->appliedState);
        });
    }

    private function markFailed(RadiusSyncOutbox $command, Throwable $exception): void
    {
        DB::transaction(function () use ($command, $exception) {
            $maxAttempts = max(1, (int) config('network.outbox.max_attempts', 8));
            $deadLetter = (int) $command->attempts >= $maxAttempts;

            $command->status = $deadLetter ? RadiusSyncOutbox::DEAD_LETTER : RadiusSyncOutbox::FAILED;
            $command->locked_at = null;
            $command->last_error = Str::limit($exception->getMessage(), 1000);
            $command->available_at = $deadLetter
                ? null
                : CarbonImmutable::now('UTC')->addSeconds($this->backoffSeconds((int) $command->attempts));
            $command->save();

            if ($command->aggregate_type === 'subscription') {
                NetworkAccessAssignment::query()
                    ->where('subscription_id', $command->aggregate_id)
                    ->update([
                        'last_error' => Str::limit($exception->getMessage(), 1000),
                        'updated_at' => CarbonImmutable::now('UTC'),
                    ]);
            }

            $this->audit($command, 'command.'.$command->command_type, $deadLetter ? 'dead_letter' : 'failed', $command->last_error);
        });
    }

    private function backoffSeconds(int $attempts): int
    {
        $base = max(1, (int) config('network.outbox.retry_base_seconds', 30));
        $max = max($base, (int) config('network.outbox.retry_max_seconds', 3600));

        return (int) min($max, $base * (2 ** max(0, $attempts - 1)));
    }

    private function audit(RadiusSyncOutbox $command, string $action, string $result, ?string $reason = null, ?string $newState = null): void
    {
        NetworkPolicyAudit::query()->create([
            'actor_type' => 'system',
            'entity_type' => $command->aggregate_type,
            'entity_id' => $command->aggregate_id,
            'action' => $action,
            'old_policy_version' => null,
            'new_policy_version' => $command->policy_version,
            'new_profile_code' => $newState,
            'result' => $result,
            'reason' => $reason,
            'correlation_id' => $command->correlation_id,
        ]);
    }
}
