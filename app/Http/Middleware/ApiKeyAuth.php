<?php

namespace App\Http\Middleware;

use App\Models\Client;
use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function __construct(protected TenantService $tenantService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json(['error' => 'API key required'], 401);
        }

        $client = Client::where('api_key', $apiKey)->where('status', 'active')->first();

        if (!$client) {
            return response()->json(['error' => 'Invalid or inactive API key'], 401);
        }

        $this->tenantService->setClient($client);
        $request->merge(['_api_client' => $client]);

        return $next($request);
    }
}
