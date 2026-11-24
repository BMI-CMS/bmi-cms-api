<?php

namespace App\Exceptions\Auth;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidHmacSignatureException extends Exception
{
    /**
     * Create a new invalid HMAC signature exception instance.
     */
    private function __construct(string $reason, private readonly int $status = 401)
    {
        parent::__construct($reason);
    }

    /**
     * Create an exception for a signing header that is missing or malformed.
     */
    public static function invalidHeader(string $header): self
    {
        return new self("The {$header} header is missing or malformed.");
    }

    /**
     * Create an exception for a client id that does not match the configured one.
     */
    public static function unknownClient(): self
    {
        return new self('The X-Client-Id header does not match the configured client.');
    }

    /**
     * Create an exception for a key id that is not in the client's keyring.
     */
    public static function unknownKey(): self
    {
        return new self('The X-Key-Id header names no configured key.');
    }

    /**
     * Create an exception for a request time outside the accepted window.
     */
    public static function requestTimeOutOfRange(): self
    {
        return new self('The X-Request-Time header is outside the accepted window.');
    }

    /**
     * Create an exception for a query string longer than the allowed number of characters.
     */
    public static function queryTooLong(int $limit): self
    {
        return new self("The query string is longer than {$limit} characters.", 400);
    }

    /**
     * Create an exception for a query string with more parameters than allowed.
     */
    public static function queryTooManyParameters(int $limit): self
    {
        return new self("The query string has more than {$limit} parameters.", 400);
    }

    /**
     * Create an exception for a query string key nested deeper than allowed.
     */
    public static function queryTooDeep(int $limit): self
    {
        return new self("A query string key is nested deeper than {$limit} levels.", 400);
    }

    /**
     * Create an exception for form fields or files that PHP parsed out of a body it did not expose.
     */
    public static function unsignedBody(): self
    {
        return new self('The request carries form fields or files that are not part of the signed body.');
    }

    /**
     * Create an exception for a signature that does not match the request.
     */
    public static function signatureMismatch(): self
    {
        return new self('The X-Signature header does not match the request.');
    }

    /**
     * Create an exception for a nonce that was already used.
     */
    public static function nonceReused(): self
    {
        return new self('The X-Nonce header was already used.');
    }

    /**
     * Render a query limit failure with its reason and every other failure as the same unauthorized JSON response.
     */
    public function render(): JsonResponse
    {
        if ($this->status === 400) {
            return response()->json(['message' => $this->getMessage()], 400);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }

    /**
     * Report the exception only when invalid HMAC signatures are configured to be reported.
     */
    public function report(): bool
    {
        return !config()->boolean('hmac.report_invalid_signature');
    }
}
