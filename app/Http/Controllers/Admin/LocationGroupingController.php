<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCustomLocationGroup;
use App\Models\ClientLocationMapping;
use App\Services\LocationGroupingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationGroupingController extends Controller
{
    public function __construct(
        protected LocationGroupingService $groupingService
    ) {}

    /**
     * Show the location grouping management page.
     */
    public function index(Client $client): View
    {
        $groups = ClientCustomLocationGroup::where('client_id', $client->id)
            ->whereNull('parent_group_id')
            ->orderBy('sort_order')
            ->with(['children' => fn($q) => $q->orderBy('sort_order'), 'mappings'])
            ->get();

        $unmappedLocations = $this->groupingService->getUnmappedLocations($client);
        $allLocations = $this->groupingService->getLocationsForAdmin($client);
        $parentableLocations = $this->groupingService->getParentableLocations($client);

        return view('admin.widget-clients.location-grouping', [
            'client' => $client,
            'groups' => $groups,
            'unmappedLocations' => $unmappedLocations,
            'allLocations' => $allLocations,
            'parentableLocations' => $parentableLocations,
        ]);
    }

    /**
     * Toggle the custom location grouping feature.
     */
    public function toggleFeature(Client $client): RedirectResponse
    {
        $client->update([
            'custom_location_grouping_enabled' => !$client->custom_location_grouping_enabled,
        ]);

        $status = $client->custom_location_grouping_enabled ? 'enabled' : 'disabled';

        return back()->with('success', "Custom location grouping {$status}.");
    }

    /**
     * Create a new custom group.
     */
    public function storeGroup(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_group_id' => 'nullable|exists:client_custom_location_groups,id',
            'parent_feed_location_id' => 'nullable|string|max:50',
            'parent_feed_location_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Verify parent belongs to this client
        if ($validated['parent_group_id']) {
            $parent = ClientCustomLocationGroup::find($validated['parent_group_id']);
            if (!$parent || $parent->client_id !== $client->id) {
                return back()->withErrors(['parent_group_id' => 'Invalid parent group.']);
            }
        }

        // Get max sort order
        $maxOrder = ClientCustomLocationGroup::where('client_id', $client->id)
            ->where('parent_group_id', $validated['parent_group_id'] ?? null)
            ->max('sort_order') ?? -1;

        ClientCustomLocationGroup::create([
            'client_id' => $client->id,
            'name' => $validated['name'],
            'parent_group_id' => $validated['parent_group_id'] ?? null,
            'parent_feed_location_id' => $validated['parent_feed_location_id'] ?? null,
            'parent_feed_location_name' => $validated['parent_feed_location_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'Group created successfully.');
    }

    /**
     * Update an existing group.
     */
    public function updateGroup(Request $request, Client $client, ClientCustomLocationGroup $group): RedirectResponse
    {
        if ($group->client_id !== $client->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_group_id' => 'nullable|exists:client_custom_location_groups,id',
            'parent_feed_location_id' => 'nullable|string|max:50',
            'parent_feed_location_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        // Prevent setting self as parent
        if (($validated['parent_group_id'] ?? null) == $group->id) {
            return back()->withErrors(['parent_group_id' => 'A group cannot be its own parent.']);
        }

        // Prevent circular reference
        if ($validated['parent_group_id']) {
            $newParent = ClientCustomLocationGroup::find($validated['parent_group_id']);
            if ($newParent && ($newParent->client_id !== $client->id || $newParent->isDescendantOf($group->id))) {
                return back()->withErrors(['parent_group_id' => 'Invalid parent group.']);
            }
        }

        $group->update([
            'name' => $validated['name'],
            'parent_group_id' => $validated['parent_group_id'] ?? null,
            'parent_feed_location_id' => $validated['parent_feed_location_id'] ?: null,
            'parent_feed_location_name' => $validated['parent_feed_location_name'] ?: null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Group updated successfully.');
    }

    /**
     * Delete a group and its mappings.
     */
    public function destroyGroup(Client $client, ClientCustomLocationGroup $group): RedirectResponse
    {
        if ($group->client_id !== $client->id) {
            abort(404);
        }

        $group->delete();

        return back()->with('success', 'Group deleted successfully.');
    }

    /**
     * Reorder groups via AJAX.
     */
    public function reorderGroups(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|integer|exists:client_custom_location_groups,id',
            'order.*.sort_order' => 'required|integer|min:0',
            'order.*.parent_group_id' => 'nullable|integer|exists:client_custom_location_groups,id',
        ]);

        foreach ($validated['order'] as $item) {
            $group = ClientCustomLocationGroup::find($item['id']);
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
     * Map locations to a group.
     */
    public function mapLocations(Request $request, Client $client, ClientCustomLocationGroup $group): RedirectResponse
    {
        if ($group->client_id !== $client->id) {
            abort(404);
        }

        $validated = $request->validate([
            'locations' => 'required|array|min:1',
            'locations.*.id' => 'required|string|max:50',
            'locations.*.name' => 'nullable|string|max:255',
            'locations.*.type' => 'nullable|string|max:20',
        ]);

        $maxOrder = ClientLocationMapping::where('custom_group_id', $group->id)->max('sort_order') ?? -1;

        foreach ($validated['locations'] as $loc) {
            // Remove existing mapping if any
            ClientLocationMapping::where('client_id', $client->id)
                ->where('feed_location_id', $loc['id'])
                ->delete();

            // Create new mapping
            ClientLocationMapping::create([
                'client_id' => $client->id,
                'custom_group_id' => $group->id,
                'feed_location_id' => $loc['id'],
                'feed_location_name' => $loc['name'] ?? null,
                'feed_location_type' => $loc['type'] ?? null,
                'sort_order' => ++$maxOrder,
            ]);
        }

        $count = count($validated['locations']);
        return back()->with('success', "{$count} location(s) mapped to {$group->name}.");
    }

    /**
     * Remove a location mapping.
     */
    public function unmapLocation(Client $client, ClientLocationMapping $mapping): RedirectResponse
    {
        if ($mapping->client_id !== $client->id) {
            abort(404);
        }

        $mapping->delete();

        return back()->with('success', 'Location unmapped.');
    }

    /**
     * Reorder mappings within a group.
     */
    public function reorderMappings(Request $request, Client $client, ClientCustomLocationGroup $group): JsonResponse
    {
        if ($group->client_id !== $client->id) {
            abort(404);
        }

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:client_location_mappings,id',
        ]);

        foreach ($validated['order'] as $index => $mappingId) {
            ClientLocationMapping::where('id', $mappingId)
                ->where('custom_group_id', $group->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get unmapped locations as JSON (for AJAX refresh).
     */
    public function getUnmapped(Client $client): JsonResponse
    {
        $unmapped = $this->groupingService->getUnmappedLocations($client);

        return response()->json(['locations' => $unmapped]);
    }
}
