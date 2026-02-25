<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WidgetSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function __construct(
        protected WidgetSubscriptionService $subscriptionService
    ) {}

    /**
     * POST /api/v1/widget/validate-license
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string',
            'domain'      => 'nullable|string',
            'platform'    => 'nullable|string',
        ]);

        $result = $this->subscriptionService->validateLicense($request->license_key);

        return response()->json($result);
    }

    /**
     * POST /api/v1/widget/activate-license
     */
    public function activate(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string',
            'domain'      => 'required|string',
            'platform'    => 'nullable|string',
            'site_name'   => 'nullable|string|max:200',
        ]);

        $result = $this->subscriptionService->activateLicense(
            $request->license_key,
            $request->domain,
            $request->only(['platform', 'site_name'])
        );

        return response()->json($result);
    }
}
