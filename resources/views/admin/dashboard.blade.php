@extends('layouts.admin')

@section('title', 'Admin Dashboard — Smart Property Management')
@section('page-title', 'Admin Dashboard')

@section('page-content')

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-primary bg-opacity-10">
                    <i class="bi bi-buildings text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Clients</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['total_clients']) }}</div>
                    <small class="text-success">{{ $stats['active_clients'] }} active</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-success bg-opacity-10">
                    <i class="bi bi-currency-euro text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Monthly Revenue (MRR)</div>
                    <div class="fw-bold fs-4">€{{ number_format($stats['mrr'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-warning bg-opacity-10">
                    <i class="bi bi-send text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small">Emails This Month</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['emails_this_month']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-info bg-opacity-10">
                    <i class="bi bi-person-plus text-info"></i>
                </div>
                <div>
                    <div class="text-muted small">New Signups (30d)</div>
                    <div class="fw-bold fs-4">{{ $stats['new_signups'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Email Volume Chart --}}
    <div class="col-xl-8">
        <div class="card stat-card">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="fw-semibold mb-0">Email Volume — Last 30 Days</h6>
            </div>
            <div class="card-body">
                <canvas id="emailChart" height="260"></canvas>
            </div>
        </div>
    </div>

    {{-- Client Status Breakdown --}}
    <div class="col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="fw-semibold mb-0">Client Status</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>
    </div>

    {{-- Recent Clients --}}
    <div class="col-12">
        <div class="card stat-card">
            <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between pt-3">
                <h6 class="fw-semibold mb-0">Recent Clients</h6>
                <a href="{{ route('admin.clients.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Company</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentClients as $client)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $client->company_name }}</div>
                                <small class="text-muted">{{ $client->subdomain }}.smartmailer.com</small>
                            </td>
                            <td><span class="badge bg-light text-dark">{{ $client->plan->name }}</span></td>
                            <td>
                                <span class="badge
                                    @if($client->status === 'active') bg-success
                                    @elseif($client->status === 'trial') bg-info
                                    @elseif($client->status === 'suspended') bg-danger
                                    @else bg-secondary
                                    @endif">
                                    {{ ucfirst($client->status) }}
                                </span>
                            </td>
                            <td><small class="text-muted">{{ $client->created_at->format('M d, Y') }}</small></td>
                            <td>
                                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Email volume chart
const emailCtx = document.getElementById('emailChart');
const emailData = @json($emailsPerDay);

new Chart(emailCtx, {
    type: 'bar',
    data: {
        labels: emailData.map(d => d.date),
        datasets: [{
            label: 'Emails Sent',
            data: emailData.map(d => d.count),
            backgroundColor: 'rgba(37,99,235,.7)',
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.04)' } }
        }
    }
});

// Status pie chart
const statusCtx = document.getElementById('statusChart');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Active', 'Trial', 'Suspended', 'Cancelled'],
        datasets: [{
            data: [
                {{ $stats['active_clients'] }},
                {{ $stats['trial_clients'] }},
                {{ $stats['suspended'] }},
                0
            ],
            backgroundColor: ['#10B981', '#3B82F6', '#EF4444', '#94A3B8'],
        }]
    },
    options: {
        cutout: '65%',
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>
@endpush
