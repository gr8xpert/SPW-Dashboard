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
                            <th>Client</th>
                            <th>API</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Expires</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                            <tr>
                                <td>{{ $client->id }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $client->company_name ?: 'Unnamed' }}</div>
                                    <a href="https://{{ $client->domain }}" target="_blank" rel="noopener" class="text-muted small">
                                        {{ $client->domain }} <i class="bi bi-box-arrow-up-right" style="font-size: 0.7em;"></i>
                                    </a>
                                </td>
                                <td>
                                    @if($client->resales_client_id && $client->resales_api_key)
                                        <span class="badge bg-success" title="Resales API configured">
                                            <i class="bi bi-plug-fill"></i>
                                        </span>
                                    @elseif($client->api_url && $client->api_key)
                                        <span class="badge bg-info" title="Legacy API configured">
                                            <i class="bi bi-plug"></i>
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted" title="No API configured">
                                            <i class="bi bi-x"></i>
                                        </span>
                                    @endif
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
                                            <span class="badge bg-warning text-dark">
                                                Grace <small>({{ $client->getGraceDaysRemaining() }}d)</small>
                                            </span>
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
                                    @if($client->admin_override)
                                        <span class="badge bg-info" title="Admin Override">
                                            <i class="bi bi-shield-check"></i>
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($client->subscription_expires_at)
                                        @if($client->subscription_expires_at->isPast())
                                            <span class="text-danger">{{ $client->subscription_expires_at->format('M d, Y') }}</span>
                                        @elseif($client->subscription_expires_at->diffInDays(now()) <= 7)
                                            <span class="text-warning">{{ $client->subscription_expires_at->format('M d, Y') }}</span>
                                        @else
                                            {{ $client->subscription_expires_at->format('M d, Y') }}
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.widget-clients.edit', $client) }}" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <form method="POST" action="{{ route('admin.widget-clients.toggle-override', $client) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-shield-{{ $client->admin_override ? 'x' : 'check' }} me-2"></i>
                                                        {{ $client->admin_override ? 'Disable Override' : 'Enable Override' }}
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <form method="POST" action="{{ route('admin.widget-clients.extend', $client) }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="period" value="1 month">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-calendar-plus me-2"></i>Extend 1 Month
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a href="{{ route('admin.widget-clients.location-grouping.index', $client) }}" class="dropdown-item">
                                                    <i class="bi bi-geo-alt me-2"></i>Location Grouping
                                                </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('admin.widget-clients.property-type-grouping.index', $client) }}" class="dropdown-item">
                                                    <i class="bi bi-house me-2"></i>Property Type Grouping
                                                </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('admin.widget-clients.feature-grouping.index', $client) }}" class="dropdown-item">
                                                    <i class="bi bi-check2-square me-2"></i>Feature Grouping
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
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
