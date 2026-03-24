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

        // Allow main platform domains
        $trustedDomains = [
            'smartpropertywidget.com',
            'www.smartpropertywidget.com',
            'sm.smartpropertywidget.com',
            'localhost',
        ];

        if (in_array($originHost, $trustedDomains)) {
            $response = $next($request);
            return $this->addCorsHeaders($response, $origin);
        }

        // Check if origin is a registered client domain
        $isRegistered = Client::where('domain', $originHost)->exists()
            || ClientDomain::where('domain', $originHost)->exists();

        if (!$isRegistered) {
            return response()->json(['error' => 'Unauthorized domain'], 403);
        }

        $response = $next($request);
        return $this->addCorsHeaders($response, $origin);
    }

    protected function addCorsHeaders(Response $response, ?string $origin): Response
    {
        if ($origin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-API-Key, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '86400');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        return $response;
    }

    protected function buildCorsResponse(?string $origin, int $status): Response
    {
        $response = response('', $status);
        return $this->addCorsHeaders($response, $origin);
    }
}
