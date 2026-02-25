<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantIsolation
{
    public function __construct(protected TenantService $tenantService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $client = $user->client;

        if (!$client) {
            abort(403, 'No associated client account.');
        }

        // Super admins and webmasters are always allowed through
        if ($user->isSuperAdmin() || $user->isWebmaster()) {
            $this->tenantService->setClient($client);
            return $next($request);
        }

        // Check if client account is active
        if ($client->isSuspended()) {
            return redirect()->route('suspended');
        }

        if ($client->status === 'cancelled') {
            return redirect()->route('cancelled');
        }

        $this->tenantService->setClient($client);

        return $next($request);
    }
}
