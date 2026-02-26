<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\LicenseKey;
use App\Models\Plan;
use Illuminate\Http\Request;

class LicenseKeyController extends Controller
{
    public function index(Request $request)
    {
        $query = LicenseKey::with(['client', 'plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $licenseKeys = $query->orderByDesc('created_at')->paginate(25);
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        $clients = Client::orderBy('company_name')->get();

        return view('admin.license-keys.index', compact('licenseKeys', 'plans', 'clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id'  => 'required|exists:clients,id',
            'plan_id'    => 'required|exists:plans,id',
            'expires_in' => 'nullable|string|in:1_month,1_year,5_years,never',
        ]);

        $client = Client::findOrFail($request->client_id);

        $licenseKey = LicenseKey::create([
            'client_id' => $client->id,
            'plan_id'   => $request->plan_id,
            'status'    => 'activated',
            'activated_at' => now(),
            'activated_domain' => $client->domain,
        ]);

        AuditLog::log('license_key.generated', 'license_key', $licenseKey->id, [
            'client_id' => $client->id,
            'plan_id'   => $request->plan_id,
        ]);

        return back()->with('success', 'License key generated successfully.')
            ->with('generated_key', $licenseKey->license_key);
    }

    public function revoke(LicenseKey $licenseKey)
    {
        $licenseKey->revoke();

        AuditLog::log('license_key.revoked', 'license_key', $licenseKey->id);

        return back()->with('success', 'License key revoked.');
    }
}
