<?php

namespace App\Domain\Network\Dto;

final class NetworkAccessQuery
{
    public function __construct(
        public readonly int $subscriptionId,
        public readonly ?string $username = null,
    ) {
    }
}
