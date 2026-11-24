<?php

namespace App\Services\Hmac;

use Illuminate\Http\Request;
use App\Exceptions\Auth\InvalidHmacSignatureException;

class HmacCanonicalRequest
{
    /**
     * Create a new canonical request instance.
     *
     * @param  array<int|string, mixed>  $query
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly string $clientId,
        public readonly ?string $keyId,
        public readonly string $requestTime,
        public readonly string $nonce,
        public readonly string $body,
    ) {}

    /**
     * Create a canonical request from the signing headers and raw body of an incoming request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            method: $request->getMethod(),
            path: $request->getPathInfo(),
            query: self::parseQuery((string) $request->server->get('QUERY_STRING', '')),
            clientId: (string) $request->header('X-Client-Id'),
            keyId: $request->header('X-Key-Id'),
            requestTime: (string) $request->header('X-Request-Time'),
            nonce: (string) $request->header('X-Nonce'),
            body: $request->getContent(),
        );
    }

    /**
     * Parse a raw query string that is within the length, parameter and nesting limits.
     *
     * @return array<int|string, mixed>
     */
    private static function parseQuery(string $queryString): array
    {
        self::assertQueryWithinLimits($queryString);

        parse_str($queryString, $query);

        return $query;
    }

    /**
     * Refuse a raw query string that is too long, has too many parameters or has a key nested too deep.
     */
    private static function assertQueryWithinLimits(string $queryString): void
    {
        $maxLength = config()->integer('hmac.query.max_length');
        $maxParameters = config()->integer('hmac.query.max_parameters');
        $maxNesting = config()->integer('hmac.query.max_nesting');

        if (strlen($queryString) > $maxLength) {
            throw InvalidHmacSignatureException::queryTooLong($maxLength);
        }

        $pairs = array_filter(explode('&', $queryString), fn(string $pair): bool => $pair !== '');

        if (count($pairs) > $maxParameters) {
            throw InvalidHmacSignatureException::queryTooManyParameters($maxParameters);
        }

        foreach ($pairs as $pair) {
            $key = urldecode(explode('=', $pair, 2)[0]);

            if (substr_count($key, '[') > $maxNesting) {
                throw InvalidHmacSignatureException::queryTooDeep($maxNesting);
            }
        }
    }

    /**
     * Build the eight-line string that is signed.
     */
    public function toString(): string
    {
        return implode("\n", [
            strtoupper($this->method),
            $this->path,
            $this->canonicalQuery(),
            $this->clientId,
            $this->keyId ?? '',
            $this->requestTime,
            $this->nonce,
            hash('sha256', $this->body),
        ]);
    }

    /**
     * Build the RFC 3986 query string with its keys sorted at every level.
     */
    private function canonicalQuery(): string
    {
        return http_build_query($this->sortByKey($this->query), '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Sort an array by key, recursing into nested arrays.
     *
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private function sortByKey(array $values): array
    {
        ksort($values, SORT_STRING);

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = $this->sortByKey($value);
            }
        }

        return $values;
    }
}
