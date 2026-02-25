<?php

namespace App\Http\Middleware;

use App\Models\Client;
use App\Models\ClientDomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WidgetCors
{
    /**
     * Domain-based CORS for widget API endpoints.
     * Only allows requests from registered client domains.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');

        // Preflight requests
        if ($request->isMethod('OPTIONS')) {
            return $this->buildCorsResponse($origin, 204);
        }

        // If no origin, allow (server-to-server calls like widget proxy)
        if (!$origin) {
            return $next($request);
        }

        $originHost = parse_url($origin, PHP_URL_HOST);

        if (!$originHost) {
            return response()->json(['error' => 'Invalid origin'], 403);
        }

        // Check if origin is a registered client domain
        $isRegistered = Client::where('domain', $originHost)->exists()
            || ClientDomain::where('domain', $originHost)->exists();

        if (!$isRegistered) {
            return response()->json(['error' => 'Unauthorized domain'], 403);
        }

        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-API-Key, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }

    protected function buildCorsResponse(?string $origin, int $status): Response
    {
        $response = response('', $status);
        if ($origin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-API-Key, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '86400');
        return $response;
    }
}
