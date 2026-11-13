<?php

namespace App\Domain\Network\Dto;

use Carbon\CarbonInterface;

final class PartnerUsageQuery
{
    public function __construct(
        public readonly TrustedScope $scope,
        public readonly int $partnerId,
        public readonly CarbonInterface $from,
        public readonly CarbonInterface $to,
    ) {
    }
}
