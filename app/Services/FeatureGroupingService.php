<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientCustomFeatureGroup;
use App\Models\ClientFeatureMapping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FeatureGroupingService
{
    /**
     * Get all features from the API, formatted for admin UI.
     */
    public function getFeaturesForAdmin(Client $client): array
    {
        $features = $this->fetchFeaturesFromApi($client);

        return collect($features)->map(function ($feature) {
            return [
                'id' => (string) $feature['id'],
                'name' => $feature['name'],
                'parent_id' => $feature['parent_id'] ?? null,
                'full_name' => $feature['name'],
            ];
        })->toArray();
    }

    /**
     * Get features that haven't been mapped to any custom group.
     */
    public function getUnmappedFeatures(Client $client): array
    {
        $allFeatures = $this->getFeaturesForAdmin($client);

        // Get already mapped feature IDs
        $mappedIds = ClientFeatureMapping::where('client_id', $client->id)
            ->pluck('feed_feature_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        // Filter out mapped features
        return collect($allFeatures)
            ->filter(fn($feature) => !in_array((string) $feature['id'], $mappedIds))
            ->values()
            ->toArray();
    }

    /**
     * Get features that can serve as parents for custom groups.
     */
    public function getParentableFeatures(Client $client): array
    {
        $features = $this->getFeaturesForAdmin($client);

        // Find features that have children (i.e., are parent features/categories)
        $parentIds = collect($features)
            ->filter(fn($f) => !empty($f['parent_id']))
            ->pluck('parent_id')
            ->unique()
            ->map(fn($id) => (string) $id)
            ->toArray();

        // Return features that are parents OR top-level
        return collect($features)
            ->filter(fn($f) => in_array((string) $f['id'], $parentIds) || empty($f['parent_id']))
            ->values()
            ->toArray();
    }

    /**
     * Get merged features (custom groups + feed features with preferences).
     */
    public function getMergedFeatures(Client $client): array
    {
        if (!$client->custom_feature_grouping_enabled) {
            return [];
        }

        // Build custom group tree
        $customTree = ClientCustomFeatureGroup::buildTree($client->id);

        if (empty($customTree)) {
            return [];
        }

        // Get all feed features
        $feedFeatures = $this->fetchFeaturesFromApi($client);

        // Get mapped feature IDs
        $mappedIds = ClientFeatureMapping::where('client_id', $client->id)
            ->pluck('feed_feature_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        // Filter feed features to only unmapped ones
        $unmappedFeatures = collect($feedFeatures)
            ->filter(fn($f) => !in_array((string) $f['id'], $mappedIds))
            ->values()
            ->toArray();

        // Merge: custom groups first, then unmapped features
        return array_merge($customTree, $unmappedFeatures);
    }

    /**
     * Fetch features from the client's API.
     */
    protected function fetchFeaturesFromApi(Client $client): array
    {
        $endpoints = ['/v1/property_features', '/v1/property-features', '/v1/features'];
        $apiUrl = null;
        $headers = ['Accept' => 'application/json'];

        if ($client->resales_client_id && $client->resales_api_key) {
            $apiUrl = 'https://api.smartpropertywidget.com';
            $queryParam = '?_domain=' . urlencode($client->domain);
        } elseif ($client->api_url && $client->api_key) {
            $apiUrl = rtrim($client->api_url, '/');
            $headers['access_token'] = $client->api_key;
            $queryParam = '';
        } else {
            return [];
        }

        foreach ($endpoints as $endpoint) {
            try {
                $url = $apiUrl . $endpoint . $queryParam;
                $response = Http::withoutVerifying()
                    ->timeout(15)
                    ->withHeaders($headers)
                    ->get($url);

                if ($response->successful()) {
                    $data = $response->json();

                    // Handle nested structure (groups with values)
                    if (isset($data['data']) && is_array($data['data'])) {
                        $items = $data['data'];
                    } elseif (is_array($data) && !empty($data) && isset($data[0])) {
                        $items = $data;
                    } else {
                        continue;
                    }

                    // Flatten grouped features if needed
                    if (!empty($items) && (isset($items[0]['value_ids']) || isset($items[0]['values']))) {
                        $flatFeatures = [];
                        foreach ($items as $group) {
                            $values = $group['value_ids'] ?? $group['values'] ?? [];
                            foreach ($values as $value) {
                                $flatFeatures[] = [
                                    'id' => $value['id'],
                                    'name' => $value['name'],
                                    'parent_id' => $group['id'],
                                    'parent_name' => $group['name'] ?? null,
                                ];
                            }
                        }
                        return $flatFeatures;
                    }

                    return $items;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch features: " . $e->getMessage());
            }
        }

        return [];
    }
}
