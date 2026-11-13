<?php

namespace App\Domain\Network\Dto;

final class NetworkCommandResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?int $commandId = null,
        public readonly ?string $appliedState = null,
        public readonly ?string $externalReference = null,
        public readonly ?string $message = null,
    ) {
    }

    public static function applied(?string $appliedState = null, ?string $externalReference = null): self
    {
        return new self('applied', null, $appliedState, $externalReference);
    }

    public static function queued(int $commandId, ?string $desiredState = null): self
    {
        return new self('queued', $commandId, $desiredState);
    }

    public function succeeded(): bool
    {
        return $this->status === 'applied';
    }
}
