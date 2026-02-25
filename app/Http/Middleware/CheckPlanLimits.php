<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimits
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $client = $request->user()?->client;

        if (!$client) {
            return $next($request);
        }

        $features = $client->plan->features ?? [];
        $allowed = $features[$feature] ?? false;

        if (!$allowed) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'upgrade_required',
                    'message' => "Your current plan does not include {$feature}. Please upgrade.",
                ], 402);
            }

            return redirect()->back()->with('upgrade_required', $feature);
        }

        return $next($request);
    }
}
