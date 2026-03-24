<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientCustomPropertyTypeGroup;
use App\Models\ClientPropertyTypeMapping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PropertyTypeGroupingService
{
    /**
     * Get all property types from the API, formatted for admin UI.
     */
    public function getPropertyTypesForAdmin(Client $client): array
    {
        $types = $this->fetchPropertyTypesFromApi($client);

        return collect($types)->map(function ($type) {
            return [
                'id' => (string) $type['id'],
                'name' => $type['name'],
                'parent_id' => $type['parent_id'] ?? null,
                'full_name' => $type['name'],
            ];
        })->toArray();
    }

    /**
     * Get property types that haven't been mapped to any custom group.
     */
    public function getUnmappedPropertyTypes(Client $client): array
    {
        $allTypes = $this->getPropertyTypesForAdmin($client);

        // Get already mapped type IDs
        $mappedIds = ClientPropertyTypeMapping::where('client_id', $client->id)
            ->pluck('feed_type_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        // Filter out mapped types
        return collect($allTypes)
            ->filter(fn($type) => !in_array((string) $type['id'], $mappedIds))
            ->values()
            ->toArray();
    }

    /**
     * Get property types that can serve as parents for custom groups.
     * Typically parent categories (those with children).
     */
    public function getParentableTypes(Client $client): array
    {
        $types = $this->getPropertyTypesForAdmin($client);

        // Find types that have children (i.e., are parent types)
        $parentIds = collect($types)
            ->filter(fn($t) => !empty($t['parent_id']))
            ->pluck('parent_id')
            ->unique()
            ->map(fn($id) => (string) $id)
            ->toArray();

        // Return types that are parents OR top-level types
        return collect($types)
            ->filter(fn($t) => in_array((string) $t['id'], $parentIds) || empty($t['parent_id']))
            ->values()
            ->toArray();
    }

    /**
     * Get merged property types (custom groups + feed types with preferences).
     * @param Client $client The client
     * @param string $lang Language code for translations (e.g., 'it_IT', 'es_ES')
     */
    public function getMergedPropertyTypes(Client $client, string $lang = 'en_US'): array
    {
        if (!$client->custom_property_type_grouping_enabled) {
            return [];
        }

        // Get all feed types with language translations FIRST
        $feedTypes = $this->fetchPropertyTypesFromApi($client, $lang);

        // Build lookup map: id -> translated name
        $feedTypesMap = [];
        foreach ($feedTypes as $type) {
            $feedTypesMap[(string) $type['id']] = $type['name'];
        }

        // Build custom group tree with translated names
        $customTree = ClientCustomPropertyTypeGroup::buildTree($client->id, $feedTypesMap);

        if (empty($customTree)) {
            return [];
        }

        // Get mapped type IDs
        $mappedIds = ClientPropertyTypeMapping::where('client_id', $client->id)
            ->pluck('feed_type_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        // Filter feed types to only unmapped ones
        $unmappedTypes = collect($feedTypes)
            ->filter(fn($t) => !in_array((string) $t['id'], $mappedIds))
            ->values()
            ->toArray();

        // Merge: custom groups first, then unmapped types
        return array_merge($customTree, $unmappedTypes);
    }

    /**
     * Fetch property types from the client's API.
     * @param Client $client The client
     * @param string $lang Language code for translations
     */
    protected function fetchPropertyTypesFromApi(Client $client, string $lang = 'en_US'): array
    {
        $endpoints = ['/v1/property_types', '/v1/property-types'];
        $apiUrl = null;
        $headers = ['Accept' => 'application/json'];

        if ($client->resales_client_id && $client->resales_api_key) {
            $apiUrl = 'https://api.smartpropertywidget.com';
            $queryParam = '?_domain=' . urlencode($client->domain) . '&_lang=' . urlencode($lang);
        } elseif ($client->api_url && $client->api_key) {
            $apiUrl = rtrim($client->api_url, '/');
            $headers['access_token'] = $client->api_key;
            // CRM uses 'ln' parameter for language (not 'lang')
            $queryParam = '?ln=' . urlencode($lang);
        } else {
            return [];
        }

        foreach ($endpoints as $endpoint) {
            try {
                $url = $apiUrl . $endpoint . $queryParam;
                $response = Http::withoutVerifying()
                    ->connectTimeout(10)
                    ->timeout(60)
                    ->withHeaders($headers)
                    ->get($url);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['data']) && is_array($data['data'])) {
                        return $data['data'];
                    } elseif (is_array($data) && !empty($data) && isset($data[0])) {
                        return $data;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch property types: " . $e->getMessage());
            }
        }

        return [];
    }
}
