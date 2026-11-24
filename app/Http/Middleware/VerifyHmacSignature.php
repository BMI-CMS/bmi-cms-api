<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Hmac\HmacVerifier;
use Symfony\Component\HttpFoundation\Response;

class VerifyHmacSignature
{
    /**
     * Create a new HMAC signature middleware instance.
     */
    public function __construct(private readonly HmacVerifier $verifier) {}

    /**
     * Handle an incoming request and verify its HMAC signature against the client's keyring.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $client): Response
    {
        $this->verifier->verify($request, $client);

        $request->attributes->set('hmac_client', $client);

        return $next($request);
    }
}
