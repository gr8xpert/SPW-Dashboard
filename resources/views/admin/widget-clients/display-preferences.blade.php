@extends('layouts.admin')

@section('page-title', 'Manage ' . $typeLabel . ' - ' . $client->company_name)

@push('styles')
<style>
    .tree-item { border-left: 2px solid #e2e8f0; margin-left: 1rem; padding-left: 1rem; }
    .tree-item.level-0 { border-left: none; margin-left: 0; padding-left: 0; }
    .tree-parent { background: #f8fafc; font-weight: 600; }
    .tree-parent td { border-top: 2px solid #e2e8f0 !important; }
    tr[data-type="area"] { background: #e8f4fd !important; }
    tr[data-type="municipality"] { background: #f0fdf4 !important; }
    tr[data-type="city"] { background: #ffffff; }
    tr[data-type="custom_group"] { background: #fef3c7 !important; border-left: 3px solid #f59e0b !important; }
    .move-btn { width: 28px; height: 28px; padding: 0; line-height: 1; }
    .move-btn:disabled { opacity: 0.3; }
    .item-hidden { opacity: 0.5; }
    .badge-children { font-size: 0.7rem; font-weight: normal; }
    .collapse-btn { cursor: pointer; user-select: none; }
    .collapse-btn .bi { transition: transform 0.2s; }
    .collapse-btn.collapsed .bi { transform: rotate(-90deg); }
</style>
@endpush

@section('page-content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Manage {{ $typeLabel }}</h1>
            <p class="text-muted mb-0">{{ $client->company_name }} ({{ $client->domain }})</p>
        </div>
        <a href="{{ route('admin.widget-clients.edit', $client) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Client
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Type Switcher --}}
    <div class="btn-group mb-4" role="group">
        <a href="{{ route('admin.widget-clients.display-preferences', [$client, 'type' => 'location']) }}"
           class="btn btn-{{ $type === 'location' ? 'primary' : 'outline-primary' }}">
            <i class="bi bi-geo-alt me-1"></i> Locations
        </a>
        <a href="{{ route('admin.widget-clients.display-preferences', [$client, 'type' => 'property_type']) }}"
           class="btn btn-{{ $type === 'property_type' ? 'primary' : 'outline-primary' }}">
            <i class="bi bi-house me-1"></i> Property Types
        </a>
        <a href="{{ route('admin.widget-clients.display-preferences', [$client, 'type' => 'feature']) }}"
           class="btn btn-{{ $type === 'feature' ? 'primary' : 'outline-primary' }}">
            <i class="bi bi-check2-square me-1"></i> Features
        </a>
    </div>

    @if($items->isEmpty())
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            No {{ strtolower($typeLabel) }} found. Make sure the client has valid API credentials configured (Resales API or CRM API) and the API is working.
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-bold">{{ $items->count() }} {{ $typeLabel }}</span>
                        <span class="text-muted ms-2">| {{ $items->where('visible', true)->count() }} visible</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="bulkVisibility(true)">
                            <i class="bi bi-eye"></i> Show All
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="bulkVisibility(false)">
                            <i class="bi bi-eye-slash"></i> Hide All
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="expandAll()">
                            <i class="bi bi-arrows-expand"></i> Expand All
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="collapseAll()">
                            <i class="bi bi-arrows-collapse"></i> Collapse All
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="items-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">Visible</th>
                                <th style="width: 100px;">Reorder</th>
                                <th>Name</th>
                                <th style="width: 250px;">Custom Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Group items by parent_id
                                $grouped = $items->groupBy(fn($item) => $item['parent_id'] === false ? 'root' : $item['parent_id']);
                                $rootItems = $grouped->get('root', collect());

                                // Build tree recursively
                                function renderTree($items, $grouped, $client, $type, $level = 0) {
                                    $html = '';
                                    $count = $items->count();
                                    foreach ($items->values() as $index => $item) {
                                        $itemId = $item['id'];
                                        $children = $grouped->get((string)$itemId, collect());
                                        $hasChildren = $children->isNotEmpty();
                                        $isFirst = $index === 0;
                                        $isLast = $index === $count - 1;
                                        $isHidden = !$item['visible'];
                                        $parentId = $item['parent_id'] === false ? 'root' : $item['parent_id'];

                                        $hiddenStyle = $level > 0 ? ' style="display: none;"' : '';
                                        $itemType = $item['type'] ?? 'city';
                                        $isParentItem = ($level === 0 || $itemType === 'municipality' || $hasChildren);
                                        $html .= '<tr class="tree-row ' . ($isParentItem ? 'tree-parent' : '') . ' ' . ($isHidden ? 'item-hidden' : '') . '"
                                                     data-id="' . $itemId . '"
                                                     data-parent="' . $parentId . '"
                                                     data-level="' . $level . '"
                                                     data-type="' . $itemType . '"
                                                     data-index="' . $index . '"' . $hiddenStyle . '>';

                                        // Visible checkbox
                                        $html .= '<td>
                                            <div class="form-check">
                                                <input class="form-check-input visibility-checkbox" type="checkbox"
                                                       data-id="' . $itemId . '"
                                                       ' . ($item['visible'] ? 'checked' : '') . '>
                                            </div>
                                        </td>';

                                        // Reorder buttons
                                        $html .= '<td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary move-btn"
                                                        onclick="moveItem(\'' . $itemId . '\', \'up\')"
                                                        ' . ($isFirst ? 'disabled' : '') . '
                                                        title="Move Up">
                                                    <i class="bi bi-chevron-up"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary move-btn"
                                                        onclick="moveItem(\'' . $itemId . '\', \'down\')"
                                                        ' . ($isLast ? 'disabled' : '') . '
                                                        title="Move Down">
                                                    <i class="bi bi-chevron-down"></i>
                                                </button>
                                            </div>
                                        </td>';

                                        // Name with indentation and collapse toggle
                                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                                        $html .= '<td>';
                                        if ($level > 0) {
                                            $html .= '<span class="text-muted">' . $indent . '└ </span>';
                                        }
                                        if ($hasChildren) {
                                            $html .= '<span class="collapse-btn collapsed me-1" data-target="' . $itemId . '" onclick="toggleChildren(this)">
                                                        <i class="bi bi-chevron-down"></i>
                                                      </span>';
                                        }
                                        $html .= '<span class="item-name">' . e($item['name']) . '</span>';

                                        // Type badge
                                        $typeBadges = [
                                            'custom_group' => '<span class="badge bg-warning text-dark ms-1"><i class="bi bi-folder-fill me-1"></i>Custom Group</span>',
                                            'area' => '<span class="badge bg-primary ms-1">Area</span>',
                                            'municipality' => '<span class="badge bg-success ms-1">Municipality</span>',
                                            'city' => '',
                                        ];
                                        $html .= $typeBadges[$itemType] ?? '';

                                        // Show mapped count for custom groups
                                        if ($itemType === 'custom_group' && isset($item['mapped_count'])) {
                                            $html .= ' <span class="badge bg-secondary badge-children">' . $item['mapped_count'] . ' mapped</span>';
                                        }

                                        if ($hasChildren) {
                                            $html .= ' <span class="badge bg-secondary badge-children">' . $children->count() . ' items</span>';
                                        }
                                        if ($item['has_pref']) {
                                            $html .= ' <span class="badge bg-info" title="Has custom settings"><i class="bi bi-gear-fill"></i></span>';
                                        }
                                        $html .= '</td>';

                                        // Custom name input
                                        $html .= '<td>
                                            <input type="text" class="form-control form-control-sm custom-name-input"
                                                   data-id="' . $itemId . '"
                                                   value="' . e($item['custom_name'] ?? '') . '"
                                                   placeholder="' . e($item['name']) . '">
                                        </td>';

                                        $html .= '</tr>';

                                        // Render children recursively
                                        if ($hasChildren) {
                                            $html .= renderTree($children, $grouped, $client, $type, $level + 1);
                                        }
                                    }
                                    return $html;
                                }

                                echo renderTree($rootItems, $grouped, $client, $type, 0);
                            @endphp
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        Use arrow buttons to reorder items. Hidden items won't show in widget dropdowns.
                        @if($type === 'location' && $client->custom_location_grouping_enabled)
                            <br><i class="bi bi-folder-fill text-warning me-1"></i>
                            <strong>Custom Groups</strong> (yellow rows) can be positioned anywhere in the list.
                            <a href="{{ route('admin.widget-clients.location-grouping.index', $client) }}">Manage groups</a>
                        @elseif($type === 'property_type' && $client->custom_property_type_grouping_enabled)
                            <br><i class="bi bi-folder-fill text-warning me-1"></i>
                            <strong>Custom Groups</strong> (yellow rows) can be positioned anywhere in the list.
                            <a href="{{ route('admin.widget-clients.property-type-grouping.index', $client) }}">Manage groups</a>
                        @elseif($type === 'feature' && $client->custom_feature_grouping_enabled)
                            <br><i class="bi bi-folder-fill text-warning me-1"></i>
                            <strong>Custom Groups</strong> (yellow rows) can be positioned anywhere in the list.
                            <a href="{{ route('admin.widget-clients.feature-grouping.index', $client) }}">Manage groups</a>
                        @endif
                    </div>
                    <button type="button" class="btn btn-primary" onclick="saveAllPreferences()">
                        <i class="bi bi-check-lg me-1"></i> Save All Changes
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
const clientId = {{ $client->id }};
const itemType = '{{ $type }}';
const csrfToken = '{{ csrf_token() }}';

// Move item up or down
function moveItem(itemId, direction) {
    fetch('{{ route("admin.widget-clients.move-preference", $client) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            type: itemType,
            item_id: itemId,
            direction: direction,
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Swap rows in the DOM
            const currentRow = document.querySelector(`tr[data-id="${itemId}"]`);
            const siblingId = data.swapped_with;
            const siblingRow = document.querySelector(`tr[data-id="${siblingId}"]`);

            if (currentRow && siblingRow) {
                if (direction === 'up') {
                    siblingRow.parentNode.insertBefore(currentRow, siblingRow);
                } else {
                    siblingRow.parentNode.insertBefore(siblingRow, currentRow);
                }
                updateMoveButtons();
            }
        } else {
            alert(data.message || 'Failed to move item');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to move item');
    });
}

// Update move button states after reorder
function updateMoveButtons() {
    // Group rows by parent
    const parents = {};
    document.querySelectorAll('tr.tree-row').forEach(row => {
        const parent = row.dataset.parent;
        if (!parents[parent]) parents[parent] = [];
        parents[parent].push(row);
    });

    // Update buttons for each group
    Object.values(parents).forEach(rows => {
        rows.forEach((row, index) => {
            const upBtn = row.querySelector('.move-btn:first-child');
            const downBtn = row.querySelector('.move-btn:last-child');
            if (upBtn) upBtn.disabled = index === 0;
            if (downBtn) downBtn.disabled = index === rows.length - 1;
        });
    });
}

// Toggle children visibility - only direct children, not grandchildren
function toggleChildren(btn) {
    const targetId = btn.dataset.target;
    const isCollapsed = btn.classList.toggle('collapsed');

    // Only toggle DIRECT children (not grandchildren)
    document.querySelectorAll(`tr[data-parent="${targetId}"]`).forEach(row => {
        if (isCollapsed) {
            // Collapsing: hide this row and all its descendants
            row.style.display = 'none';
            // Also collapse this row's toggle button if it has one
            const childBtn = row.querySelector('.collapse-btn');
            if (childBtn) {
                childBtn.classList.add('collapsed');
            }
            // Hide all descendants recursively
            hideAllDescendants(row.dataset.id);
        } else {
            // Expanding: show only direct children (keep grandchildren hidden)
            row.style.display = '';
            // Keep this row's children collapsed
            const childBtn = row.querySelector('.collapse-btn');
            if (childBtn) {
                childBtn.classList.add('collapsed');
            }
        }
    });
}

// Helper: hide all descendants of a parent
function hideAllDescendants(parentId) {
    document.querySelectorAll(`tr[data-parent="${parentId}"]`).forEach(row => {
        row.style.display = 'none';
        const childBtn = row.querySelector('.collapse-btn');
        if (childBtn) {
            childBtn.classList.add('collapsed');
        }
        hideAllDescendants(row.dataset.id);
    });
}

// Expand all tree nodes - show everything
function expandAll() {
    document.querySelectorAll('.collapse-btn').forEach(btn => {
        btn.classList.remove('collapsed');
    });
    document.querySelectorAll('tr.tree-row').forEach(row => {
        row.style.display = '';
    });
}

// Collapse all tree nodes - show only top level (areas)
function collapseAll() {
    document.querySelectorAll('.collapse-btn').forEach(btn => {
        btn.classList.add('collapsed');
    });
    document.querySelectorAll('tr.tree-row').forEach(row => {
        // Hide everything except level 0 (areas)
        if (parseInt(row.dataset.level) > 0) {
            row.style.display = 'none';
        }
    });
}

// Bulk visibility change
function bulkVisibility(visible) {
    document.querySelectorAll('.visibility-checkbox').forEach(cb => {
        cb.checked = visible;
        const row = cb.closest('tr');
        if (visible) {
            row.classList.remove('item-hidden');
        } else {
            row.classList.add('item-hidden');
        }
    });
}

// Toggle visibility for single item
document.querySelectorAll('.visibility-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        const row = this.closest('tr');
        if (this.checked) {
            row.classList.remove('item-hidden');
        } else {
            row.classList.add('item-hidden');
        }
    });
});

// Save all preferences
function saveAllPreferences() {
    const items = {};

    document.querySelectorAll('tr.tree-row').forEach((row, index) => {
        const id = row.dataset.id;
        const visible = row.querySelector('.visibility-checkbox').checked;
        const customName = row.querySelector('.custom-name-input').value;
        const name = row.querySelector('.item-name').textContent;

        // Calculate sort order based on position within parent group
        const parent = row.dataset.parent;
        let sortOrder = 0;
        document.querySelectorAll(`tr[data-parent="${parent}"]`).forEach((r, i) => {
            if (r.dataset.id === id) sortOrder = i;
        });

        items[id] = {
            name: name,
            visible: visible ? '1' : '0',
            sort_order: sortOrder,
            custom_name: customName || '',
        };
    });

    // Submit form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.widget-clients.save-display-preferences", $client) }}';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);

    const typeInput = document.createElement('input');
    typeInput.type = 'hidden';
    typeInput.name = 'type';
    typeInput.value = itemType;
    form.appendChild(typeInput);

    Object.entries(items).forEach(([id, data]) => {
        Object.entries(data).forEach(([key, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `items[${id}][${key}]`;
            input.value = value;
            form.appendChild(input);
        });
    });

    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
