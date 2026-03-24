<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientDisplayPreference;
use App\Services\LocationGroupingService;
use App\Services\PropertyTypeGroupingService;
use App\Services\FeatureGroupingService;
use App\Services\WidgetSubscriptionService;
use App\Services\LabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WidgetProxyController extends Controller
{
    public function __construct(
        protected WidgetSubscriptionService $subscriptionService,
        protected LocationGroupingService $locationGroupingService,
        protected PropertyTypeGroupingService $propertyTypeGroupingService,
        protected FeatureGroupingService $featureGroupingService,
        protected LabelService $labelService
    ) {}

    /**
     * Proxy locations with display preferences applied.
     * GET /api/v1/widget/locations?domain={domain}&lang={lang}
     */
    public function locations(Request $request): JsonResponse
    {
        $request->validate(['domain' => 'required|string']);

        $domain = $request->input('domain');
        $lang = $request->input('lang', 'en_US');
        $client = $this->subscriptionService->findClientByDomain($domain);

        if (!$client) {
            return response()->json(['error' => 'Domain not registered'], 404);
        }

        if (!$this->subscriptionService->shouldAllowAccess($client)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Check for custom location grouping
        if ($client->custom_location_grouping_enabled) {
            $mergedLocations = $this->locationGroupingService->getMergedLocations($client, $lang);
            if (!empty($mergedLocations)) {
                return response()->json([
                    'data' => $mergedLocations,
                    'count' => count($mergedLocations),
                    'has_custom_grouping' => true,
                ]);
            }
        }

        // Fall back to standard flow with display preferences
        return $this->proxyWithPreferences($request, 'location', ['/v2/location', '/v1/location'], $lang);
    }

    /**
     * Proxy property types with display preferences applied.
     * GET /api/v1/widget/property-types?domain={domain}&lang={lang}
     */
    public function propertyTypes(Request $request): JsonResponse
    {
        $request->validate(['domain' => 'required|string']);

        $domain = $request->input('domain');
        $lang = $request->input('lang', 'en_US');
        $client = $this->subscriptionService->findClientByDomain($domain);

        if (!$client) {
            return response()->json(['error' => 'Domain not registered'], 404);
        }

        if (!$this->subscriptionService->shouldAllowAccess($client)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Check for custom property type grouping
        if ($client->custom_property_type_grouping_enabled) {
            $mergedTypes = $this->propertyTypeGroupingService->getMergedPropertyTypes($client, $lang);
            if (!empty($mergedTypes)) {
                return response()->json([
                    'data' => $mergedTypes,
                    'count' => count($mergedTypes),
                    'has_custom_grouping' => true,
                ]);
            }
        }

        // Fall back to standard flow with display preferences
        return $this->proxyWithPreferences($request, 'property_type', ['/v1/property_types'], $lang);
    }

    /**
     * Proxy features with display preferences applied.
     * GET /api/v1/widget/features?domain={domain}&lang={lang}
     */
    public function features(Request $request): JsonResponse
    {
        $request->validate(['domain' => 'required|string']);

        $domain = $request->input('domain');
        $lang = $request->input('lang', 'en_US');
        $client = $this->subscriptionService->findClientByDomain($domain);

        if (!$client) {
            return response()->json(['error' => 'Domain not registered'], 404);
        }

        if (!$this->subscriptionService->shouldAllowAccess($client)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Check for custom feature grouping
        if ($client->custom_feature_grouping_enabled) {
            $mergedFeatures = $this->featureGroupingService->getMergedFeatures($client, $lang);
            if (!empty($mergedFeatures)) {
                return response()->json([
                    'data' => $mergedFeatures,
                    'count' => count($mergedFeatures),
                    'has_custom_grouping' => true,
                ]);
            }
        }

        // Fall back to standard flow with display preferences
        return $this->proxyWithPreferences($request, 'feature', ['/v1/property_features'], $lang);
    }

    /**
     * Fetch data from API and apply display preferences.
     */
    protected function proxyWithPreferences(Request $request, string $type, array $endpoints, string $lang = 'en_US'): JsonResponse
    {
        $request->validate(['domain' => 'required|string']);

        $domain = $request->input('domain');
        $client = $this->subscriptionService->findClientByDomain($domain);

        if (!$client) {
            return response()->json(['error' => 'Domain not registered'], 404);
        }

        if (!$this->subscriptionService->shouldAllowAccess($client)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Fetch data from the appropriate API with language
        $data = $this->fetchFromApi($client, $endpoints, $lang);

        if ($data === null) {
            return response()->json(['error' => 'Failed to fetch data from API'], 502);
        }

        // Get display preferences
        $preferences = $this->getPreferences($client->id, $type);

        // Apply preferences (filter hidden, reorder, rename)
        $filteredData = $this->applyPreferences($data, $preferences, $type);

        return response()->json([
            'data' => $filteredData,
            'count' => count($filteredData),
        ]);
    }

    /**
     * Fetch data from the client's API (Resales or CRM).
     * @param Client $client The client to fetch data for
     * @param array $endpoints The API endpoints to try
     * @param string $lang The language code for translations (e.g., 'it_IT', 'es_ES')
     */
    protected function fetchFromApi(Client $client, array $endpoints, string $lang = 'en_US'): ?array
    {
        $apiUrl = null;
        $headers = ['Accept' => 'application/json'];
        $queryParam = '';

        if ($client->resales_client_id && $client->resales_api_key) {
            // Resales clients: use spw-transform with language
            $apiUrl = 'https://api.smartpropertywidget.com';
            $queryParam = '?_domain=' . urlencode($client->domain) . '&_lang=' . urlencode($lang);
        } elseif ($client->api_url && $client->api_key) {
            // CRM/Odoo clients: use their configured API with language
            $apiUrl = rtrim($client->api_url, '/');
            $headers['access_token'] = $client->api_key;
            // CRM uses 'ln' parameter for language (not 'lang')
            $queryParam = '?ln=' . urlencode($lang);
        } else {
            Log::warning("No API configured for client {$client->domain}");
            return null;
        }

        // Try each endpoint until one succeeds
        foreach ($endpoints as $endpoint) {
            try {
                $url = $apiUrl . $endpoint . $queryParam;

                $response = Http::withoutVerifying()
                    ->timeout(15)
                    ->withHeaders($headers)
                    ->get($url);

                if ($response->successful()) {
                    $json = $response->json();

                    // Extract data array from response
                    if (isset($json['data']) && is_array($json['data'])) {
                        return $json['data'];
                    } elseif (is_array($json) && !empty($json) && isset($json[0])) {
                        return $json;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("API fetch failed for {$endpoint}: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Get display preferences for a client and type.
     */
    protected function getPreferences(int $clientId, string $type): array
    {
        $prefs = ClientDisplayPreference::where('client_id', $clientId)
            ->where('item_type', $type)
            ->get();

        return [
            'hidden_ids' => $prefs->where('visible', false)->pluck('item_id')->toArray(),
            'sort_order' => $prefs->pluck('sort_order', 'item_id')->toArray(),
            'custom_names' => $prefs->whereNotNull('custom_name')
                ->where('custom_name', '!=', '')
                ->pluck('custom_name', 'item_id')
                ->toArray(),
        ];
    }

    /**
     * Apply display preferences to data.
     */
    protected function applyPreferences(array $data, array $preferences, string $type): array
    {
        $hiddenIds = $preferences['hidden_ids'];
        $sortOrder = $preferences['sort_order'];
        $customNames = $preferences['custom_names'];

        // Filter out hidden items
        $filtered = array_filter($data, function ($item) use ($hiddenIds) {
            $id = (string) ($item['id'] ?? '');
            return !in_array($id, $hiddenIds);
        });

        // Apply custom names
        $filtered = array_map(function ($item) use ($customNames) {
            $id = (string) ($item['id'] ?? '');
            if (isset($customNames[$id]) && $customNames[$id]) {
                $item['name'] = $customNames[$id];
            }
            return $item;
        }, $filtered);

        // Apply sort order (group by parent_id for hierarchical data)
        $filtered = $this->sortByPreferences($filtered, $sortOrder);

        return array_values($filtered);
    }

    /**
     * Check if an item is a root item (no parent).
     */
    protected function isRootItem($parentId): bool
    {
        // Handle all possible "no parent" values from different APIs
        return $parentId === false
            || $parentId === null
            || $parentId === 0
            || $parentId === '0'
            || $parentId === ''
            || !$parentId;  // Catches any falsy value
    }

    /**
     * Sort items by preferences while maintaining hierarchy.
     */
    protected function sortByPreferences(array $items, array $sortOrder): array
    {
        if (empty($sortOrder)) {
            return $items;
        }

        // Group items by parent_id
        $grouped = [];
        $roots = [];

        foreach ($items as $item) {
            $parentId = $item['parent_id'] ?? false;
            if ($this->isRootItem($parentId)) {
                $roots[] = $item;
            } else {
                $grouped[$parentId][] = $item;
            }
        }

        // Sort roots
        usort($roots, function ($a, $b) use ($sortOrder) {
            $orderA = $sortOrder[(string) $a['id']] ?? 9999;
            $orderB = $sortOrder[(string) $b['id']] ?? 9999;
            return $orderA <=> $orderB;
        });

        // Sort each group of children
        foreach ($grouped as $parentId => $children) {
            usort($grouped[$parentId], function ($a, $b) use ($sortOrder) {
                $orderA = $sortOrder[(string) $a['id']] ?? 9999;
                $orderB = $sortOrder[(string) $b['id']] ?? 9999;
                return $orderA <=> $orderB;
            });
        }

        // Rebuild flat array maintaining tree structure
        $result = [];
        $addWithChildren = function ($items) use (&$addWithChildren, &$result, $grouped) {
            foreach ($items as $item) {
                $result[] = $item;
                $id = (string) $item['id'];
                if (isset($grouped[$id])) {
                    $addWithChildren($grouped[$id]);
                }
            }
        };
        $addWithChildren($roots);

        return $result;
    }

    /**
     * Get merged labels (defaults + client overrides) and enabled listing types.
     * GET /api/v1/widget/labels?domain={domain}&lang={language}
     */
    public function labels(Request $request): JsonResponse
    {
        $request->validate([
            'domain' => 'required|string',
            'lang' => 'nullable|string|max:10',
        ]);

        $domain = $request->input('domain');
        $client = $this->subscriptionService->findClientByDomain($domain);

        if (!$client) {
            return response()->json(['error' => 'Domain not registered'], 404);
        }

        if (!$this->subscriptionService->shouldAllowAccess($client)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Get language from request or use client default
        $language = $request->input('lang', $client->default_language ?? 'en_US');

        // Get merged labels (defaults + overrides)
        $labels = $this->labelService->getMergedLabels($client->id, $language);

        // Get enabled listing types from client's resales settings
        $enabledListingTypes = $this->subscriptionService->getEnabledListingTypes($client);

        // Get custom price ranges from client's widget_config
        $widgetConfig = $client->widget_config ?? [];
        $priceRanges = $widgetConfig['priceRanges'] ?? null;

        // Build response
        $response = [
            'labels' => $labels,
            'enabledListingTypes' => $enabledListingTypes,
            'owner_email' => $client->owner_email,
            // Location hierarchy configuration
            'location_parent_type' => $client->location_parent_type ?? 'municipality',
            'location_child_types' => array_filter(explode(',', $client->location_child_type ?? 'city')),
        ];

        // Only include priceRanges if configured
        if ($priceRanges && is_array($priceRanges)) {
            $response['priceRanges'] = $priceRanges;
        }

        // Include feature toggles
        $response['features'] = [
            'enableAiSearch' => $client->ai_search_enabled ?? false,
            'enableMapView' => $widgetConfig['enableMapView'] ?? true,
            'enableCurrencyConverter' => $widgetConfig['enableCurrencyConverter'] ?? true,
            'enableWishlist' => $widgetConfig['enableWishlist'] ?? true,
        ];

        // Include currency settings (always send with defaults)
        $response['baseCurrency'] = $widgetConfig['baseCurrency'] ?? 'EUR';
        $response['availableCurrencies'] = $widgetConfig['availableCurrencies'] ?? ['EUR', 'GBP', 'USD'];

        // Include display settings (always send with defaults)
        $response['defaultView'] = $widgetConfig['defaultView'] ?? 'grid';
        $response['perPage'] = $widgetConfig['perPage'] ?? 24;

        // Include customization options
        if (!empty($widgetConfig['wishlistIcon']) && $widgetConfig['wishlistIcon'] !== 'heart') {
            $response['wishlistIcon'] = $widgetConfig['wishlistIcon'];
        }
        if (!empty($widgetConfig['recaptchaSiteKey'])) {
            $response['recaptchaSiteKey'] = $widgetConfig['recaptchaSiteKey'];
        }

        // Include branding if set
        if (!empty($widgetConfig['branding'])) {
            $response['branding'] = $widgetConfig['branding'];
        }

        return response()->json($response);
    }
}
