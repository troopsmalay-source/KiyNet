<?php

namespace App\Domain\Network\Dto;

final class NetworkDisconnectCommand
{
    public function __construct(
        public readonly int $sessionId,
        public readonly string $username,
        public readonly ?int $nasDeviceId,
        public readonly string $reasonCode,
        public readonly ?string $reasonNote,
        public readonly string $correlationId,
        public readonly string $idempotencyKey,
    ) {
    }

    public function toPayload(): array
    {
        return [
            'session_id' => $this->sessionId,
            'pppoe_username' => $this->username,
            'nas_device_id' => $this->nasDeviceId,
            'reason_code' => $this->reasonCode,
            'reason_note' => $this->reasonNote,
        ];
    }
}
