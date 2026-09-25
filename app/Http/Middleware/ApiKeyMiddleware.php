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
        $apiKey = $request->header('BMI_CMS_KEY');

        if (!$apiKey || !hash_equals(config('services.api.key'), $apiKey)) {
            return response()->json([
                'message' => 'Invalid API key.',
            ], 401);
        }

        return $next($request);
    }
}
