<?php

namespace App\Domain\Accounting\Dto;

final class IngestResult
{
    public function __construct(
        public readonly int $sessionId,
        public readonly int $sampleId,
        public readonly bool $duplicate,
        public readonly bool $deltaValid,
        public readonly bool $outOfOrder,
        public readonly bool $counterReset,
    ) {
    }

    public function accepted(): bool
    {
        return ! $this->duplicate;
    }
}
