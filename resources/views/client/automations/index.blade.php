@extends('layouts.client')

@section('title', 'Automations — Smart Property Management')

@section('page-content')

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-robot me-2 text-primary"></i>Automations</h4>
        <p class="text-muted mb-0">Build and manage automated email workflows</p>
    </div>
    <a href="{{ route('dashboard.automations.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Automation
    </a>
</div>

{{-- Automations Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($automations->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-robot fs-1 d-block mb-3 opacity-25"></i>
                <p class="mb-1 fw-semibold">No automations yet</p>
                <p class="small mb-3">Create your first automation to start sending triggered emails.</p>
                <a href="{{ route('dashboard.automations.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> New Automation
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Trigger</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($automations as $auto)
                            @php
                                $triggerLabels = [
                                    'contact_added'    => 'Contact Added',
                                    'tag_added'        => 'Tag Added',
                                    'contact_updated'  => 'Contact Updated',
                                    'date_field'       => 'Date Field',
                                    'manual'           => 'Manual',
                                    'engagement_drop'  => 'Engagement Drop',
                                ];
                                $triggerIcons = [
                                    'contact_added'    => 'bi-person-plus',
                                    'tag_added'        => 'bi-tag',
                                    'contact_updated'  => 'bi-person-gear',
                                    'date_field'       => 'bi-calendar-event',
                                    'manual'           => 'bi-hand-index',
                                    'engagement_drop'  => 'bi-graph-down',
                                ];
                                $statusBadge = match($auto->status) {
                                    'active' => 'success',
                                    'paused' => 'warning',
                                    default  => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">
                                        <a href="{{ route('dashboard.automations.edit', $auto) }}"
                                           class="text-decoration-none text-dark">
                                            {{ $auto->name }}
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        <i class="bi {{ $triggerIcons[$auto->trigger_type] ?? 'bi-lightning' }} me-1"></i>
                                        {{ $triggerLabels[$auto->trigger_type] ?? ucfirst(str_replace('_', ' ', $auto->trigger_type)) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $statusBadge }} bg-opacity-10 text-{{ $statusBadge }} border border-{{ $statusBadge }} border-opacity-25">
                                        {{ ucfirst($auto->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $auto->created_at->format('M j, Y') }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        {{-- Edit --}}
                                        <a href="{{ route('dashboard.automations.edit', $auto) }}"
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        {{-- Activate --}}
                                        @if($auto->status !== 'active')
                                            <form method="POST" action="{{ route('dashboard.automations.activate', $auto) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Activate">
                                                    <i class="bi bi-play-fill"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Pause --}}
                                        @if($auto->status === 'active')
                                            <form method="POST" action="{{ route('dashboard.automations.pause', $auto) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Pause">
                                                    <i class="bi bi-pause-fill"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Delete --}}
                                        <form method="POST" action="{{ route('dashboard.automations.destroy', $auto) }}"
                                              onsubmit="return confirm('Delete automation \'{{ addslashes($auto->name) }}\'? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($automations->hasPages())
                <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top">
                    <small class="text-muted">
                        Showing {{ $automations->firstItem() }}–{{ $automations->lastItem() }}
                        of {{ $automations->total() }} automations
                    </small>
                    {{ $automations->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

@endsection
