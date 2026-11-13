<?php

namespace App\Domain\Network\Dto;

final class SessionQuery
{
    public function __construct(
        public readonly TrustedScope $scope,
        public readonly bool $activeOnly = true,
        public readonly ?int $customerId = null,
        public readonly ?int $partnerId = null,
        public readonly int $page = 1,
        public readonly int $perPage = 25,
    ) {
    }
}
