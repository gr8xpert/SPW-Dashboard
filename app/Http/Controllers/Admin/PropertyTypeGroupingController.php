<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCustomPropertyTypeGroup;
use App\Models\ClientPropertyTypeMapping;
use App\Services\PropertyTypeGroupingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyTypeGroupingController extends Controller
{
    public function __construct(
        protected PropertyTypeGroupingService $groupingService
    ) {}

    /**
     * Show the property type grouping management page.
     */
    public function index(Client $client): View
    {
        $groups = ClientCustomPropertyTypeGroup::where('client_id', $client->id)
            ->whereNull('parent_group_id')
            ->orderBy('sort_order')
            ->with(['children' => fn($q) => $q->orderBy('sort_order'), 'mappings'])
            ->get();

        $unmappedTypes = $this->groupingService->getUnmappedPropertyTypes($client);
        $allTypes = $this->groupingService->getPropertyTypesForAdmin($client);
        $parentableTypes = $this->groupingService->getParentableTypes($client);

        return view('admin.widget-clients.property-type-grouping', [
            'client' => $client,
            'groups' => $groups,
            'unmappedTypes' => $unmappedTypes,
            'allTypes' => $allTypes,
            'parentableTypes' => $parentableTypes,
        ]);
    }

    /**
     * Toggle the custom property type grouping feature.
     */
    public function toggleFeature(Client $client): RedirectResponse
    {
        $client->update([
            'custom_property_type_grouping_enabled' => !$client->custom_property_type_grouping_enabled,
        ]);

        $status = $client->custom_property_type_grouping_enabled ? 'enabled' : 'disabled';

        return back()->with('success', "Custom property type grouping {$status}.");
    }

    /**
     * Create a new custom group.
     */
    public function storeGroup(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_group_id' => 'nullable|exists:client_custom_property_type_groups,id',
            'parent_feed_type_id' => 'nullable|string|max:50',
            'parent_feed_type_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Verify parent belongs to this client
        if ($validated['parent_group_id']) {
            $parent = ClientCustomPropertyTypeGroup::find($validated['parent_group_id']);
            if (!$parent || $parent->client_id !== $client->id) {
                return back()->withErrors(['parent_group_id' => 'Invalid parent group.']);
            }
        }

        // Get max sort order
        $maxOrder = ClientCustomPropertyTypeGroup::where('client_id', $client->id)
            ->where('parent_group_id', $validated['parent_group_id'] ?? null)
            ->max('sort_order') ?? -1;

        ClientCustomPropertyTypeGroup::create([
            'client_id' => $client->id,
            'name' => $validated['name'],
            'parent_group_id' => $validated['parent_group_id'] ?? null,
            'parent_feed_type_id' => $validated['parent_feed_type_id'] ?? null,
            'parent_feed_type_name' => $validated['parent_feed_type_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'Property type group created successfully.');
    }

    /**
     * Update an existing group.
     */
    public function updateGroup(Request $request, Client $client, ClientCustomPropertyTypeGroup $group): RedirectResponse
    {
        if ($group->client_id !== $client->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_group_id' => 'nullable|exists:client_custom_property_type_groups,id',
            'parent_feed_type_id' => 'nullable|string|max:50',
            'parent_feed_type_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        // Prevent setting self as parent
        if (($validated['parent_group_id'] ?? null) == $group->id) {
            return back()->withErrors(['parent_group_id' => 'A group cannot be its own parent.']);
        }

        // Prevent circular reference
        if ($validated['parent_group_id']) {
            $newParent = ClientCustomPropertyTypeGroup::find($validated['parent_group_id']);
            if ($newParent && ($newParent->client_id !== $client->id || $newParent->isDescendantOf($group->id))) {
                return back()->withErrors(['parent_group_id' => 'Invalid parent group.']);
            }
        }

        $group->update([
            'name' => $validated['name'],
            'parent_group_id' => $validated['parent_group_id'] ?? null,
            'parent_feed_type_id' => $validated['parent_feed_type_id'] ?: null,
            'parent_feed_type_name' => $validated['parent_feed_type_name'] ?: null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Group updated successfully.');
    }

    /**
     * Delete a group and its mappings.
     */
    public function destroyGroup(Client $client, ClientCustomPropertyTypeGroup $group): RedirectResponse
    {
        if ($group->client_id !== $client->id) {
            abort(404);
        }

        $group->delete();

        return back()->with('success', 'Group deleted successfully.');
    }

    /**
     * Map property types to a group.
     */
    public function mapTypes(Request $request, Client $client, ClientCustomPropertyTypeGroup $group): RedirectResponse
    {
        if ($group->client_id !== $client->id) {
            abort(404);
        }

        $validated = $request->validate([
            'types' => 'required|array|min:1',
            'types.*.id' => 'required|string|max:50',
            'types.*.name' => 'nullable|string|max:255',
        ]);

        $maxOrder = ClientPropertyTypeMapping::where('custom_group_id', $group->id)->max('sort_order') ?? -1;

        foreach ($validated['types'] as $type) {
            // Remove existing mapping if any
            ClientPropertyTypeMapping::where('client_id', $client->id)
                ->where('feed_type_id', $type['id'])
                ->delete();

            // Create new mapping
            ClientPropertyTypeMapping::create([
                'client_id' => $client->id,
                'custom_group_id' => $group->id,
                'feed_type_id' => $type['id'],
                'feed_type_name' => $type['name'] ?? null,
                'sort_order' => ++$maxOrder,
            ]);
        }

        $count = count($validated['types']);
        return back()->with('success', "{$count} property type(s) mapped to {$group->name}.");
    }

    /**
     * Remove a type mapping.
     */
    public function unmapType(Client $client, ClientPropertyTypeMapping $mapping): RedirectResponse
    {
        if ($mapping->client_id !== $client->id) {
            abort(404);
        }

        $mapping->delete();

        return back()->with('success', 'Property type unmapped.');
    }

    /**
     * Get unmapped property types as JSON.
     */
    public function getUnmapped(Client $client): JsonResponse
    {
        $unmapped = $this->groupingService->getUnmappedPropertyTypes($client);

        return response()->json(['types' => $unmapped]);
    }

    /**
     * Reorder groups via AJAX.
     */
    public function reorderGroups(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|integer|exists:client_custom_property_type_groups,id',
            'order.*.sort_order' => 'required|integer|min:0',
            'order.*.parent_group_id' => 'nullable|integer|exists:client_custom_property_type_groups,id',
        ]);

        foreach ($validated['order'] as $item) {
            $group = ClientCustomPropertyTypeGroup::find($item['id']);
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
    public function reorderMappings(Request $request, Client $client, ClientCustomPropertyTypeGroup $group): JsonResponse
    {
        if ($group->client_id !== $client->id) {
            abort(404);
        }

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:client_property_type_mappings,id',
        ]);

        foreach ($validated['order'] as $index => $mappingId) {
            ClientPropertyTypeMapping::where('id', $mappingId)
                ->where('custom_group_id', $group->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
