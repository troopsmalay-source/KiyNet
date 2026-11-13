<?php

namespace App\Domain\Network\Services;

use App\Domain\Network\Dto\NetworkAccessCommand;
use App\Domain\Network\Dto\NetworkDisconnectCommand;
use App\Domain\Network\Exceptions\IdempotencyConflict;
use App\Domain\Network\Models\RadiusSyncOutbox;
use Carbon\CarbonImmutable;

/**
 * Writes network commands next to the business transition in the same
 * transaction. Callers must invoke this inside their DB transaction.
 */
final class NetworkOutboxDispatcher
{
    public function enqueueAccessCommand(
        NetworkAccessCommand $command,
        string $commandType = 'provision',
    ): RadiusSyncOutbox {
        return $this->enqueue(
            aggregateType: 'subscription',
            aggregateId: $command->subscriptionId,
            partnerId: $command->partnerId,
            commandType: $commandType,
            payload: $command->toPayload(),
            policyVersion: $command->policyVersion,
            idempotencyKey: $command->idempotencyKey,
            correlationId: $command->correlationId,
        );
    }

    public function enqueueDisconnect(NetworkDisconnectCommand $command): RadiusSyncOutbox
    {
        return $this->enqueue(
            aggregateType: 'session',
            aggregateId: $command->sessionId,
            partnerId: null,
            commandType: 'disconnect',
            payload: $command->toPayload(),
            policyVersion: 0,
            idempotencyKey: $command->idempotencyKey,
            correlationId: $command->correlationId,
        );
    }

    private function enqueue(
        string $aggregateType,
        int $aggregateId,
        ?int $partnerId,
        string $commandType,
        array $payload,
        int $policyVersion,
        string $idempotencyKey,
        ?string $correlationId,
    ): RadiusSyncOutbox {
        $payloadHash = hash('sha256', $commandType.'|'.json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        $existing = RadiusSyncOutbox::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing !== null) {
            if (! hash_equals((string) $existing->payload_hash, $payloadHash)) {
                throw new IdempotencyConflict();
            }

            return $existing;
        }

        return RadiusSyncOutbox::query()->create([
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'partner_id' => $partnerId,
            'command_type' => $commandType,
            'payload_json' => $payload,
            'policy_version' => $policyVersion,
            'idempotency_key' => $idempotencyKey,
            'payload_hash' => $payloadHash,
            'correlation_id' => $correlationId,
            'status' => RadiusSyncOutbox::PENDING,
            'attempts' => 0,
            'available_at' => CarbonImmutable::now('UTC'),
        ]);
    }
}
