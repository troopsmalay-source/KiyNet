<?php

namespace App\Domain\Network\Services;

use App\Domain\Network\Dto\NetworkAccessCommand;
use App\Domain\Network\Models\NetworkAccessAssignment;
use App\Domain\Network\Models\RadiusSyncOutbox;
use App\Models\Langganan;
use App\Models\NetworkAccessProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

/**
 * Bridges the Langganan lifecycle (activate / suspend / restore / change-package)
 * to radius_sync_outbox, inside the caller's DB transaction.
 *
 * Rules:
 *  - the assignment row (network_access_assignments) is the network projection;
 *    business code writes desired state only through this service;
 *  - policy_version is bumped on every transition so superseded commands skip;
 *  - no password/secret enters the outbox payload.
 */
final class SubscriptionNetworkSync
{
    public function __construct(
        private readonly NetworkOutboxDispatcher $dispatcher,
    ) {
    }

    public function activate(Langganan $langganan, string $correlationId = null): ?RadiusSyncOutbox
    {
        return $this->transition($langganan, 'provision', 'active', $correlationId);
    }

    public function suspend(Langganan $langganan, ?string $reason = null, string $correlationId = null): ?RadiusSyncOutbox
    {
        return $this->transition($langganan, 'suspend', 'suspended', $correlationId, $reason);
    }

    public function restore(Langganan $langganan, string $correlationId = null): ?RadiusSyncOutbox
    {
        return $this->transition($langganan, 'restore', 'active', $correlationId);
    }

    public function changePackage(Langganan $langganan, string $correlationId = null): ?RadiusSyncOutbox
    {
        return $this->transition($langganan, 'change_profile', 'active', $correlationId);
    }

    private function transition(
        Langganan $langganan,
        string $commandType,
        string $desiredState,
        ?string $correlationId = null,
        ?string $reason = null,
    ): ?RadiusSyncOutbox {
        if (! in_array($commandType, ['provision', 'suspend', 'restore', 'change_profile'], true)) {
            throw new InvalidArgumentException("Unknown subscription transition: {$commandType}");
        }

        if ($langganan->pppoe_username === null || $langganan->pppoe_username === '') {
            // No PPPoE identity: nothing to project, keep the network untouched.
            return null;
        }

        return DB::transaction(function () use ($langganan, $commandType, $desiredState, $correlationId, $reason) {
            $assignment = NetworkAccessAssignment::query()
                ->where('subscription_id', $langganan->id)
                ->lockForUpdate()
                ->first();

            if ($assignment === null) {
                // ponytail: assignment provisioning (NAS + profile pick) is a
                // separate operator flow; skip silently until it exists.
                return null;
            }

            if ($desiredState === 'terminated') {
                throw new LogicException('Termination is handled by the operator flow, not the lifecycle bridge.');
            }

            $assignment->desired_state = $desiredState;
            $assignment->policy_version = (int) $assignment->policy_version + 1;
            $assignment->save();

            $correlationId ??= (string) Str::uuid();

            $command = new NetworkAccessCommand(
                subscriptionId: (int) $langganan->id,
                customerId: (int) $langganan->pelanggan_id,
                partnerId: $assignment->partner_id !== null ? (int) $assignment->partner_id : null,
                username: (string) $langganan->pppoe_username,
                nasDeviceId: $assignment->nas_device_id !== null ? (int) $assignment->nas_device_id : null,
                profileCode: $assignment->profile?->code,
                policyVersion: (int) $assignment->policy_version,
                desiredState: $desiredState,
                correlationId: $correlationId,
                idempotencyKey: 'sub:'.$langganan->id.':'.$assignment->policy_version.':'.$commandType,
                reason: $reason,
                ipPool: $assignment->ip_pool,
            );

            return $this->dispatcher->enqueueAccessCommand($command, $commandType);
        });
    }
}
