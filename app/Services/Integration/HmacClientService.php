<?php

namespace App\Services\Integration;

use App\Enums\Integration\IntegrationSystem;
use App\Exceptions\Integration\PermanentIntegrationException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HmacClientService
{
    public function send(
        IntegrationSystem $destinationSystem,
        string $method,
        string $endpoint,
        array $payload,
        string $idempotencyKey
    ): Response {

        error_log('test111111111111111111111');
        $system = strtolower($destinationSystem->value);
        error_log('test222222222222222222222222222222');
        error_log($system);
        error_log('test3333333333333');
        $configuration = config("outbound.{$system}");
        error_log($configuration["url"]);
        error_log('test44444444444444444444444');
        if (
            !$configuration ||
            empty($configuration['url']) ||
            empty($configuration['client_id']) ||
            empty($configuration['secret'])
        ) {
            throw new PermanentIntegrationException(
                'Invalid outbound integration configuration.'
            );
        }

        $method = strtoupper($method);

        if (!in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
            throw new PermanentIntegrationException(
                'Unsupported outbound HTTP method.'
            );
        }

        $url = rtrim($configuration['url'], '/') . $endpoint;

        $path = parse_url($url, PHP_URL_PATH);

        if (parse_url($url, PHP_URL_QUERY) !== null) {
            throw new PermanentIntegrationException(
                'Query parameters are not supported by this outbox client.'
            );
        }

        $clientId = $configuration['client_id'];
        $keyId = $configuration['key_id'] ?? '';
        $secret = $configuration['secret'];

        $requestTime = (string) now()->timestamp;
        $nonce = (string) Str::uuid();

        $body = json_encode(
            $payload,
            JSON_THROW_ON_ERROR
        );

        $bodyHash = hash('sha256', $body);

        $canonicalQuery = '';

        $stringToSign = implode("\n", [
            $method,
            $path,
            $canonicalQuery,
            $clientId,
            $keyId,
            $requestTime,
            $nonce,
            $bodyHash,
            $idempotencyKey,
        ]);

        $signature = hash_hmac(
            'sha256',
            $stringToSign,
            $secret
        );

        $headers = [
            'X-Client-Id' => $clientId,
            'X-Request-Time' => $requestTime,
            'X-Nonce' => $nonce,
            'X-Signature' => $signature,
            'Idempotency-Key' => $idempotencyKey,
            'Accept' => 'application/json',
        ];

        if ($keyId !== '') {
            $headers['X-Key-Id'] = $keyId;
        }

        return Http::withHeaders($headers)
            ->connectTimeout(
                config('integrations.http.connect_timeout', 5)
            )
            ->timeout(
                config('integrations.http.timeout', 15)
            )
            ->withoutRedirecting()
            ->withBody($body, 'application/json')
            ->send($method, $url);
    }
}
