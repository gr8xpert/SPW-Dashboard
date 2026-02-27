<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\LicenseKey;
use App\Models\Plan;
use App\Services\WidgetSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WidgetClientController extends Controller
{
    public function __construct(
        protected WidgetSubscriptionService $subscriptionService
    ) {}

    public function index(Request $request)
    {
        $query = Client::with('plan')
            ->withCount('supportTickets');

        if ($request->filled('status')) {
            $query->where('subscription_status', $request->status);
        }

        if ($request->filled('plan')) {
            $query->where('plan_id', $request->plan);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('company_name', 'like', "%{$request->search}%")
                    ->orWhere('domain', 'like', "%{$request->search}%");
            });
        }

        $clients = $query->orderByDesc('id')->paginate(25);
        $plans = Plan::orderBy('sort_order')->get();

        return view('admin.widget-clients.index', compact('clients', 'plans'));
    }

    public function edit(Client $client)
    {
        $client->load(['plan', 'domains', 'licenseKeys']);
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.widget-clients.edit', compact('client', 'plans'));
    }

    public function update(Request $request, Client $client)
    {
        $request->validate([
            'company_name'        => 'required|string|max:255',
            'domain'              => 'nullable|string|max:255',
            'api_url'             => 'nullable|url|max:500',
            'api_key'             => 'nullable|string|max:255',
            'owner_email'         => 'nullable|email|max:255',
            'default_language'    => 'nullable|string|max:10',
            'site_name'           => 'nullable|string|max:255',
            'widget_features'     => 'nullable|array',
            'plan_id'             => 'nullable|exists:plans,id',
            'billing_cycle'       => 'nullable|in:monthly,yearly',
            'billing_source'      => 'nullable|in:paddle,manual,internal',
            'subscription_status' => 'nullable|in:active,grace,expired,manual,internal',
            // Widget config fields
            'wc_baseCurrency'          => 'nullable|string|max:5',
            'wc_availableCurrencies'   => 'nullable|array',
            'wc_companyName'           => 'nullable|string|max:255',
            'wc_logoUrl'               => 'nullable|url|max:500',
            'wc_websiteUrl'            => 'nullable|url|max:500',
            'wc_primaryColor'          => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'wc_emailHeaderColor'      => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'wc_extraJson'             => 'nullable|json',
        ]);

        $oldValues = $client->only([
            'domain', 'widget_enabled', 'admin_override',
            'is_internal', 'billing_source', 'subscription_status', 'plan_id',
        ]);

        // Normalize domain: strip protocol, www, and trailing slash
        if ($request->filled('domain')) {
            $domain = $request->input('domain');
            $domain = preg_replace('#^https?://#i', '', $domain);
            $domain = preg_replace('#^www\.#i', '', $domain);
            $domain = rtrim($domain, '/');
            $request->merge(['domain' => $domain]);
        }

        // Build widget_config from individual form fields
        $widgetConfig = [
            'enableMapView'          => $request->boolean('wc_enableMapView'),
            'enableAISearch'         => $request->boolean('ai_search_enabled'),
            'enableCurrencyConverter' => $request->boolean('wc_enableCurrencyConverter'),
            'baseCurrency'           => $request->input('wc_baseCurrency', 'EUR'),
            'availableCurrencies'    => $request->input('wc_availableCurrencies', ['EUR']),
            'branding' => array_filter([
                'companyName'      => $request->input('wc_companyName', ''),
                'logoUrl'          => $request->input('wc_logoUrl', ''),
                'websiteUrl'       => $request->input('wc_websiteUrl', ''),
                'primaryColor'     => $request->input('wc_primaryColor', ''),
                'emailHeaderColor' => $request->input('wc_emailHeaderColor', ''),
            ]),
        ];

        // Merge extra JSON overrides if provided
        if ($request->filled('wc_extraJson')) {
            $extra = json_decode($request->input('wc_extraJson'), true);
            if (is_array($extra)) {
                $widgetConfig['_extra'] = $extra;
                // Merge extra keys into top level (except reserved keys)
                $reserved = ['enableMapView', 'enableAISearch', 'enableCurrencyConverter', 'baseCurrency', 'availableCurrencies', 'branding', '_extra'];
                foreach ($extra as $k => $v) {
                    if (!in_array($k, $reserved)) {
                        $widgetConfig[$k] = $v;
                    }
                }
            }
        }

        // Auto-calculate grace period (expiry + 7 days) if expiry is set
        $graceEndsAt = null;
        if ($request->filled('subscription_expires_at')) {
            $expiryDate = \Carbon\Carbon::parse($request->input('subscription_expires_at'));
            $graceEndsAt = $expiryDate->copy()->addDays(config('smartmailer.widget.grace_period_days', 7));
        }

        $client->update(array_merge(
            $request->only([
                'company_name', 'domain', 'api_url', 'api_key', 'owner_email', 'default_language',
                'site_name', 'plan_id', 'billing_cycle', 'billing_source', 'subscription_status',
                'subscription_expires_at',
            ]),
            [
                'grace_ends_at'     => $graceEndsAt,
                'widget_features'   => $request->input('widget_features', []),
                'widget_config'     => $widgetConfig,
                'ai_search_enabled' => $request->boolean('ai_search_enabled'),
                'widget_enabled'    => $request->boolean('widget_enabled'),
                'admin_override'    => $request->boolean('admin_override'),
                'is_internal'       => $request->boolean('is_internal'),
            ]
        ));

        AuditLog::log(
            'widget_client.updated',
            'client',
            $client->id,
            ['changes' => $request->all()],
            $oldValues,
            $client->fresh()->only(array_keys($oldValues))
        );

        return redirect()->route('admin.widget-clients.edit', $client)
            ->with('success', 'Widget client updated successfully.');
    }

    public function toggleOverride(Client $client)
    {
        $newState = !$client->admin_override;
        $this->subscriptionService->setAdminOverride($client->id, $newState);

        AuditLog::log('widget_client.override_toggled', 'client', $client->id, [
            'admin_override' => $newState,
        ]);

        return back()->with('success', $newState
            ? 'Admin override enabled — widget will always be active.'
            : 'Admin override disabled — billing status will be enforced.');
    }

    public function extendSubscription(Request $request, Client $client)
    {
        $request->validate(['period' => 'required|string|in:1 month,3 months,6 months,1 year']);

        $this->subscriptionService->extendSubscription($client->id, $request->period);

        AuditLog::log('widget_client.subscription_extended', 'client', $client->id, [
            'period' => $request->period,
        ]);

        return back()->with('success', "Subscription extended by {$request->period}.");
    }

    public function manualActivate(Client $client)
    {
        $this->subscriptionService->manualActivate($client->id);

        AuditLog::log('widget_client.manually_activated', 'client', $client->id);

        return back()->with('success', 'Client manually activated.');
    }

    public function expire(Client $client)
    {
        $this->subscriptionService->expireSubscription($client->id);

        AuditLog::log('widget_client.manually_expired', 'client', $client->id);

        return back()->with('success', 'Client subscription expired.');
    }

    public function revokeLicense(Client $client)
    {
        $key = $client->licenseKeys()->where('status', 'activated')->first();

        if (!$key) {
            return back()->with('error', 'No active license key to revoke.');
        }

        $key->update(['status' => 'revoked']);

        AuditLog::log('license_key.revoked', 'client', $client->id, [
            'license_key' => $key->license_key,
        ]);

        return back()->with('success', 'License key revoked.');
    }

    public function regenerateLicense(Client $client)
    {
        // Revoke any existing active keys
        $client->licenseKeys()->where('status', 'activated')->update([
            'status' => 'revoked',
        ]);

        // Generate new key
        $newKey = LicenseKey::create([
            'client_id'        => $client->id,
            'plan_id'          => $client->plan_id,
            'status'           => 'activated',
            'activated_at'     => now(),
            'activated_domain' => $client->domain,
        ]);

        AuditLog::log('license_key.regenerated', 'client', $client->id, [
            'license_key' => $newKey->license_key,
        ]);

        return back()->with('success', 'New license key generated.');
    }

    public function checkConnection(Client $client)
    {
        $domain = $client->domain;

        $result = [
            'config' => false,
            'config_issues' => [],
            'widget' => null,
        ];

        // 1. Verify client has all required fields for proxy authorization
        if (!$domain) {
            $result['config_issues'][] = 'No domain set';
        }
        if (!$client->api_url) {
            $result['config_issues'][] = 'No API URL';
        }
        if (!$client->api_key) {
            $result['config_issues'][] = 'No API key';
        }
        if (!$client->widget_enabled) {
            $result['config_issues'][] = 'Widget disabled';
        }
        if ($client->subscription_status === 'expired') {
            $result['config_issues'][] = 'Subscription expired';
        }

        $result['config'] = empty($result['config_issues']);
        $result['status'] = $client->subscription_status;
        $result['override'] = (bool) $client->admin_override;

        // 2. Test the CRM API directly to verify API credentials work
        if ($client->api_url && $client->api_key) {
            try {
                $apiUrl = rtrim($client->api_url, '/') . '/v1/property_types';
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'User-Agent' => 'SmartPropertyWidget/1.0',
                        'access_token' => $client->api_key,
                        'Accept' => 'application/json',
                    ])
                    ->timeout(10)
                    ->get($apiUrl);

                $result['api'] = $response->successful();
                if (!$response->successful()) {
                    $result['api_detail'] = 'HTTP ' . $response->status();
                }
            } catch (\Exception $e) {
                $result['api'] = false;
                $result['api_detail'] = 'Unreachable';
            }
        } else {
            $result['api'] = false;
        }

        // 3. Check if widget is installed on the client site
        if ($domain) {
            try {
                $siteResponse = Http::withoutVerifying()
                    ->withHeaders(['User-Agent' => 'SmartPropertyWidget/1.0'])
                    ->timeout(10)
                    ->get("https://{$domain}");

                if ($siteResponse->successful()) {
                    $body = $siteResponse->body();
                    $result['widget'] = str_contains($body, 'realtysoft-loader') || str_contains($body, 'RealtySoftConfig');
                }
            } catch (\Exception $e) {
                $result['widget'] = null;
            }
        }

        return response()->json($result);
    }

    public function subscriptionStatus()
    {
        $counts = [
            'active'     => Client::where('subscription_status', 'active')->count(),
            'grace'      => Client::where('subscription_status', 'grace')->count(),
            'expired'    => Client::where('subscription_status', 'expired')->count(),
            'manual'     => Client::where('subscription_status', 'manual')->count(),
            'internal'   => Client::where('subscription_status', 'internal')->count(),
            'overridden' => Client::where('admin_override', true)->count(),
        ];

        $graceClients = Client::with('plan')
            ->where('subscription_status', 'grace')
            ->orderBy('grace_ends_at')
            ->get();

        $recentlyExpired = Client::with('plan')
            ->where('subscription_status', 'expired')
            ->where('subscription_expires_at', '>=', now()->subDays(30))
            ->orderByDesc('subscription_expires_at')
            ->get();

        return view('admin.widget-clients.subscription-status', compact(
            'counts', 'graceClients', 'recentlyExpired'
        ));
    }
}
