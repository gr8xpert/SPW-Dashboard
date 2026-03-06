<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientDisplayPreference;
use App\Services\LabelService;
use Illuminate\Http\Request;

class MicroserviceController extends Controller
{
    public function __construct(
        protected LabelService $labelService
    ) {}

    /**
     * Get Resales Online credentials for a client by domain.
     * Used by the spw-transform microservice.
     *
     * GET /internal/client-resales-config?domain={domain}
     */
    public function resalesConfig(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
        ]);

        $domain = $this->normalizeDomain($request->input('domain'));

        $client = Client::where('domain', $domain)->first();

        if (!$client) {
            return response()->json([
                'error' => 'Client not found for domain',
                'domain' => $domain,
            ], 404);
        }

        if (!$client->resales_client_id || !$client->resales_api_key) {
            return response()->json([
                'error' => 'Resales credentials not configured for this client',
                'domain' => $domain,
            ], 404);
        }

        // Default resales settings structure
        $defaultSettings = [
            'resales' => ['enabled' => true, 'filter_id' => '1', 'own_filter' => null, 'min_price' => 0],
            'developments' => ['enabled' => false, 'filter_id' => '1', 'own_filter' => null, 'min_price' => 0],
            'short_rentals' => ['enabled' => false, 'filter_id' => '2', 'own_filter' => null, 'min_price' => 0],
            'long_rentals' => ['enabled' => false, 'filter_id' => '3', 'own_filter' => null, 'min_price' => 0],
        ];

        $resalesSettings = array_merge($defaultSettings, $client->resales_settings ?? []);

        return response()->json([
            'resales_client_id' => $client->resales_client_id,
            'resales_api_key' => $client->resales_api_key,
            'resales_filter_id' => $client->resales_filter_id ?? '1',
            'resales_agency_code' => $client->resales_agency_code,
            'resales_settings' => $resalesSettings,
            'default_language' => $client->default_language ?? 'en_US',
            'enabled_languages' => $client->enabled_languages ?? ['en_US'],
            'owner_email' => $client->owner_email,
            'client_id' => $client->id,
        ]);
    }

    /**
     * Get display preferences for locations, types, or features.
     * Used to control ordering and visibility of dropdown items.
     *
     * GET /internal/display-preferences?domain={domain}&type={location|property_type|feature}
     */
    public function displayPreferences(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'type' => 'required|in:location,property_type,feature',
        ]);

        $domain = $this->normalizeDomain($request->input('domain'));
        $itemType = $request->input('type');

        $client = Client::where('domain', $domain)->first();

        if (!$client) {
            return response()->json([
                'error' => 'Client not found for domain',
                'domain' => $domain,
            ], 404);
        }

        $preferences = ClientDisplayPreference::where('client_id', $client->id)
            ->where('item_type', $itemType)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($pref) {
                return [
                    'item_id' => $pref->item_id,
                    'item_name' => $pref->item_name,
                    'visible' => $pref->visible,
                    'sort_order' => $pref->sort_order,
                    'custom_name' => $pref->custom_name,
                ];
            });

        // Get hidden item IDs for quick filtering
        $hiddenIds = $preferences->where('visible', false)->pluck('item_id')->toArray();

        // Get sort order map for reordering
        $sortOrderMap = $preferences->where('sort_order', '>', 0)
            ->pluck('sort_order', 'item_id')
            ->toArray();

        // Get custom names map
        $customNames = $preferences->whereNotNull('custom_name')
            ->pluck('custom_name', 'item_id')
            ->toArray();

        return response()->json([
            'preferences' => $preferences,
            'hidden_ids' => $hiddenIds,
            'sort_order' => $sortOrderMap,
            'custom_names' => $customNames,
            'type' => $itemType,
            'client_id' => $client->id,
        ]);
    }

    /**
     * Get merged labels for a client (defaults + overrides).
     * Used by the spw-transform microservice.
     *
     * GET /internal/labels?domain={domain}&language={lang}
     */
    public function labels(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'language' => 'nullable|string|max:10',
        ]);

        $domain = $this->normalizeDomain($request->input('domain'));
        $language = $request->input('language', 'en_US');

        $client = Client::where('domain', $domain)->first();

        if (!$client) {
            return response()->json([
                'error' => 'Client not found for domain',
                'domain' => $domain,
            ], 404);
        }

        $labels = $this->labelService->getMergedLabels($client->id, $language);

        // Get enabled listing types from resales_settings
        $enabledListingTypes = $this->getEnabledListingTypes($client);

        return response()->json([
            'labels' => $labels,
            'language' => $language,
            'enabledListingTypes' => $enabledListingTypes,
            'owner_email' => $client->owner_email,
            'client_id' => $client->id,
        ]);
    }

    /**
     * Extract enabled listing types from client's resales_settings.
     * Returns array of widget listing type keys (resale, development, short_rental, long_rental).
     */
    protected function getEnabledListingTypes(Client $client): array
    {
        $resalesSettings = $client->resales_settings ?? [];
        $enabledTypes = [];

        // Map internal keys to widget keys
        $keyMap = [
            'resales' => 'resale',
            'developments' => 'development',
            'short_rentals' => 'short_rental',
            'long_rentals' => 'long_rental',
        ];

        foreach ($keyMap as $settingsKey => $widgetKey) {
            $settings = $resalesSettings[$settingsKey] ?? [];
            // Only include if explicitly enabled (default to false if not set)
            $isEnabled = $settings['enabled'] ?? false;
            if ($isEnabled) {
                $enabledTypes[] = $widgetKey;
            }
        }

        return $enabledTypes;
    }

    /**
     * Normalize a domain: strip protocol, www, and trailing slash.
     */
    protected function normalizeDomain(string $domain): string
    {
        $domain = preg_replace('#^https?://#i', '', $domain);
        $domain = preg_replace('#^www\.#i', '', $domain);
        $domain = rtrim($domain, '/');
        return strtolower($domain);
    }
}
