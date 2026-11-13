<?php

namespace App\Domain\Network\RouterOs;

interface RouterOsClient
{
    /**
     * Forcibly terminate an active PPPoE session on the NAS.
     *
     * Implementations must only be enabled for the exact lab/production
     * RouterOS version that has been verified for this operation.
     */
    public function disconnectSession(string $username): void;

    public function isEnabled(): bool;
}
