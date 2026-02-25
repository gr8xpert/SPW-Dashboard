<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalApiAuth
{
    /**
     * Verify internal API key for n8n → Laravel communication.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = config('smartmailer.internal_api_key');

        if (empty($apiKey)) {
            return response()->json(['error' => 'Internal API not configured'], 500);
        }

        $providedKey = $request->header('X-Internal-API-Key')
            ?? $request->header('Authorization');

        if ($providedKey && str_starts_with($providedKey, 'Bearer ')) {
            $providedKey = substr($providedKey, 7);
        }

        if (!$providedKey || !hash_equals($apiKey, $providedKey)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
