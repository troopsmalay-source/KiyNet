<?php

namespace App\Domain\Network\Dto;

use Carbon\CarbonInterface;

final class NetworkAccessStatus
{
    public function __construct(
        public readonly string $desiredState,
        public readonly ?string $appliedState,
        public readonly int $policyVersion,
        public readonly ?CarbonInterface $lastSyncAt = null,
        public readonly ?string $lastError = null,
    ) {
    }

    public function isInSync(): bool
    {
        return $this->appliedState === $this->desiredState;
    }
}
