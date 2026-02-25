@extends('layouts.admin')

@section('page-title', 'Subscription Status Dashboard')

@section('page-content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Subscription Status Dashboard</h1>

    {{-- Status Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-lg-2">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <h2 class="display-6 fw-bold text-success">{{ $counts['active'] ?? 0 }}</h2>
                    <p class="text-muted mb-0">Active</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <h2 class="display-6 fw-bold text-warning">{{ $counts['grace'] ?? 0 }}</h2>
                    <p class="text-muted mb-0">Grace Period</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card border-danger h-100">
                <div class="card-body text-center">
                    <h2 class="display-6 fw-bold text-danger">{{ $counts['expired'] ?? 0 }}</h2>
                    <p class="text-muted mb-0">Expired</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card border-secondary h-100">
                <div class="card-body text-center">
                    <h2 class="display-6 fw-bold text-secondary">{{ $counts['manual'] ?? 0 }}</h2>
                    <p class="text-muted mb-0">Manual</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card border-dark h-100">
                <div class="card-body text-center">
                    <h2 class="display-6 fw-bold">{{ $counts['internal'] ?? 0 }}</h2>
                    <p class="text-muted mb-0">Internal</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card border-info h-100">
                <div class="card-body text-center">
                    <h2 class="display-6 fw-bold text-info">{{ $counts['overridden'] ?? 0 }}</h2>
                    <p class="text-muted mb-0">Admin Overridden</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Grace Period Clients Table --}}
    <div class="card">
        <div class="card-header bg-warning bg-opacity-10">
            <h5 class="card-title mb-0">
                <i class="bi bi-exclamation-triangle text-warning"></i>
                Clients in Grace Period
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Domain</th>
                            <th>Plan</th>
                            <th>Subscription Expired</th>
                            <th>Grace Period Ends</th>
                            <th>Days Remaining</th>
                            <th>Admin Override</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($graceClients as $client)
                            <tr>
                                <td>
                                    <a href="{{ $client->domain }}" target="_blank" rel="noopener">{{ $client->domain }}</a>
                                </td>
                                <td><span class="badge bg-info text-dark">{{ ucfirst($client->plan) }}</span></td>
                                <td>{{ $client->expires_at?->format('M d, Y') ?? '—' }}</td>
                                <td>{{ $client->grace_period_ends_at?->format('M d, Y') ?? '—' }}</td>
                                <td>
                                    @php
                                        $daysRemaining = $client->grace_period_ends_at ? now()->diffInDays($client->grace_period_ends_at, false) : null;
                                    @endphp
                                    @if($daysRemaining !== null)
                                        <span class="badge {{ $daysRemaining <= 3 ? 'bg-danger' : 'bg-warning text-dark' }}">
                                            {{ $daysRemaining > 0 ? $daysRemaining . ' days' : 'Ended' }}
                                        </span>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td>
                                    @if($client->admin_override)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="text-muted">No</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.widget-clients.edit', $client) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No clients currently in grace period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recently Expired --}}
    <div class="card mt-4">
        <div class="card-header bg-danger bg-opacity-10">
            <h5 class="card-title mb-0">
                <i class="bi bi-x-circle text-danger"></i>
                Recently Expired (Last 30 Days)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Domain</th>
                            <th>Plan</th>
                            <th>Expired On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentlyExpired as $client)
                            <tr>
                                <td>{{ $client->domain }}</td>
                                <td><span class="badge bg-info text-dark">{{ ucfirst($client->plan) }}</span></td>
                                <td>{{ $client->expires_at?->format('M d, Y') ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.widget-clients.edit', $client) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No recently expired clients.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
