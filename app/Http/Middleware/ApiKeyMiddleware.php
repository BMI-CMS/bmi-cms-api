<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = config('services.api.key');
        $apiKey = $request->header('BMI_CMS_KEY')
            ?? $request->header('BMI-CMS-KEY')
            ?? $request->header('x-api-key')
            ?? $request->header('X-API-KEY');

        if (!$expectedKey || !$apiKey || !hash_equals((string) $expectedKey, (string) $apiKey)) {
            return response()->json([
                'message' => 'Invalid API key.',
            ], 401);
        }

        return $next($request);
    }
}
