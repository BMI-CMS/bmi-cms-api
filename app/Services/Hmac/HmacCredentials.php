<?php

namespace App\Services\Hmac;

class HmacCredentials
{
    /**
     * Create a new HMAC credentials instance.
     */
    public function __construct(
        public readonly string $clientId,
        public readonly ?string $keyId,
        public readonly string $secret,
    ) {}
}
