@extends('layouts.admin')

@section('page-title', 'Credit Analytics')

@section('page-content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Credit Analytics Overview</h1>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card border-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Total Credits Issued</p>
                            <h2 class="fw-bold text-primary mb-0">{{ number_format($summary['total_issued'] ?? 0) }}</h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded p-2">
                            <i class="bi bi-coin fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card border-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Credits In Circulation</p>
                            <h2 class="fw-bold text-success mb-0">{{ number_format($summary['in_circulation'] ?? 0) }}</h2>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded p-2">
                            <i class="bi bi-arrow-repeat fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card border-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Credits Used (30d)</p>
                            <h2 class="fw-bold text-warning mb-0">{{ number_format($summary['used_30d'] ?? 0) }}</h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded p-2">
                            <i class="bi bi-graph-down fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card border-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Avg Credits / Client</p>
                            <h2 class="fw-bold text-info mb-0">{{ number_format($summary['avg_per_client'] ?? 0, 1) }}</h2>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded p-2">
                            <i class="bi bi-bar-chart fs-4 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Client Credit Balances --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Client Credit Balances</h5>
                    <span class="badge bg-secondary">{{ count($clientCredits ?? []) }} clients</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client / Domain</th>
                                    <th>Plan</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-end">Used (30d)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($clientCredits as $credit)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.widget-clients.edit', $credit->client_id) }}">
                                                {{ $credit->domain }}
                                            </a>
                                        </td>
                                        <td><span class="badge bg-info text-dark">{{ ucfirst($credit->plan) }}</span></td>
                                        <td class="text-end">
                                            <span class="fw-bold {{ $credit->balance <= 0 ? 'text-danger' : '' }}">
                                                {{ number_format($credit->balance) }}
                                            </span>
                                        </td>
                                        <td class="text-end text-muted">{{ number_format($credit->used_30d) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No client credit data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Webmaster Credit Balances --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Webmaster Credit Balances</h5>
                    <span class="badge bg-secondary">{{ count($webmasterCredits ?? []) }} webmasters</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Webmaster</th>
                                    <th>Clients</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-end">Used (30d)</th>
                                    <th class="text-end">Allocated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($webmasterCredits as $credit)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.webmasters.show', $credit->webmaster_id) }}">
                                                {{ $credit->name }}
                                            </a>
                                        </td>
                                        <td>{{ $credit->clients_count }}</td>
                                        <td class="text-end">
                                            <span class="fw-bold {{ $credit->balance <= 0 ? 'text-danger' : '' }}">
                                                {{ number_format($credit->balance) }}
                                            </span>
                                        </td>
                                        <td class="text-end text-muted">{{ number_format($credit->used_30d) }}</td>
                                        <td class="text-end text-muted">{{ number_format($credit->allocated) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No webmaster credit data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Transactions --}}
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Recent Credit Transactions</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Client / Webmaster</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Balance After</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $txn)
                            <tr>
                                <td>{{ $txn->created_at->format('M d, Y g:i A') }}</td>
                                <td>
                                    @if($txn->amount > 0)
                                        <span class="badge bg-success">Credit</span>
                                    @else
                                        <span class="badge bg-danger">Debit</span>
                                    @endif
                                </td>
                                <td>{{ $txn->description }}</td>
                                <td>{{ $txn->entity_name ?? '—' }}</td>
                                <td class="text-end fw-bold {{ $txn->amount > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $txn->amount > 0 ? '+' : '' }}{{ number_format($txn->amount) }}
                                </td>
                                <td class="text-end">{{ number_format($txn->balance_after) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No recent transactions.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
