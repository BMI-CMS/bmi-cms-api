<?php

namespace App\Services;

use App\Models\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class LucasIntegrationService
{
    private string $clientId;
    private string $keyId;
    private string $secret;
    private string $url;

    public function __construct()
    {
        $this->clientId = config('hmac.outbound.lucas.client_id');
        $this->keyId = config('hmac.outbound.lucas.key');
        $this->secret = config('hmac.outbound.lucas.secret');
        $this->url = config('hmac.outbound.lucas.url');
    }

    public function outbound(string $endpoint, array $data)
    {
        $url = "$this->url/$endpoint";

        $requestTime = (string) now()->timestamp;
        $nonce = (string) Str::uuid();

        $body = json_encode($data, JSON_THROW_ON_ERROR);

        $bodyHash = hash('sha256', $body);


        $path = parse_url($url, PHP_URL_PATH);

        $canonicalQuery = '';

        $stringToSign = implode("\n", [
            'POST',
            $path,
            $canonicalQuery,
            $this->clientId,
            $this->keyId ?? '',
            $requestTime,
            $nonce,
            $bodyHash,
        ]);

        $signature = hash_hmac('sha256', $stringToSign, $this->secret);

        $headers = [
            'X-Client-Id' => $this->clientId,
            'X-Request-Time' => $requestTime,
            'X-Nonce' => $nonce,
            'X-Signature' => $signature,
            'Accept' => 'application/json',
        ];

        if ($this->keyId !== null && $this->keyId !== '') {
            $headers['X-Key-Id'] = $this->keyId;
        }

        $response = Http::withHeaders($headers)
            ->withBody($body, 'application/json')
            ->post($url);

        return $response;
    }

    public function inbound()
    {
        return '';
    }
}
