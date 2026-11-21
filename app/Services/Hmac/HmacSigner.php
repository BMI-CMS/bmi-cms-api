<?php

namespace App\Services\Hmac;

use Illuminate\Support\Str;

class HmacSigner
{
    /**
     * Sign a canonical request and return the lowercase hex HMAC-SHA256.
     */
    public function sign(HmacCanonicalRequest $request, string $secret): string
    {
        return hash_hmac('sha256', $request->toString(), $secret);
    }

    /**
     * Build the signing headers for an outgoing request with a fresh nonce and request time.
     *
     * @return array<string, string>
     */
    public function headers(string $method, string $url, string $body, HmacCredentials $credentials): array
    {
        $requestTime = (string) now()->getTimestamp();
        $nonce = (string) Str::uuid();

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $signature = $this->sign(
            new HmacCanonicalRequest(
                method: $method,
                path: (string) parse_url($url, PHP_URL_PATH),
                query: $query,
                clientId: $credentials->clientId,
                keyId: $credentials->keyId,
                requestTime: $requestTime,
                nonce: $nonce,
                body: $body,
            ),
            $credentials->secret,
        );

        $headers = [
            'X-Client-Id' => $credentials->clientId,
            'X-Request-Time' => $requestTime,
            'X-Nonce' => $nonce,
            'X-Signature' => $signature,
        ];

        if ($credentials->keyId !== null) {
            $headers['X-Key-Id'] = $credentials->keyId;
        }

        return $headers;
    }
}
