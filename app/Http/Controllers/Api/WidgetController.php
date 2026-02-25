<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WidgetSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function __construct(
        protected WidgetSubscriptionService $subscriptionService
    ) {}

    /**
     * GET /api/v1/widget/subscription-check?domain={domain}
     */
    public function subscriptionCheck(Request $request): JsonResponse
    {
        $request->validate(['domain' => 'required|string']);

        $domain = $request->input('domain');
        $result = $this->subscriptionService->checkSubscription($domain);

        $response = response()->json($result);

        // Add warning headers for grace period
        if ($result['status'] === 'grace') {
            $response->header('X-RS-Subscription-Warning', 'grace_period');
            $response->header('X-RS-Grace-Days', $result['grace_days_remaining']);
        }

        return $response;
    }

    /**
     * GET /api/v1/widget/client-config?domain={domain}
     */
    public function clientConfig(Request $request): JsonResponse
    {
        $request->validate(['domain' => 'required|string']);

        $domain = $request->input('domain');
        $config = $this->subscriptionService->getClientConfig($domain);

        if (!$config) {
            return response()->json(['error' => 'Domain not registered'], 404);
        }

        return response()->json($config);
    }
}
