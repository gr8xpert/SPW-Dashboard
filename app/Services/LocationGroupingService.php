<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientCustomLocationGroup;
use App\Models\ClientDisplayPreference;
use App\Models\ClientLocationMapping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationGroupingService
{
    /**
     * Request-level cache for feed locations to avoid multiple API calls.
     */
    protected array $feedLocationCache = [];

    public function __construct(
        protected WidgetSubscriptionService $subscriptionService
    ) {}

    /**
     * Get merged locations: custom groups nested under their parent feed locations.
     * Returns a FLAT array with parent_id references (widget expects this structure).
     * @param Client $client The client
     * @param string $lang Language code for translations (e.g., 'it_IT', 'es_ES')
     */
    public function getMergedLocations(Client $client, string $lang = 'en_US'): array
    {
        if (!$client->custom_location_grouping_enabled) {
            return [];
        }

        // Get custom groups with mappings
        $customGroups = ClientCustomLocationGroup::where('client_id', $client->id)
            ->whereNull('parent_group_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['mappings', 'children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order')->with('mappings');
            }])
            ->get();

        if ($customGroups->isEmpty()) {
            return [];
        }

        // Get all mapped location IDs
        $mappedIds = ClientLocationMapping::where('client_id', $client->id)
            ->pluck('feed_location_id')
            ->toArray();

        // Fetch feed locations with language translations
        $feedLocations = $this->fetchFeedLocations($client, $lang);

        if ($feedLocations === null) {
            // API failed, return custom groups only (as root items)
            return $this->buildCustomGroupItemsFlat($customGroups);
        }

        // Get display preferences for sort ordering
        $preferences = ClientDisplayPreference::where('client_id', $client->id)
            ->where('item_type', 'location')
            ->get()
            ->keyBy('item_id');

        // Group custom groups by their parent feed location
        $rootCustomGroups = $customGroups->whereNull('parent_feed_location_id');
        $nestedCustomGroups = $customGroups->whereNotNull('parent_feed_location_id')
            ->groupBy('parent_feed_location_id');

        // Build the merged FLAT list (widget expects flat array with parent_id)
        $result = $this->buildMergedFlat(
            $feedLocations,
            $mappedIds,
            $preferences,
            $rootCustomGroups,
            $nestedCustomGroups
        );

        return $result;
    }

    /**
     * Build merged FLAT list with parent_id references.
     * Widget expects flat array and builds hierarchy from parent_id.
     */
    protected function buildMergedFlat(
        array $feedLocations,
        array $mappedIds,
        $preferences,
        $rootCustomGroups,
        $nestedCustomGroups,
        $parentId = null
    ): array {
        $result = [];

        // Flatten feed locations first
        $flatFeed = $this->flattenFeedLocations($feedLocations, $mappedIds, $preferences);

        // Add root-level custom groups (parent_id = null for top level - widget expects null/0/undefined)
        foreach ($rootCustomGroups as $group) {
            $itemId = 'custom_group_' . $group->id;
            $pref = $preferences->get($itemId);

            if ($pref && !$pref->visible) {
                continue;
            }

            // Add the custom group itself (type=municipality so widget shows it in parent dropdown)
            // Widget filters parents by 'municipality' type by default
            $result[] = [
                'id' => $itemId,
                'group_id' => $group->id,
                'name' => $pref && $pref->custom_name ? $pref->custom_name : $group->name,
                'slug' => $group->slug,
                'type' => 'municipality', // Use 'municipality' so widget shows it in parent dropdown
                'parent_id' => null,
                'is_custom' => true,
                'property_count' => $group->mappings->count(),
            ];

            // Add mapped locations as children of this group
            // Always use type 'city' so widget shows them in child dropdown (default childType)
            foreach ($group->mappings as $mapping) {
                $result[] = [
                    'id' => $mapping->feed_location_id,
                    'name' => $mapping->feed_location_name,
                    'type' => 'city', // Force 'city' type so widget shows in child dropdown
                    'parent_id' => $itemId,
                    'is_custom' => false,
                    'property_count' => 1, // Assume has properties since it was mapped
                ];
            }

            // Add nested child groups
            if ($group->children) {
                foreach ($group->children as $childGroup) {
                    $childItemId = 'custom_group_' . $childGroup->id;
                    $result[] = [
                        'id' => $childItemId,
                        'group_id' => $childGroup->id,
                        'name' => $childGroup->name,
                        'type' => 'municipality', // Use recognized type for widget
                        'parent_id' => $itemId,
                        'is_custom' => true,
                        'property_count' => $childGroup->mappings->count(),
                    ];

                    foreach ($childGroup->mappings as $mapping) {
                        $result[] = [
                            'id' => $mapping->feed_location_id,
                            'name' => $mapping->feed_location_name,
                            'type' => 'city', // Force 'city' type so widget shows in child dropdown
                            'parent_id' => $childItemId,
                            'is_custom' => false,
                            'property_count' => 1,
                        ];
                    }
                }
            }
        }

        // Add custom groups that are nested under feed locations
        foreach ($nestedCustomGroups as $parentFeedId => $groups) {
            foreach ($groups as $group) {
                $itemId = 'custom_group_' . $group->id;
                $pref = $preferences->get($itemId);

                if ($pref && !$pref->visible) {
                    continue;
                }

                // Add the custom group with parent_id pointing to feed location
                // Use type='municipality' so widget recognizes it as a parent-level location
                $result[] = [
                    'id' => $itemId,
                    'group_id' => $group->id,
                    'name' => $pref && $pref->custom_name ? $pref->custom_name : $group->name,
                    'slug' => $group->slug,
                    'type' => 'municipality',
                    'parent_id' => (int) $parentFeedId,
                    'is_custom' => true,
                    'property_count' => $group->mappings->count(),
                ];

                // Add mapped locations as children
                // Always use type 'city' so widget shows them in child dropdown
                foreach ($group->mappings as $mapping) {
                    $result[] = [
                        'id' => $mapping->feed_location_id,
                        'name' => $mapping->feed_location_name,
                        'type' => 'city', // Force 'city' type so widget shows in child dropdown
                        'parent_id' => $itemId,
                        'is_custom' => false,
                        'property_count' => 1,
                    ];
                }
            }
        }

        // Merge with feed locations (unmapped ones)
        $result = array_merge($result, $flatFeed);

        return $result;
    }

    /**
     * Flatten feed locations to array with parent_id references.
     */
    protected function flattenFeedLocations(array $locations, array $mappedIds, $preferences, $parentId = null): array
    {
        $result = [];

        foreach ($locations as $location) {
            $id = (string) ($location['id'] ?? '');

            // Skip mapped locations (they're inside custom groups)
            if (in_array($id, $mappedIds)) {
                continue;
            }

            $pref = $preferences->get($id);

            // Skip hidden locations
            if ($pref && !$pref->visible) {
                continue;
            }

            // Preserve original parent_id if it exists, otherwise use passed parentId
            // Feed may have flat structure with parent_id OR nested children array
            // Convert false to null (widget expects null/0/undefined for root items, not false)
            $originalParentId = $location['parent_id'] ?? null;
            if ($originalParentId === false) {
                $originalParentId = null;
            }
            $effectiveParentId = $parentId !== null ? $parentId : $originalParentId;

            $item = [
                'id' => $location['id'] ?? $id,
                'name' => $pref && $pref->custom_name ? $pref->custom_name : ($location['name'] ?? ''),
                'type' => $location['type'] ?? 'location',
                'parent_id' => $effectiveParentId,
                'is_custom' => false,
                'property_count' => $location['property_count'] ?? 1,
                'zipcode' => $location['zipcode'] ?? '',
            ];

            // Copy other fields
            if (isset($location['coordinates'])) {
                $item['coordinates'] = $location['coordinates'];
            }
            if (isset($location['counts'])) {
                $item['counts'] = $location['counts'];
            }

            $result[] = $item;

            // Recursively process children
            if (isset($location['children']) && is_array($location['children'])) {
                $children = $this->flattenFeedLocations(
                    $location['children'],
                    $mappedIds,
                    $preferences,
                    $location['id'] ?? $id
                );
                $result = array_merge($result, $children);
            }
        }

        return $result;
    }

    /**
     * Build custom group items as flat array for standalone use.
     */
    protected function buildCustomGroupItemsFlat($groups): array
    {
        $result = [];
        foreach ($groups as $group) {
            $itemId = 'custom_group_' . $group->id;
            $result[] = [
                'id' => $itemId,
                'group_id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
                'type' => 'municipality', // Use 'municipality' so widget shows it in parent dropdown
                'parent_id' => null,
                'is_custom' => true,
                'property_count' => $group->mappings->count(),
            ];

            foreach ($group->mappings as $mapping) {
                $result[] = [
                    'id' => $mapping->feed_location_id,
                    'name' => $mapping->feed_location_name,
                    'type' => 'city', // Force 'city' type so widget shows in child dropdown
                    'parent_id' => $itemId,
                    'is_custom' => false,
                    'property_count' => 1,
                ];
            }
        }
        return $result;
    }

    /**
     * Build merged tree with custom groups nested under feed locations.
     * @deprecated Use buildMergedFlat instead
     */
    protected function buildMergedTree(
        array $feedLocations,
        array $mappedIds,
        $preferences,
        $rootCustomGroups,
        $nestedCustomGroups,
        int $defaultOrder = 0
    ): array {
        $result = [];

        // First add root-level custom groups (with negative sort order to appear at top by default)
        foreach ($rootCustomGroups as $group) {
            $itemId = 'custom_group_' . $group->id;
            $pref = $preferences->get($itemId);

            if ($pref && !$pref->visible) {
                continue;
            }

            $result[] = $this->buildCustomGroupItem($group, $pref);
        }

        // Then process feed locations
        foreach ($feedLocations as $index => $location) {
            $id = (string) ($location['id'] ?? '');

            // Skip mapped locations (they're inside custom groups)
            if (in_array($id, $mappedIds)) {
                continue;
            }

            $pref = $preferences->get($id);

            // Skip hidden locations
            if ($pref && !$pref->visible) {
                continue;
            }

            $item = $location;
            $item['is_custom'] = false;
            $item['sort_order'] = $pref ? $pref->sort_order : ($defaultOrder + $index);

            // Apply custom name if set
            if ($pref && $pref->custom_name) {
                $item['name'] = $pref->custom_name;
            }

            // Check if any custom groups should be nested under this location
            $childCustomGroups = $nestedCustomGroups->get($id, collect());

            // Build children array
            $children = [];

            // Add custom groups nested under this location
            foreach ($childCustomGroups as $group) {
                $groupItemId = 'custom_group_' . $group->id;
                $groupPref = $preferences->get($groupItemId);

                if ($groupPref && !$groupPref->visible) {
                    continue;
                }

                $children[] = $this->buildCustomGroupItem($group, $groupPref);
            }

            // Add regular children (recursively)
            if (isset($location['children']) && is_array($location['children'])) {
                $regularChildren = $this->buildMergedTree(
                    $location['children'],
                    $mappedIds,
                    $preferences,
                    collect(), // No root custom groups at nested levels
                    $nestedCustomGroups,
                    ($item['sort_order'] ?? 0) * 1000
                );
                $children = array_merge($children, $regularChildren);
            }

            // Sort children by sort_order
            usort($children, fn($a, $b) => ($a['sort_order'] ?? 9999) <=> ($b['sort_order'] ?? 9999));

            // Remove sort_order from children
            $children = array_map(function ($child) {
                unset($child['sort_order']);
                return $child;
            }, $children);

            if (!empty($children)) {
                $item['children'] = $children;
            }

            $result[] = $item;
        }

        // Sort by sort_order
        usort($result, fn($a, $b) => ($a['sort_order'] ?? 9999) <=> ($b['sort_order'] ?? 9999));

        // Remove sort_order from output
        return array_map(function ($item) {
            unset($item['sort_order']);
            return $item;
        }, $result);
    }

    /**
     * Build a custom group item for the API response.
     */
    /**
     * @deprecated Use buildMergedFlat instead - this method returns nested structure
     */
    protected function buildCustomGroupItem($group, $pref = null): array
    {
        $item = [
            'id' => 'custom_group_' . $group->id,
            'group_id' => $group->id,
            'name' => $pref && $pref->custom_name ? $pref->custom_name : $group->name,
            'slug' => $group->slug,
            'type' => 'municipality', // Use municipality so widget recognizes as parent
            'is_custom' => true,
            'sort_order' => $pref ? $pref->sort_order : -1000 + $group->sort_order,
            'children' => [],
        ];

        // Add mapped locations
        foreach ($group->mappings as $mapping) {
            $item['children'][] = [
                'id' => $mapping->feed_location_id,
                'name' => $mapping->feed_location_name,
                'type' => 'city', // Force city so widget shows as child
                'is_custom' => false,
            ];
        }

        // Add nested child groups
        if ($group->children) {
            foreach ($group->children as $childGroup) {
                $childItem = [
                    'id' => 'custom_group_' . $childGroup->id,
                    'group_id' => $childGroup->id,
                    'name' => $childGroup->name,
                    'type' => 'municipality', // Use municipality for nested groups too
                    'is_custom' => true,
                    'children' => [],
                ];

                foreach ($childGroup->mappings as $mapping) {
                    $childItem['children'][] = [
                        'id' => $mapping->feed_location_id,
                        'name' => $mapping->feed_location_name,
                        'type' => 'city', // Force city so widget shows as child
                        'is_custom' => false,
                    ];
                }

                $item['children'][] = $childItem;
            }
        }

        return $item;
    }

    /**
     * Build custom group items for standalone use.
     */
    protected function buildCustomGroupItems($groups): array
    {
        $result = [];
        foreach ($groups as $group) {
            $result[] = $this->buildCustomGroupItem($group);
        }
        return $result;
    }

    /**
     * Fetch locations from the external API.
     * Uses request-level caching to avoid multiple slow API calls.
     * @param Client $client The client
     * @param string $lang Language code for translations
     */
    public function fetchFeedLocations(Client $client, string $lang = 'en_US'): ?array
    {
        // Check request-level cache first (avoids 3x API calls on admin page)
        $cacheKey = $client->id . '_' . $lang;
        if (isset($this->feedLocationCache[$cacheKey])) {
            return $this->feedLocationCache[$cacheKey];
        }

        $apiUrl = null;
        $headers = ['Accept' => 'application/json'];
        $queryParam = '';

        if ($client->resales_client_id && $client->resales_api_key) {
            $apiUrl = 'https://api.smartpropertywidget.com';
            $queryParam = '?_domain=' . urlencode($client->domain) . '&_lang=' . urlencode($lang);
        } elseif ($client->api_url && $client->api_key) {
            $apiUrl = rtrim($client->api_url, '/');
            $headers['access_token'] = $client->api_key;
            // CRM uses 'ln' parameter for language (not 'lang')
            $queryParam = '?ln=' . urlencode($lang);
        } else {
            return null;
        }

        $endpoints = ['/v2/location', '/v1/location'];

        foreach ($endpoints as $endpoint) {
            try {
                $url = $apiUrl . $endpoint . $queryParam;

                $response = Http::withoutVerifying()
                    ->connectTimeout(10)
                    ->timeout(60)
                    ->withHeaders($headers)
                    ->get($url);

                if ($response->successful()) {
                    $json = $response->json();

                    if (isset($json['data']) && is_array($json['data'])) {
                        $this->feedLocationCache[$cacheKey] = $json['data'];
                        return $json['data'];
                    } elseif (is_array($json) && !empty($json) && isset($json[0])) {
                        $this->feedLocationCache[$cacheKey] = $json;
                        return $json;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Location fetch failed for {$endpoint}: " . $e->getMessage());
            }
        }

        // Cache null result to avoid retrying on same request
        $this->feedLocationCache[$cacheKey] = null;
        return null;
    }

    /**
     * Get all feed locations with mapping status for admin UI.
     */
    public function getLocationsForAdmin(Client $client): array
    {
        $feedLocations = $this->fetchFeedLocations($client);

        if ($feedLocations === null) {
            return [];
        }

        $mappedIds = ClientLocationMapping::where('client_id', $client->id)
            ->pluck('custom_group_id', 'feed_location_id')
            ->toArray();

        return $this->flattenWithMappingStatus($feedLocations, $mappedIds);
    }

    /**
     * Flatten locations and add mapping status.
     */
    protected function flattenWithMappingStatus(array $locations, array $mappedIds, ?string $parentName = null): array
    {
        $result = [];

        foreach ($locations as $location) {
            $id = (string) ($location['id'] ?? '');
            $name = $location['name'] ?? '';
            $type = $location['type'] ?? 'location';
            $fullName = $parentName ? "{$parentName} > {$name}" : $name;

            $result[] = [
                'id' => $id,
                'name' => $name,
                'full_name' => $fullName,
                'type' => $type,
                'is_mapped' => isset($mappedIds[$id]),
                'mapped_group_id' => $mappedIds[$id] ?? null,
            ];

            // Recursively process children
            if (isset($location['children']) && is_array($location['children'])) {
                $children = $this->flattenWithMappingStatus($location['children'], $mappedIds, $fullName);
                $result = array_merge($result, $children);
            }
        }

        return $result;
    }

    /**
     * Get unmapped locations only for admin UI.
     */
    public function getUnmappedLocations(Client $client): array
    {
        $allLocations = $this->getLocationsForAdmin($client);

        return array_values(array_filter($allLocations, fn($loc) => !$loc['is_mapped']));
    }

    /**
     * Get locations suitable for parent selection (areas, municipalities).
     */
    public function getParentableLocations(Client $client): array
    {
        $allLocations = $this->getLocationsForAdmin($client);

        // Only return areas and municipalities as potential parents
        return array_values(array_filter($allLocations, fn($loc) =>
            in_array($loc['type'], ['area', 'municipality', 'region', 'province'])
        ));
    }
}
