<?php

namespace App\Domain\Network\Dto;

use Carbon\CarbonInterface;

final class CustomerUsageQuery
{
    public function __construct(
        public readonly TrustedScope $scope,
        public readonly int $customerId,
        public readonly CarbonInterface $from,
        public readonly CarbonInterface $to,
    ) {
    }
}
