<?php

namespace App\Services\Hmac;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\Auth\InvalidHmacSignatureException;

class HmacVerifier
{
    /**
     * Create a new HMAC verifier instance.
     */
    public function __construct(private readonly HmacKeyring $keyring, private readonly HmacSigner $signer) {}

    /**
     * Verify the HMAC signature headers of an incoming request from a client.
     */
    public function verify(Request $request, string $client): void
    {
        $clientId = $this->requiredHeader($request, 'X-Client-Id', '/^.+$/D');
        $keyId = $this->optionalHeader($request, 'X-Key-Id', '/^[A-Za-z0-9._-]{1,32}$/D');
        $requestTime = $this->requiredHeader($request, 'X-Request-Time', '/^[0-9]+$/D');
        $nonce = $this->requiredHeader($request, 'X-Nonce', '/^[A-Za-z0-9-]{16,64}$/D');
        $signature = $this->requiredHeader($request, 'X-Signature', '/^[0-9a-f]{64}$/D');

        $configuredClientId = $this->keyring->clientId($client);

        if ($configuredClientId === null || !hash_equals($configuredClientId, $clientId)) {
            throw InvalidHmacSignatureException::unknownClient();
        }

        $secret = $this->keyring->secret($client, $keyId);

        if ($secret === null) {
            throw InvalidHmacSignatureException::unknownKey();
        }

        $tolerance = config()->integer('hmac.request_time_tolerance');

        if (abs(now()->getTimestamp() - (int) $requestTime) > $tolerance) {
            throw InvalidHmacSignatureException::requestTimeOutOfRange();
        }

        $this->assertSignableBody($request);

        $expectedSignature = $this->signer->sign(HmacCanonicalRequest::fromRequest($request), $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            throw InvalidHmacSignatureException::signatureMismatch();
        }

        if (!Cache::add("hmac:nonce:{$client}:{$nonce}", true, 2 * $tolerance + 60)) {
            throw InvalidHmacSignatureException::nonceReused();
        }
    }

    /**
     * Refuse a request whose form fields or files PHP parsed out of a body it did not expose.
     */
    private function assertSignableBody(Request $request): void
    {
        if ($request->getContent() === '' && ($request->request->count() > 0 || $request->files->count() > 0)) {
            throw InvalidHmacSignatureException::unsignedBody();
        }
    }

    /**
     * Read a header that must be present and match a pattern.
     */
    private function requiredHeader(Request $request, string $header, string $pattern): string
    {
        $value = $request->header($header);

        if (!is_string($value) || preg_match($pattern, $value) !== 1) {
            throw InvalidHmacSignatureException::invalidHeader($header);
        }

        return $value;
    }

    /**
     * Read a header that may be absent but must match a pattern when present.
     */
    private function optionalHeader(Request $request, string $header, string $pattern): ?string
    {
        if (!$request->headers->has($header)) {
            return null;
        }

        return $this->requiredHeader($request, $header, $pattern);
    }
}
