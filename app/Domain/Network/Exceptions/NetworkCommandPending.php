<?php

namespace App\Domain\Network\Exceptions;

use RuntimeException;

/**
 * Raised when the network side is unreachable/disabled. The command stays
 * pending and must never be reported as applied.
 */
class NetworkCommandPending extends RuntimeException
{
}
