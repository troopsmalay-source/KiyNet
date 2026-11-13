<?php

namespace App\Domain\Network\Dto;

/**
 * Command payload for provisioning/suspension/profile changes.
 *
 * Never carries PPPoE password or router secret material. Credential enrollment
 * is a separate protected flow that only exchanges a credential reference.
 */
final class NetworkAccessCommand
{
    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $customerId,
        public readonly ?int $partnerId,
        public readonly string $username,
        public readonly ?int $nasDeviceId,
        public readonly ?string $profileCode,
        public readonly int $policyVersion,
        public readonly string $desiredState,
        public readonly string $correlationId,
        public readonly string $idempotencyKey,
        public readonly ?string $reason = null,
        public readonly ?string $credentialReference = null,
        public readonly ?string $ipPool = null,
    ) {
    }

    public static function fromAssignment(array $payload, string $desiredState, string $correlationId, string $idempotencyKey): self
    {
        return new self(
            subscriptionId: (int) $payload['subscription_id'],
            customerId: (int) $payload['customer_id'],
            partnerId: isset($payload['partner_id']) ? (int) $payload['partner_id'] : null,
            username: (string) $payload['pppoe_username'],
            nasDeviceId: isset($payload['nas_device_id']) ? (int) $payload['nas_device_id'] : null,
            profileCode: $payload['profile_code'] ?? null,
            policyVersion: (int) ($payload['policy_version'] ?? 1),
            desiredState: $desiredState,
            correlationId: $correlationId,
            idempotencyKey: $idempotencyKey,
            reason: $payload['reason'] ?? null,
            credentialReference: $payload['credential_reference'] ?? null,
            ipPool: $payload['ip_pool'] ?? null,
        );
    }

    /**
     * Safe, secret-free payload persisted in the outbox.
     */
    public function toPayload(): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'customer_id' => $this->customerId,
            'partner_id' => $this->partnerId,
            'pppoe_username' => $this->username,
            'nas_device_id' => $this->nasDeviceId,
            'profile_code' => $this->profileCode,
            'policy_version' => $this->policyVersion,
            'desired_state' => $this->desiredState,
            'reason' => $this->reason,
            'credential_reference' => $this->credentialReference,
            'ip_pool' => $this->ipPool,
        ];
    }
}
