<?php

namespace App\Services\Hmac;

use RuntimeException;
use Illuminate\Support\Str;

class HmacKeyring
{
    /**
     * Resolve the client id configured for an inbound client.
     */
    public function clientId(string $client): ?string
    {
        $clientId = config("hmac.inbound.{$client}.client_id");

        return is_string($clientId) && $clientId !== '' ? $clientId : null;
    }

    /**
     * Resolve the secret of a client's key id, or its default secret when no key id is given.
     */
    public function secret(string $client, ?string $keyId): ?string
    {
        $keys = $this->keys($client);

        if ($keyId === null) {
            return $keys === [] ? null : reset($keys);
        }

        return $keys[$keyId] ?? null;
    }

    /**
     * Resolve the configured secrets of a client by key id, default first.
     *
     * @return array<string, string>
     */
    private function keys(string $client): array
    {
        $rawKeys = config("hmac.inbound.{$client}.keys");

        if (!is_string($rawKeys)) {
            return [];
        }

        $keys = [];

        foreach (Str::of($rawKeys)->explode(',') as $pair) {
            $pair = trim($pair);

            if ($pair === '') {
                continue;
            }

            [$keyId, $secret] = array_pad(explode(':', $pair, 2), 2, '');

            if ($keyId === '' || $secret === '') {
                throw new RuntimeException("A {$client} HMAC key is not in the key_id:secret format.");
            }

            $keys[$keyId] = $secret;
        }

        return $keys;
    }
}
