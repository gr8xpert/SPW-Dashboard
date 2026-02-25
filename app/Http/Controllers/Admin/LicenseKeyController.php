<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Services\WidgetSubscriptionService;
use Illuminate\Http\Request;

class LicenseKeyController extends Controller
{
    public function __construct(
        protected WidgetSubscriptionService $subscriptionService
    ) {}

    public function index(Request $request)
    {
        $query = LicenseKey::with(['client', 'plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $licenseKeys = $query->orderByDesc('created_at')->paginate(25);
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.license-keys.index', compact('licenseKeys', 'plans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'count'   => 'required|integer|min:1|max:50',
            'notes'   => 'nullable|string|max:500',
        ]);

        $keys = [];
        for ($i = 0; $i < $request->count; $i++) {
            $key = $this->subscriptionService->generateLicenseKey(
                $request->plan_id,
                $request->notes
            );
            $keys[] = $key;
        }

        AuditLog::log('license_keys.generated', 'license_key', null, [
            'count'   => $request->count,
            'plan_id' => $request->plan_id,
        ]);

        return back()->with('success', "Generated {$request->count} license key(s).")
            ->with('generated_keys', $keys);
    }

    public function revoke(LicenseKey $licenseKey)
    {
        $licenseKey->revoke();

        AuditLog::log('license_key.revoked', 'license_key', $licenseKey->id);

        return back()->with('success', 'License key revoked.');
    }
}
