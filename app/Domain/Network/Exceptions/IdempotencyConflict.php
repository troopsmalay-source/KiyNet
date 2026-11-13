<?php

namespace App\Domain\Network\Exceptions;

use RuntimeException;

class IdempotencyConflict extends RuntimeException
{
    public function __construct(string $message = 'Idempotency key sudah dipakai untuk payload berbeda.')
    {
        parent::__construct($message);
    }
}
