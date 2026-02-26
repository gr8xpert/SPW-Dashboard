@extends('layouts.admin')

@section('page-title', 'Widget Clients')

@section('page-content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Widget Clients</h1>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.widget-clients.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by domain or name..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="plan" class="form-select">
                        <option value="">All Plans</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(request('plan') == $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="grace" @selected(request('status') === 'grace')>Grace Period</option>
                        <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                        <option value="manual" @selected(request('status') === 'manual')>Manual</option>
                        <option value="internal" @selected(request('status') === 'internal')>Internal</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Clients Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Domain</th>
                            <th>Plan</th>
                            <th>Subscription Status</th>
                            <th>Override</th>
                            <th>Expires At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                            <tr>
                                <td>{{ $client->id }}</td>
                                <td>
                                    <a href="{{ $client->domain }}" target="_blank" rel="noopener">{{ $client->domain }}</a>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark">{{ ucfirst($client->plan?->name ?? 'N/A') }}</span>
                                </td>
                                <td>
                                    @switch($client->subscription_status)
                                        @case('active')
                                            <span class="badge bg-success">Active</span>
                                            @break
                                        @case('grace')
                                            <span class="badge bg-warning text-dark">Grace Period</span>
                                            @break
                                        @case('expired')
                                            <span class="badge bg-danger">Expired</span>
                                            @break
                                        @case('manual')
                                            <span class="badge bg-secondary">Manual</span>
                                            @break
                                        @case('internal')
                                            <span class="badge bg-dark">Internal</span>
                                            @break
                                        @default
                                            <span class="badge bg-light text-dark">{{ $client->subscription_status }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    @if($client->admin_override)
                                        <span class="badge bg-info">
                                            <i class="bi bi-shield-check"></i> Admin Override
                                        </span>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td>{{ $client->subscription_expires_at ? $client->subscription_expires_at->format('M d, Y') : '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.widget-clients.edit', $client) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No clients found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($clients->hasPages())
            <div class="card-footer">
                {{ $clients->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
