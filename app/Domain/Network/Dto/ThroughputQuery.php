<?php

namespace App\Domain\Network\Dto;

use Carbon\CarbonInterface;

final class ThroughputQuery
{
    public function __construct(
        public readonly TrustedScope $scope,
        public readonly int $partnerId,
        public readonly CarbonInterface $from,
        public readonly CarbonInterface $to,
        public readonly int $resolutionSeconds = 300,
    ) {
    }
}
