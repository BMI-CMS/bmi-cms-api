<?php

namespace App\Exceptions\Lucas;

use Exception;

class LucasRejectedException extends Exception
{
    /**
     * Create a new LUCAS rejected exception instance.
     */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message);
    }
}
