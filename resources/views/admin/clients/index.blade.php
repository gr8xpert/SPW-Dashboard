@extends('layouts.admin')

@section('title', 'Clients — Smart Property Management Admin')
@section('page-title', 'Clients')

@section('page-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-buildings me-2 text-primary"></i>Clients</h4>
        <p class="text-muted mb-0">
            {{ $clients->total() }} {{ Str::plural('client', $clients->total()) }} registered
        </p>
    </div>
    <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Client
    </a>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.clients.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0"
                           placeholder="Search by company name..."
                           value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="plan" class="form-select">
                    <option value="">All Plans</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="trial"     {{ request('status') === 'trial'     ? 'selected' : '' }}>Trial</option>
                    <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'plan', 'status']))
                    <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary" title="Clear filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Clients Table --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Company Name</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th width="180">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    @php
                        $statusColors = [
                            'active'    => 'success',
                            'trial'     => 'info',
                            'suspended' => 'danger',
                            'cancelled' => 'secondary',
                        ];
                        $statusColor = $statusColors[$client->status] ?? 'secondary';
                    @endphp
                    <tr>
                        <td class="text-muted small">{{ $client->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $client->company_name }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $client->plan->name ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $statusColor }}">
                                {{ ucfirst($client->status) }}
                            </span>
                        </td>
                        <td class="text-muted small">
                            {{ $client->created_at->format('M d, Y') }}
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                {{-- View --}}
                                <a href="{{ route('admin.clients.show', $client) }}"
                                   class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>

                                {{-- Suspend / Activate --}}
                                @if($client->status !== 'suspended')
                                    <form method="POST" action="{{ route('admin.clients.suspend', $client) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Suspend"
                                                onclick="return confirm('Suspend {{ addslashes($client->company_name) }}?')">
                                            <i class="bi bi-pause-circle"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.clients.activate', $client) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Activate">
                                            <i class="bi bi-play-circle"></i>
                                        </button>
                                    </form>
                                @endif

                                {{-- Delete --}}
                                <form method="POST" action="{{ route('admin.clients.destroy', $client) }}"
                                      onsubmit="return confirm('Permanently delete {{ addslashes($client->company_name) }}? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-buildings fs-1 d-block mb-2 opacity-25"></i>
                            No clients found.
                            @if(request()->hasAny(['search', 'plan', 'status']))
                                <a href="{{ route('admin.clients.index') }}" class="d-block mt-2">Clear filters</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($clients->hasPages())
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing {{ $clients->firstItem() }}–{{ $clients->lastItem() }}
                of {{ $clients->total() }} clients
            </small>
            {{ $clients->links() }}
        </div>
    @endif
</div>

@endsection
