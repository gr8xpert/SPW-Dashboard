@extends('layouts.admin')

@section('page-title', 'Location Grouping - ' . $client->domain)

@section('page-content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Location Grouping</h1>
            <p class="text-muted mb-0">{{ $client->domain ?: $client->company_name }}</p>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('admin.widget-clients.location-grouping.toggle', $client) }}">
                @csrf
                <button type="submit" class="btn btn-{{ $client->custom_location_grouping_enabled ? 'success' : 'outline-secondary' }}">
                    <i class="bi bi-{{ $client->custom_location_grouping_enabled ? 'check-circle-fill' : 'circle' }} me-1"></i>
                    {{ $client->custom_location_grouping_enabled ? 'Enabled' : 'Disabled' }}
                </button>
            </form>
            <a href="{{ route('admin.widget-clients.edit', $client) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(!$client->custom_location_grouping_enabled)
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Custom location grouping is disabled. Enable it to create custom groups and map locations.
        </div>
    @endif

    <div class="row">
        {{-- Left Panel: Custom Groups --}}
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-folder2 me-2 text-primary"></i>Custom Groups</h6>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                        <i class="bi bi-plus-lg"></i> New Group
                    </button>
                </div>
                <div class="card-body p-0">
                    @if($groups->isEmpty())
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-folder2-open display-4 d-block mb-2"></i>
                            No custom groups yet. Create one to get started.
                        </div>
                    @else
                        <div class="list-group list-group-flush" id="groups-list">
                            @foreach($groups as $group)
                                @include('admin.widget-clients._location-group-item', ['group' => $group, 'level' => 0])
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Panel: Unmapped Locations --}}
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom pt-4 pb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-geo-alt me-2 text-primary"></i>Unmapped Locations</h6>
                    <div class="mt-2">
                        <input type="text" class="form-control form-control-sm" id="location-search" placeholder="Search locations...">
                    </div>
                </div>
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    @if(empty($unmappedLocations))
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-check-circle display-4 d-block mb-2"></i>
                            All locations are mapped.
                        </div>
                    @else
                        <div class="list-group list-group-flush" id="unmapped-locations">
                            @foreach($unmappedLocations as $location)
                                <label class="list-group-item list-group-item-action d-flex align-items-center gap-2 location-item"
                                       data-name="{{ strtolower($location['full_name']) }}">
                                    <input type="checkbox" class="form-check-input location-checkbox" value="{{ $location['id'] }}"
                                           data-name="{{ $location['name'] }}" data-type="{{ $location['type'] }}">
                                    <div class="flex-grow-1">
                                        <div class="fw-medium">{{ $location['name'] }}</div>
                                        @if($location['full_name'] !== $location['name'])
                                            <small class="text-muted">{{ $location['full_name'] }}</small>
                                        @endif
                                    </div>
                                    <span class="badge bg-secondary">{{ $location['type'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if(!empty($unmappedLocations) && !$groups->isEmpty())
                    <div class="card-footer bg-light d-flex gap-2 align-items-center">
                        <select class="form-select form-select-sm" id="target-group" style="max-width: 200px;">
                            <option value="">Select group...</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @foreach($group->children as $child)
                                    <option value="{{ $child->id }}">-- {{ $child->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-primary btn-sm" id="map-selected" disabled>
                            <i class="bi bi-arrow-left"></i> Map Selected
                        </button>
                        <span class="text-muted small ms-auto"><span id="selected-count">0</span> selected</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Create Group Modal --}}
<div class="modal fade" id="createGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.widget-clients.location-grouping.groups.store', $client) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Custom Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="group-name" class="form-label">Group Name</label>
                        <input type="text" class="form-control" id="group-name" name="name" required placeholder="e.g., Marbella East">
                    </div>
                    <div class="mb-3">
                        <label for="parent-feed-location" class="form-label">Nest Under Location</label>
                        <select class="form-select" id="parent-feed-location" name="parent_feed_location_id" onchange="updateParentName(this, 'parent-feed-location-name')">
                            <option value="">None (Show at top level)</option>
                            @foreach($parentableLocations as $loc)
                                <option value="{{ $loc['id'] }}" data-name="{{ $loc['name'] }}">{{ $loc['full_name'] }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="parent-feed-location-name" name="parent_feed_location_name" value="">
                        <div class="form-text">Select a location (e.g., Costa del Sol) to nest this group under it.</div>
                    </div>
                    <div class="mb-3">
                        <label for="parent-group" class="form-label">Parent Group (Optional)</label>
                        <select class="form-select" id="parent-group" name="parent_group_id">
                            <option value="">None</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Nest under another custom group (for multi-level grouping).</div>
                    </div>
                    <div class="mb-3">
                        <label for="group-description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="group-description" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="create-group-btn">
                        <span class="btn-text">Create Group</span>
                        <span class="btn-loading d-none">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Creating...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Group Modal --}}
<div class="modal fade" id="editGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="edit-group-form">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-group-name" class="form-label">Group Name</label>
                        <input type="text" class="form-control" id="edit-group-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-parent-feed-location" class="form-label">Nest Under Location</label>
                        <select class="form-select" id="edit-parent-feed-location" name="parent_feed_location_id" onchange="updateParentName(this, 'edit-parent-feed-location-name')">
                            <option value="">None (Show at top level)</option>
                            @foreach($parentableLocations as $loc)
                                <option value="{{ $loc['id'] }}" data-name="{{ $loc['name'] }}">{{ $loc['full_name'] }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="edit-parent-feed-location-name" name="parent_feed_location_name" value="">
                        <div class="form-text">Select a location (e.g., Costa del Sol) to nest this group under it.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit-parent-group" class="form-label">Parent Group</label>
                        <select class="form-select" id="edit-parent-group" name="parent_group_id">
                            <option value="">None</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit-group-description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit-group-description" name="description" rows="2"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="edit-group-active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="edit-group-active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';
    const reorderGroupsUrl = '{{ route("admin.widget-clients.location-grouping.groups.reorder", $client) }}';
    const reorderMappingsBaseUrl = '{{ route("admin.widget-clients.location-grouping.index", $client) }}/groups';

    // Prevent double-submission on Create Group form
    const createGroupForm = document.querySelector('#createGroupModal form');
    const createGroupBtn = document.getElementById('create-group-btn');
    if (createGroupForm && createGroupBtn) {
        createGroupForm.addEventListener('submit', function(e) {
            if (createGroupBtn.disabled) {
                e.preventDefault();
                return false;
            }
            createGroupBtn.disabled = true;
            createGroupBtn.querySelector('.btn-text').classList.add('d-none');
            createGroupBtn.querySelector('.btn-loading').classList.remove('d-none');
        });
    }

    // Location search filter
    const searchInput = document.getElementById('location-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.location-item').forEach(function(item) {
                const name = item.dataset.name || '';
                item.style.display = name.includes(query) ? '' : 'none';
            });
        });
    }

    // Checkbox selection handling
    const checkboxes = document.querySelectorAll('.location-checkbox');
    const mapBtn = document.getElementById('map-selected');
    const targetGroup = document.getElementById('target-group');
    const selectedCount = document.getElementById('selected-count');

    function updateMapButton() {
        const checked = document.querySelectorAll('.location-checkbox:checked').length;
        if (selectedCount) selectedCount.textContent = checked;
        if (mapBtn) mapBtn.disabled = checked === 0 || !targetGroup.value;
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', updateMapButton);
    });

    if (targetGroup) {
        targetGroup.addEventListener('change', updateMapButton);
    }

    // Map selected locations
    if (mapBtn) {
        mapBtn.addEventListener('click', function() {
            const groupId = targetGroup.value;
            if (!groupId) return;

            const selected = [];
            document.querySelectorAll('.location-checkbox:checked').forEach(function(cb) {
                selected.push({
                    id: cb.value,
                    name: cb.dataset.name,
                    type: cb.dataset.type
                });
            });

            if (selected.length === 0) return;

            // Show loader
            if (window.PageLoader) {
                PageLoader.show('Mapping locations...');
            }

            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.widget-clients.location-grouping.groups.store", $client) }}'.replace('/groups', '/groups/' + groupId + '/map');

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            selected.forEach(function(loc, i) {
                ['id', 'name', 'type'].forEach(function(key) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'locations[' + i + '][' + key + ']';
                    input.value = loc[key] || '';
                    form.appendChild(input);
                });
            });

            document.body.appendChild(form);
            form.submit();
        });
    }

    // Update parent name hidden field when dropdown changes
    window.updateParentName = function(select, hiddenId) {
        const option = select.options[select.selectedIndex];
        const name = option ? option.dataset.name || '' : '';
        document.getElementById(hiddenId).value = name;
    };

    // Edit group modal
    window.editGroup = function(id, name, parentId, parentFeedLocationId, description, isActive) {
        document.getElementById('edit-group-form').action = '{{ route("admin.widget-clients.location-grouping.index", $client) }}/groups/' + id;
        document.getElementById('edit-group-name').value = name;
        document.getElementById('edit-parent-group').value = parentId || '';
        document.getElementById('edit-parent-feed-location').value = parentFeedLocationId || '';
        document.getElementById('edit-group-description').value = description || '';
        document.getElementById('edit-group-active').checked = isActive;

        // Update hidden name field
        updateParentName(document.getElementById('edit-parent-feed-location'), 'edit-parent-feed-location-name');

        new bootstrap.Modal(document.getElementById('editGroupModal')).show();
    };

    // ─── Group Reorder ─────────────────────────────────────────────────────────
    document.querySelectorAll('.move-group-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const groupItem = this.closest('.group-item');
            const direction = this.dataset.direction;
            const groupId = groupItem.dataset.groupId;
            const parentId = groupItem.dataset.parentId || null;

            // Get siblings (groups at the same level)
            const siblings = Array.from(document.querySelectorAll('.group-item')).filter(function(item) {
                return (item.dataset.parentId || null) === parentId;
            });

            const currentIndex = siblings.indexOf(groupItem);
            const targetIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1;

            if (targetIndex < 0 || targetIndex >= siblings.length) return;

            // Swap in DOM
            const targetItem = siblings[targetIndex];
            if (direction === 'up') {
                groupItem.parentNode.insertBefore(groupItem, targetItem);
            } else {
                groupItem.parentNode.insertBefore(targetItem, groupItem);
            }

            // Build new order array
            const newOrder = [];
            document.querySelectorAll('.group-item').forEach(function(item, index) {
                // Only include top-level groups for simplicity
                // Nested groups maintain their parent relationship
                const itemParentId = item.dataset.parentId || null;
                const sameLevel = siblings.some(s => s.dataset.groupId === item.dataset.groupId);
                if (sameLevel) {
                    newOrder.push({
                        id: parseInt(item.dataset.groupId),
                        sort_order: newOrder.length,
                        parent_group_id: itemParentId ? parseInt(itemParentId) : null
                    });
                }
            });

            // Send AJAX request
            fetch(reorderGroupsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ order: newOrder }),
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Failed to save order');
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to save order');
                location.reload();
            });
        });
    });

    // ─── Mapping Reorder ───────────────────────────────────────────────────────
    document.querySelectorAll('.move-mapping-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const mappingItem = this.closest('.mapping-item');
            const mappingsList = this.closest('.mappings-list');
            const direction = this.dataset.direction;
            const groupId = mappingsList.dataset.groupId;

            const siblings = Array.from(mappingsList.querySelectorAll('.mapping-item'));
            const currentIndex = siblings.indexOf(mappingItem);
            const targetIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1;

            if (targetIndex < 0 || targetIndex >= siblings.length) return;

            // Swap in DOM
            const targetItem = siblings[targetIndex];
            if (direction === 'up') {
                mappingItem.parentNode.insertBefore(mappingItem, targetItem);
            } else {
                mappingItem.parentNode.insertBefore(targetItem, mappingItem);
            }

            // Build new order array
            const newOrder = Array.from(mappingsList.querySelectorAll('.mapping-item')).map(function(item) {
                return parseInt(item.dataset.mappingId);
            });

            // Send AJAX request
            fetch(reorderMappingsBaseUrl + '/' + groupId + '/reorder-mappings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ order: newOrder }),
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Failed to save order');
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to save order');
                location.reload();
            });
        });
    });
});
</script>
@endsection
