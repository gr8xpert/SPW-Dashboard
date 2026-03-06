@php $indent = $level * 20; @endphp
<div class="list-group-item {{ !$group->is_active ? 'bg-light text-muted' : '' }}" style="padding-left: {{ 16 + $indent }}px;">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-folder{{ $group->children->isNotEmpty() || $group->mappings->isNotEmpty() ? '-fill text-warning' : ' text-secondary' }}"></i>
        <div class="flex-grow-1">
            <div class="fw-medium">
                {{ $group->name }}
                @if(!$group->is_active)
                    <span class="badge bg-secondary">Inactive</span>
                @endif
                @if($group->parent_feed_location_id)
                    <span class="badge bg-info" title="Nested under {{ $group->parent_feed_location_name }}">
                        <i class="bi bi-diagram-3"></i> {{ $group->parent_feed_location_name }}
                    </span>
                @endif
            </div>
            <small class="text-muted">{{ $group->mappings->count() }} location(s)</small>
        </div>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    onclick="editGroup({{ $group->id }}, '{{ addslashes($group->name) }}', {{ $group->parent_group_id ?? 'null' }}, '{{ $group->parent_feed_location_id ?? '' }}', '{{ addslashes($group->description ?? '') }}', {{ $group->is_active ? 'true' : 'false' }})">
                <i class="bi bi-pencil"></i>
            </button>
            <form method="POST" action="{{ route('admin.widget-clients.location-grouping.groups.destroy', [$client, $group]) }}"
                  onsubmit="return confirm('Delete this group? Mapped locations will become unmapped.')" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </div>

    {{-- Mapped Locations --}}
    @if($group->mappings->isNotEmpty())
        <div class="mt-2 ms-4">
            @foreach($group->mappings as $mapping)
                <div class="d-flex align-items-center gap-2 py-1 border-bottom">
                    <i class="bi bi-geo-alt text-primary"></i>
                    <span class="small flex-grow-1">{{ $mapping->feed_location_name ?: $mapping->feed_location_id }}</span>
                    <span class="badge bg-light text-muted">{{ $mapping->feed_location_type }}</span>
                    <form method="POST" action="{{ route('admin.widget-clients.location-grouping.mappings.destroy', [$client, $mapping]) }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-link btn-sm text-danger p-0" title="Unmap">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Nested Children --}}
@foreach($group->children as $child)
    @include('admin.widget-clients._location-group-item', ['group' => $child, 'level' => $level + 1])
@endforeach
