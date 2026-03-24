<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCustomFeatureGroup;
use App\Models\ClientFeatureMapping;
use App\Services\FeatureGroupingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeatureGroupingController extends Controller
{
    public function __construct(
        protected FeatureGroupingService $groupingService
    ) {}

    /**
     * Show the feature grouping management page.
     */
    public function index(Client $client): View
    {
        $groups = ClientCustomFeatureGroup::where('client_id', $client->id)
            ->whereNull('parent_group_id')
            ->orderBy('sort_order')
            ->with(['children' => fn($q) => $q->orderBy('sort_order'), 'mappings'])
            ->get();

        $unmappedFeatures = $this->groupingService->getUnmappedFeatures($client);
        $allFeatures = $this->groupingService->getFeaturesForAdmin($client);
        $parentableFeatures = $this->groupingService->getParentableFeatures($client);

        return view('admin.widget-clients.feature-grouping', [
            'client' => $client,
            'groups' => $groups,
            'unmappedFeatures' => $unmappedFeatures,
            'allFeatures' => $allFeatures,
            'parentableFeatures' => $parentableFeatures,
        ]);
    }

    /**
     * Toggle the custom feature grouping feature.
     */
    public function toggleFeature(Client $client): RedirectResponse
    {
        $client->update([
            'custom_feature_grouping_enabled' => !$client->custom_feature_grouping_enabled,
        ]);

        $status = $client->custom_feature_grouping_enabled ? 'enabled' : 'disabled';

        return back()->with('success', "Custom feature grouping {$status}.");
    }

    /**
     * Create a new custom group.
     */
    public function storeGroup(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_group_id' => 'nullable|exists:client_custom_feature_groups,id',
            'parent_feed_feature_id' => 'nullable|string|max:50',
            'parent_feed_feature_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Verify parent belongs to this client
        if ($validated['parent_group_id']) {
            $parent = ClientCustomFeatureGroup::find($validated['parent_group_id']);
            if (!$parent || $parent->client_id !== $client->id) {
                return back()->withErrors(['parent_group_id' => 'Invalid parent group.']);
            }
        }

        // Get max sort order
        $maxOrder = ClientCustomFeatureGroup::where('client_id', $client->id)
            ->where('parent_group_id', $validated['parent_group_id'] ?? null)
            ->max('sort_order') ?? -1;

        ClientCustomFeatureGroup::create([
            'client_id' => $client->id,
            'name' => $validated['name'],
            'parent_group_id' => $validated['parent_group_id'] ?? null,
            'parent_feed_feature_id' => $validated['parent_feed_feature_id'] ?? null,
            'parent_feed_feature_name' => $validated['parent_feed_feature_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'Feature group created successfully.');
    }

    /**
     * Update an existing group.
     */
    public function updateGroup(Request $request, Client $client, ClientCustomFeatureGroup $group): RedirectResponse
    {
        if ($group->client_id !== $client->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_group_id' => 'nullable|exists:client_custom_feature_groups,id',
            'parent_feed_feature_id' => 'nullable|string|max:50',
            'parent_feed_feature_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        // Prevent setting self as parent
        if (($validated['parent_group_id'] ?? null) == $group->id) {
            return back()->withErrors(['parent_group_id' => 'A group cannot be its own parent.']);
        }

        // Prevent circular reference
        if ($validated['parent_group_id']) {
            $newParent = ClientCustomFeatureGroup::find($validated['parent_group_id']);
            if ($newParent && ($newParent->client_id !== $client->id || $newParent->isDescendantOf($group->id))) {
                return back()->withErrors(['parent_group_id' => 'Invalid parent group.']);
            }
        }

        $group->update([
            'name' => $validated['name'],
            'parent_group_id' => $validated['parent_group_id'] ?? null,
            'parent_feed_feature_id' => $validated['parent_feed_feature_id'] ?: null,
            'parent_feed_feature_name' => $validated['parent_feed_feature_name'] ?: null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Group updated successfully.');
    }

    /**
     * Delete a group and its mappings.
     */
    public function destroyGroup(Client $client, ClientCustomFeatureGroup $group): RedirectResponse
    {
        if ($group->client_id !== $client->id) {
            abort(404);
        }

        $group->delete();

        return back()->with('success', 'Group deleted successfully.');
    }

    /**
     * Map features to a group.
     */
    public function mapFeatures(Request $request, Client $client, ClientCustomFeatureGroup $group): RedirectResponse
    {
        if ($group->client_id !== $client->id) {
            abort(404);
        }

        $validated = $request->validate([
            'features' => 'required|array|min:1',
            'features.*.id' => 'required|string|max:50',
            'features.*.name' => 'nullable|string|max:255',
        ]);

        $maxOrder = ClientFeatureMapping::where('custom_group_id', $group->id)->max('sort_order') ?? -1;

        foreach ($validated['features'] as $feature) {
            // Remove existing mapping if any
            ClientFeatureMapping::where('client_id', $client->id)
                ->where('feed_feature_id', $feature['id'])
                ->delete();

            // Create new mapping
            ClientFeatureMapping::create([
                'client_id' => $client->id,
                'custom_group_id' => $group->id,
                'feed_feature_id' => $feature['id'],
                'feed_feature_name' => $feature['name'] ?? null,
                'sort_order' => ++$maxOrder,
            ]);
        }

        $count = count($validated['features']);
        return back()->with('success', "{$count} feature(s) mapped to {$group->name}.");
    }

    /**
     * Remove a feature mapping.
     */
    public function unmapFeature(Client $client, ClientFeatureMapping $mapping): RedirectResponse
    {
        if ($mapping->client_id !== $client->id) {
            abort(404);
        }

        $mapping->delete();

        return back()->with('success', 'Feature unmapped.');
    }

    /**
     * Get unmapped features as JSON.
     */
    public function getUnmapped(Client $client): JsonResponse
    {
        $unmapped = $this->groupingService->getUnmappedFeatures($client);

        return response()->json(['features' => $unmapped]);
    }

    /**
     * Reorder groups via AJAX.
     */
    public function reorderGroups(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|integer|exists:client_custom_feature_groups,id',
            'order.*.sort_order' => 'required|integer|min:0',
            'order.*.parent_group_id' => 'nullable|integer|exists:client_custom_feature_groups,id',
        ]);

        foreach ($validated['order'] as $item) {
            $group = ClientCustomFeatureGroup::find($item['id']);
            if ($group && $group->client_id === $client->id) {
                $group->update([
                    'sort_order' => $item['sort_order'],
                    'parent_group_id' => $item['parent_group_id'] ?? null,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Reorder mappings within a group.
     */
    public function reorderMappings(Request $request, Client $client, ClientCustomFeatureGroup $group): JsonResponse
    {
        if ($group->client_id !== $client->id) {
            abort(404);
        }

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:client_feature_mappings,id',
        ]);

        foreach ($validated['order'] as $index => $mappingId) {
            ClientFeatureMapping::where('id', $mappingId)
                ->where('custom_group_id', $group->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
