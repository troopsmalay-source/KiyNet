<?php

namespace App\Domain\Network\RouterOs;

use App\Domain\Network\Exceptions\NetworkCommandPending;

/**
 * RouterOS API is disabled until lab evidence exists for the target
 * RouterOS version. Commands fail as transient/pending instead of pretending
 * the session was disconnected.
 */
class UnavailableRouterOsClient implements RouterOsClient
{
    public function disconnectSession(string $username): void
    {
        throw new NetworkCommandPending(
            'RouterOS API belum diaktifkan/diverifikasi di lab; perintah disconnect tetap pending.',
        );
    }

    public function isEnabled(): bool
    {
        return false;
    }
}
