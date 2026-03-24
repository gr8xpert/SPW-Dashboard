<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientCustomFeatureGroup;
use App\Models\ClientCustomLocationGroup;
use App\Models\ClientCustomPropertyTypeGroup;
use App\Models\ClientDisplayPreference;
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
            // Resales Online API credentials
            'resales_client_id'   => 'nullable|string|max:50',
            'resales_api_key'     => 'nullable|string|max:255',
            'resales_filter_id'   => 'nullable|string|max:10',
            'resales_agency_code' => 'nullable|string|max:10',
            'resales_settings'    => 'nullable|array',
            'resales_settings.*.enabled'    => 'nullable|boolean',
            'resales_settings.*.filter_id'  => 'nullable|string|max:10',
            'resales_settings.*.own_filter' => 'nullable|string|max:10',
            'resales_settings.*.min_price'  => 'nullable|integer|min:0',
            // Location hierarchy types
            'location_parent_type' => 'nullable|string|in:area,municipality,province,region,country',
            'location_child_types' => 'nullable|array',
            'location_child_types.*' => 'string|in:city,town,municipality,area,urbanization',
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
            'wishlistIcon'           => $request->input('wc_wishlistIcon', 'heart'),
            'branding' => array_filter([
                'companyName'      => $request->input('wc_companyName', ''),
                'logoUrl'          => $request->input('wc_logoUrl', ''),
                'websiteUrl'       => $request->input('wc_websiteUrl', ''),
                'primaryColor'     => $request->input('wc_primaryColor', ''),
                'emailHeaderColor' => $request->input('wc_emailHeaderColor', ''),
            ]),
        ];

        // Handle reCAPTCHA keys - only save if filled, remove if explicitly cleared
        $siteKey = trim($request->input('wc_recaptchaSiteKey', ''));
        $secretKey = trim($request->input('wc_recaptchaSecretKey', ''));

        if (!empty($siteKey)) {
            $widgetConfig['recaptchaSiteKey'] = $siteKey;
        }
        if (!empty($secretKey)) {
            $widgetConfig['recaptchaSecretKey'] = $secretKey;
        }

        // Merge extra JSON overrides if provided
        if ($request->filled('wc_extraJson')) {
            $extra = json_decode($request->input('wc_extraJson'), true);
            if (is_array($extra)) {
                $widgetConfig['_extra'] = $extra;
                // Merge extra keys into top level (except reserved keys)
                $reserved = ['enableMapView', 'enableAISearch', 'enableCurrencyConverter', 'baseCurrency', 'availableCurrencies', 'wishlistIcon', 'recaptchaSiteKey', 'recaptchaSecretKey', 'branding', '_extra', 'priceRanges'];
                foreach ($extra as $k => $v) {
                    if (!in_array($k, $reserved)) {
                        $widgetConfig[$k] = $v;
                    }
                }
            }
        }

        // Parse custom price ranges from form (comma-separated values)
        $priceRanges = [];
        if ($request->has('widget_price_ranges')) {
            foreach ($request->input('widget_price_ranges', []) as $listingType => $ranges) {
                $priceRanges[$listingType] = [];

                if (!empty($ranges['min']) && is_string($ranges['min'])) {
                    $minValues = array_map('intval',
                        array_filter(array_map('trim', explode(',', $ranges['min'])), fn($v) => is_numeric(trim($v))));
                    if (!empty($minValues)) {
                        $priceRanges[$listingType]['min'] = array_values(array_unique($minValues));
                        sort($priceRanges[$listingType]['min']);
                    }
                }

                if (!empty($ranges['max']) && is_string($ranges['max'])) {
                    $maxValues = array_map('intval',
                        array_filter(array_map('trim', explode(',', $ranges['max'])), fn($v) => is_numeric(trim($v))));
                    if (!empty($maxValues)) {
                        $priceRanges[$listingType]['max'] = array_values(array_unique($maxValues));
                        sort($priceRanges[$listingType]['max']);
                    }
                }

                // Remove empty entries
                if (empty($priceRanges[$listingType]['min']) && empty($priceRanges[$listingType]['max'])) {
                    unset($priceRanges[$listingType]);
                }
            }
        }

        // Add price ranges to widget config if any are set
        if (!empty($priceRanges)) {
            $widgetConfig['priceRanges'] = $priceRanges;
        }

        // Auto-calculate grace period (expiry + 7 days) if expiry is set
        $graceEndsAt = null;
        if ($request->filled('subscription_expires_at')) {
            $expiryDate = \Carbon\Carbon::parse($request->input('subscription_expires_at'));
            $graceEndsAt = $expiryDate->copy()->addDays(config('smartmailer.widget.grace_period_days', 7));
        }

        // Process resales_settings - convert checkbox values to boolean
        $resalesSettings = [];
        if ($request->has('resales_settings')) {
            foreach ($request->input('resales_settings', []) as $key => $settings) {
                $resalesSettings[$key] = [
                    'enabled'    => isset($settings['enabled']),
                    'filter_id'  => $settings['filter_id'] ?? '1',
                    'own_filter' => $settings['own_filter'] ?? null,
                    'min_price'  => (int) ($settings['min_price'] ?? 0),
                ];
            }
        }

        $client->update(array_merge(
            $request->only([
                'company_name', 'domain', 'api_url', 'api_key', 'owner_email', 'default_language',
                'site_name', 'plan_id', 'billing_cycle', 'billing_source', 'subscription_status',
                'subscription_expires_at',
                // Resales Online credentials
                'resales_client_id', 'resales_api_key', 'resales_filter_id', 'resales_agency_code',
                // Location hierarchy types
                'location_parent_type',
            ]),
            [
                'grace_ends_at'     => $graceEndsAt,
                'widget_features'   => $request->input('widget_features', []),
                'widget_config'     => $widgetConfig,
                'resales_settings'  => $resalesSettings,
                'ai_search_enabled' => $request->boolean('ai_search_enabled'),
                'widget_enabled'    => $request->boolean('widget_enabled'),
                'admin_override'    => $request->boolean('admin_override'),
                'is_internal'       => $request->boolean('is_internal'),
                // Location child types: convert array to comma-separated string
                'location_child_type' => implode(',', $request->input('location_child_types', ['city'])),
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

        return response()->json($result);
    }

    public function testResales(Client $client)
    {
        if (!$client->resales_client_id || !$client->resales_api_key) {
            return response()->json([
                'success' => false,
                'message' => 'Resales credentials not configured. Save the form first.',
            ]);
        }

        try {
            // Test the LocationsTypes endpoint (lightweight, returns locations + property types)
            $url = 'https://webapi.resales-online.com/V6/LocationsTypes?' . http_build_query([
                'p1' => $client->resales_client_id,
                'p2' => $client->resales_api_key,
                'p_agency_filterid' => $client->resales_filter_id ?? '1',
            ]);

            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'SmartPropertyWidget/1.0',
                    'Accept' => 'application/json',
                ])
                ->timeout(15)
                ->get($url);

            if ($response->successful()) {
                // Safely parse JSON response
                $body = $response->body();
                $data = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'success' => false,
                        'message' => 'API returned invalid JSON. Response may be XML or HTML.',
                    ]);
                }

                // Check if we got locations data
                $locationCount = isset($data['LocationData']['Location'])
                    ? (is_array($data['LocationData']['Location']) ? count($data['LocationData']['Location']) : 0)
                    : 0;
                $typeCount = isset($data['PropertyTypes']['PropertyType'])
                    ? (is_array($data['PropertyTypes']['PropertyType']) ? count($data['PropertyTypes']['PropertyType']) : 0)
                    : 0;

                return response()->json([
                    'success' => true,
                    'message' => "Connected! Found {$locationCount} locations and {$typeCount} property types.",
                ]);
            }

            // Check for specific error responses
            $body = $response->body();
            if (str_contains($body, 'IP') || str_contains($body, 'blocked') || str_contains($body, 'whitelist')) {
                return response()->json([
                    'success' => false,
                    'message' => 'IP not whitelisted. Add server IP to Resales API key settings.',
                ]);
            }

            if (str_contains($body, 'Authentication') || str_contains($body, 'Invalid') || str_contains($body, 'credentials')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials. Check Client ID and API Key.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'API returned HTTP ' . $response->status() . '. Check credentials.',
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection timeout. The Resales API may be slow or unreachable.',
            ]);
        } catch (\Exception $e) {
            Log::error('Resales test connection failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ]);
        }
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

    /**
     * Show display preferences management page for locations, types, or features.
     */
    public function displayPreferences(Request $request, Client $client)
    {
        $type = $request->get('type', 'location');
        if (!in_array($type, ['location', 'property_type', 'feature'])) {
            $type = 'location';
        }

        // Get existing preferences for this client and type
        $preferences = ClientDisplayPreference::where('client_id', $client->id)
            ->where('item_type', $type)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('item_id');

        // Fetch available items from the microservice
        $items = $this->fetchItemsFromMicroservice($client, $type);

        // Include custom groups based on type if feature is enabled
        $customGroups = $this->getCustomGroupsForType($client, $type, $preferences);

        // Merge preferences with items
        $mergedItems = collect($items)->map(function ($item, $index) use ($preferences) {
            $itemId = (string) $item['id'];
            $pref = $preferences->get($itemId);

            return [
                'id'          => $itemId,
                'name'        => $item['name'],
                'parent_id'   => $item['parent_id'] ?? false,
                'type'        => $item['type'] ?? 'city',  // area, municipality, or city
                'is_custom'   => false,
                'visible'     => $pref ? $pref->visible : true,
                'sort_order'  => $pref ? $pref->sort_order : $index,
                'custom_name' => $pref ? $pref->custom_name : null,
                'has_pref'    => (bool) $pref,
            ];
        });

        // Add custom groups to the items collection (for locations only)
        if ($customGroups->isNotEmpty()) {
            $mergedItems = $customGroups->concat($mergedItems);
        }

        // Group by parent_id and sort within each group
        $grouped = $mergedItems->groupBy(fn($item) => $item['parent_id'] === false ? 'root' : $item['parent_id']);

        // Sort each group by sort_order, then by name
        $grouped = $grouped->map(function ($group) {
            return $group->sortBy([
                ['sort_order', 'asc'],
                ['name', 'asc'],
            ])->values();
        });

        // Rebuild flat list maintaining tree structure
        $sortedItems = collect();
        $addChildren = function ($parentId) use (&$addChildren, &$sortedItems, $grouped) {
            $children = $grouped->get($parentId === false ? 'root' : (string) $parentId, collect());
            foreach ($children as $child) {
                $sortedItems->push($child);
                $addChildren($child['id']);
            }
        };
        $addChildren(false);

        $mergedItems = $sortedItems;

        $typeLabels = [
            'location'      => 'Locations',
            'property_type' => 'Property Types',
            'feature'       => 'Features',
        ];

        return view('admin.widget-clients.display-preferences', [
            'client'    => $client,
            'type'      => $type,
            'typeLabel' => $typeLabels[$type],
            'items'     => $mergedItems,
        ]);
    }

    /**
     * Save display preferences for a client.
     */
    public function saveDisplayPreferences(Request $request, Client $client)
    {
        $type = $request->input('type');
        if (!in_array($type, ['location', 'property_type', 'feature'])) {
            return back()->with('error', 'Invalid item type.');
        }

        $items = $request->input('items', []);

        foreach ($items as $itemId => $data) {
            ClientDisplayPreference::updateOrCreate(
                [
                    'client_id' => $client->id,
                    'item_type' => $type,
                    'item_id'   => $itemId,
                ],
                [
                    'item_name'   => $data['name'] ?? null,
                    'visible'     => ($data['visible'] ?? '0') === '1' || $data['visible'] === true,
                    'sort_order'  => (int) ($data['sort_order'] ?? 0),
                    'custom_name' => $data['custom_name'] ?? null,
                ]
            );
        }

        AuditLog::log('widget_client.display_preferences_updated', 'client', $client->id, [
            'type'  => $type,
            'count' => count($items),
        ]);

        return back()->with('success', ucfirst(str_replace('_', ' ', $type)) . ' preferences saved successfully.');
    }

    /**
     * Move a display preference item up or down within its parent group.
     */
    public function movePreference(Request $request, Client $client)
    {
        $request->validate([
            'type'      => 'required|in:location,property_type,feature',
            'item_id'   => 'required|string',
            'direction' => 'required|in:up,down',
        ]);

        $type = $request->input('type');
        $itemId = $request->input('item_id');
        $direction = $request->input('direction');

        // Fetch all items from API
        $apiItems = collect($this->fetchItemsFromMicroservice($client, $type));

        // Get existing preferences to build custom groups properly
        $preferences = ClientDisplayPreference::where('client_id', $client->id)
            ->where('item_type', $type)
            ->get()
            ->keyBy('item_id');

        // Get custom groups for this type
        $customGroups = $this->getCustomGroupsForType($client, $type, $preferences);

        // Merge API items with custom groups
        $items = $apiItems->map(function ($item) {
            return [
                'id'        => (string) $item['id'],
                'name'      => $item['name'],
                'parent_id' => $item['parent_id'] ?? false,
            ];
        });

        if ($customGroups->isNotEmpty()) {
            $items = $customGroups->map(function ($group) {
                return [
                    'id'        => $group['id'],
                    'name'      => $group['name'],
                    'parent_id' => $group['parent_id'],
                ];
            })->concat($items);
        }

        // Find the current item (could be API item or custom group)
        $currentItem = $items->firstWhere('id', $itemId);

        if (!$currentItem) {
            return response()->json(['success' => false, 'message' => 'Item not found']);
        }

        // Get siblings (items with same parent_id)
        $parentId = $currentItem['parent_id'] ?? false;
        $siblings = $items->filter(function ($item) use ($parentId) {
            $itemParent = $item['parent_id'] ?? false;
            // Handle both false and string comparison
            if ($parentId === false) {
                return $itemParent === false || $itemParent === '' || $itemParent === null;
            }
            return (string) $itemParent === (string) $parentId;
        })->values();

        // Build sorted list with sort_order from preferences
        $sortedSiblings = $siblings->map(function ($item, $index) use ($preferences) {
            $pref = $preferences->get((string) $item['id']);
            return [
                'id'         => (string) $item['id'],
                'name'       => $item['name'],
                'sort_order' => $pref ? $pref->sort_order : $index,
            ];
        })->sortBy('sort_order')->values();

        // Find current position
        $currentIndex = $sortedSiblings->search(fn($item) => $item['id'] === (string) $itemId);

        if ($currentIndex === false) {
            return response()->json(['success' => false, 'message' => 'Item not found in siblings']);
        }

        // Calculate new index
        $newIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        // Check bounds
        if ($newIndex < 0 || $newIndex >= $sortedSiblings->count()) {
            return response()->json(['success' => false, 'message' => 'Cannot move further']);
        }

        // Get the sibling to swap with
        $swapWith = $sortedSiblings[$newIndex];

        // Swap sort_order values
        $currentSortOrder = $sortedSiblings[$currentIndex]['sort_order'];
        $swapSortOrder = $swapWith['sort_order'];

        // If they have the same sort_order, use indexes
        if ($currentSortOrder === $swapSortOrder) {
            $currentSortOrder = $currentIndex;
            $swapSortOrder = $newIndex;
        }

        // Update current item
        ClientDisplayPreference::updateOrCreate(
            ['client_id' => $client->id, 'item_type' => $type, 'item_id' => $itemId],
            ['sort_order' => $swapSortOrder, 'item_name' => $currentItem['name']]
        );

        // Update swap item
        ClientDisplayPreference::updateOrCreate(
            ['client_id' => $client->id, 'item_type' => $type, 'item_id' => $swapWith['id']],
            ['sort_order' => $currentSortOrder, 'item_name' => $swapWith['name']]
        );

        return response()->json([
            'success'      => true,
            'swapped_with' => $swapWith['id'],
        ]);
    }

    /**
     * Get custom groups for a specific type (location, property_type, feature).
     */
    protected function getCustomGroupsForType(Client $client, string $type, $preferences): \Illuminate\Support\Collection
    {
        $customGroups = collect();

        if ($type === 'location' && $client->custom_location_grouping_enabled) {
            $customGroups = ClientCustomLocationGroup::where('client_id', $client->id)
                ->whereNull('parent_group_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(function ($group) use ($preferences) {
                    $itemId = 'custom_group_' . $group->id;
                    $pref = $preferences->get($itemId);
                    $parentId = $group->parent_feed_location_id ?: false;

                    return [
                        'id'           => $itemId,
                        'name'         => $group->name,
                        'parent_id'    => $parentId,
                        'type'         => 'custom_group',
                        'is_custom'    => true,
                        'visible'      => $pref ? $pref->visible : true,
                        'sort_order'   => $pref ? $pref->sort_order : -1,
                        'custom_name'  => $pref ? $pref->custom_name : null,
                        'has_pref'     => (bool) $pref,
                        'mapped_count' => $group->mappings()->count(),
                        'parent_feed_location_name' => $group->parent_feed_location_name,
                    ];
                });
        } elseif ($type === 'property_type' && $client->custom_property_type_grouping_enabled) {
            $customGroups = ClientCustomPropertyTypeGroup::where('client_id', $client->id)
                ->whereNull('parent_group_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(function ($group) use ($preferences) {
                    $itemId = 'custom_type_group_' . $group->id;
                    $pref = $preferences->get($itemId);
                    $parentId = $group->parent_feed_type_id ?: false;

                    return [
                        'id'           => $itemId,
                        'name'         => $group->name,
                        'parent_id'    => $parentId,
                        'type'         => 'custom_group',
                        'is_custom'    => true,
                        'visible'      => $pref ? $pref->visible : true,
                        'sort_order'   => $pref ? $pref->sort_order : -1,
                        'custom_name'  => $pref ? $pref->custom_name : null,
                        'has_pref'     => (bool) $pref,
                        'mapped_count' => $group->mappings()->count(),
                        'parent_feed_type_name' => $group->parent_feed_type_name ?? null,
                    ];
                });
        } elseif ($type === 'feature' && $client->custom_feature_grouping_enabled) {
            $customGroups = ClientCustomFeatureGroup::where('client_id', $client->id)
                ->whereNull('parent_group_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(function ($group) use ($preferences) {
                    $itemId = 'custom_feature_group_' . $group->id;
                    $pref = $preferences->get($itemId);
                    $parentId = $group->parent_feed_feature_id ?: false;

                    return [
                        'id'           => $itemId,
                        'name'         => $group->name,
                        'parent_id'    => $parentId,
                        'type'         => 'custom_group',
                        'is_custom'    => true,
                        'visible'      => $pref ? $pref->visible : true,
                        'sort_order'   => $pref ? $pref->sort_order : -1,
                        'custom_name'  => $pref ? $pref->custom_name : null,
                        'has_pref'     => (bool) $pref,
                        'mapped_count' => $group->mappings()->count(),
                        'parent_feed_feature_name' => $group->parent_feed_feature_name ?? null,
                    ];
                });
        }

        return $customGroups;
    }

    /**
     * Fetch items (locations, types, or features) from the appropriate API.
     * Supports: Resales (spw-transform), CRM (direct), and Odoo (inmotechplugin).
     */
    protected function fetchItemsFromMicroservice(Client $client, string $type): array
    {
        // Primary and fallback endpoints for different API types
        $endpoints = [
            'location'      => ['/v2/location', '/v1/location', '/v1/locations'],
            'property_type' => ['/v1/property_types', '/v1/property-types'],
            'feature'       => ['/v1/property_features', '/v1/property-features', '/v1/features'],
        ];

        $endpointList = $endpoints[$type] ?? null;
        if (!$endpointList) {
            return [];
        }

        try {
            // Determine which API to use based on client configuration
            $apiUrl = null;
            $headers = ['Accept' => 'application/json'];

            if ($client->resales_client_id && $client->resales_api_key) {
                // Resales clients: use spw-transform
                $apiUrl = 'https://api.smartpropertywidget.com';
                $queryParam = '?_domain=' . urlencode($client->domain);
            } elseif ($client->api_url && $client->api_key) {
                // CRM/Odoo clients: use their configured API
                $apiUrl = rtrim($client->api_url, '/');
                $headers['access_token'] = $client->api_key;
                $queryParam = '';
            } else {
                // No API configured
                \Log::info("No API configured for client {$client->domain}");
                return [];
            }

            $response = null;
            $lastUrl = '';

            // Try each endpoint until one succeeds
            foreach ($endpointList as $endpoint) {
                $url = $apiUrl . $endpoint . $queryParam;
                $lastUrl = $url;

                \Log::info("Fetching {$type} for {$client->domain}: {$url}");

                $response = Http::withoutVerifying()
                    ->connectTimeout(10)
                    ->timeout(60)
                    ->withHeaders($headers)
                    ->get($url);

                if ($response->successful()) {
                    \Log::info("Success fetching {$type} from {$endpoint}");
                    break;
                }

                \Log::info("Failed {$endpoint} with HTTP {$response->status()}, trying next...");
            }

            if ($response && $response->successful()) {
                $data = $response->json();

                \Log::info("Response for {$type}: " . json_encode(array_keys($data ?? [])));

                // Handle different response formats
                $items = [];

                // Check for 'data' wrapper
                if (isset($data['data']) && is_array($data['data'])) {
                    $items = $data['data'];
                }
                // Check if response is already an array of items
                elseif (is_array($data) && !empty($data) && isset($data[0])) {
                    $items = $data;
                }
                // Some APIs return {'count': X, 'data': [...]}
                elseif (isset($data['count']) && isset($data['data'])) {
                    $items = $data['data'];
                }

                // Handle features which have nested structure (groups with value_ids)
                if ($type === 'feature' && !empty($items)) {
                    // Check if first item has value_ids (grouped format)
                    if (isset($items[0]['value_ids']) || isset($items[0]['values'])) {
                        $flatFeatures = [];
                        foreach ($items as $group) {
                            // Add group itself
                            $flatFeatures[] = [
                                'id'        => 'group_' . $group['id'],
                                'name'      => ($group['name'] ?? 'Group') . ' (Category)',
                                'parent_id' => false,
                            ];
                            // Add values within group (try both 'value_ids' and 'values')
                            $values = $group['value_ids'] ?? $group['values'] ?? [];
                            foreach ($values as $value) {
                                $flatFeatures[] = [
                                    'id'        => $value['id'],
                                    'name'      => $value['name'],
                                    'parent_id' => 'group_' . $group['id'],
                                ];
                            }
                        }
                        return $flatFeatures;
                    }
                    // Already flat format
                    return $items;
                }

                \Log::info("Returning " . count($items) . " items for {$type}");
                return $items;
            }

            \Log::warning("All endpoints failed for {$type}, client {$client->domain}");

        } catch (\Exception $e) {
            // Log error but don't fail
            \Log::warning("Failed to fetch {$type} from API for client {$client->domain}: " . $e->getMessage());
        }

        return [];
    }
}
