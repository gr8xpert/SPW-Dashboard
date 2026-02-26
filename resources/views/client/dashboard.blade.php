@extends('layouts.client')

@section('title', 'Dashboard — Smart Property Management')

@section('page-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Welcome back, {{ auth()->user()->name }} 👋</h4>
        <p class="text-muted mb-0">Here's what's happening with {{ $client->company_name }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard.campaigns.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Campaign
        </a>
        <a href="{{ route('dashboard.contacts.create') }}" class="btn btn-outline-secondary">
            <i class="bi bi-person-plus me-1"></i> Add Contact
        </a>
    </div>
</div>

{{-- Trial Banner --}}
@if($client->status === 'trial' && $client->trial_ends_at)
    <div class="alert alert-info d-flex align-items-center mb-4">
        <i class="bi bi-clock me-2 fs-5"></i>
        <div>
            <strong>Trial ends {{ $client->trial_ends_at->diffForHumans() }}</strong>
            — Upgrade to keep all your data and features.
            <a href="{{ route('dashboard.billing.index') }}" class="alert-link ms-2">Upgrade now →</a>
        </div>
    </div>
@endif

{{-- Stats Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-primary bg-opacity-10">
                    <i class="bi bi-people text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Contacts</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['total_contacts']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-success bg-opacity-10">
                    <i class="bi bi-send text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Emails Sent</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['emails_sent']) }}</div>
                    <div class="text-muted" style="font-size:.75rem">
                        of {{ number_format($stats['emails_limit']) }} this month
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-warning bg-opacity-10">
                    <i class="bi bi-eye text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small">Avg. Open Rate</div>
                    <div class="fw-bold fs-4">{{ $stats['avg_open_rate'] }}%</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-info bg-opacity-10">
                    <i class="bi bi-megaphone text-info"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Campaigns</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['total_campaigns']) }}</div>
                    @if($stats['sending_campaigns'] > 0)
                        <span class="badge bg-success" style="font-size:.7rem">
                            {{ $stats['sending_campaigns'] }} sending now
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Usage Progress Bar --}}
<div class="card stat-card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold">Monthly Email Usage</span>
            <small class="text-muted">
                {{ number_format($stats['emails_sent']) }} / {{ number_format($stats['emails_limit']) }}
            </small>
        </div>
        @php
            $pct = $stats['emails_limit'] > 0 ? min(100, ($stats['emails_sent'] / $stats['emails_limit']) * 100) : 0;
            $barClass = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-primary');
        @endphp
        <div class="progress" style="height: 8px;">
            <div class="progress-bar {{ $barClass }}" style="width: {{ $pct }}%"></div>
        </div>
        @if($pct >= 90)
            <small class="text-danger mt-1 d-block">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Almost at limit!
                <a href="{{ route('dashboard.billing.index') }}">Upgrade now</a>
            </small>
        @endif
    </div>
</div>

<div class="row g-4">
    {{-- Engagement Chart --}}
    <div class="col-xl-8">
        <div class="card stat-card h-100">
            <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between pt-3">
                <h6 class="fw-semibold mb-0">Engagement — Last 30 Days</h6>
            </div>
            <div class="card-body">
                <canvas id="engagementChart" height="280"></canvas>
            </div>
        </div>
    </div>

    {{-- Recent Campaigns --}}
    <div class="col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between pt-3">
                <h6 class="fw-semibold mb-0">Recent Campaigns</h6>
                <a href="{{ route('dashboard.campaigns.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
            <div class="card-body p-0">
                @forelse($recentCampaigns as $campaign)
                    <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-semibold text-truncate small">{{ $campaign->name }}</div>
                            <div class="text-muted" style="font-size:.75rem">
                                {{ $campaign->created_at->diffForHumans() }}
                            </div>
                        </div>
                        <span class="badge
                            @if($campaign->status === 'sent') bg-success
                            @elseif($campaign->status === 'sending') bg-primary
                            @elseif($campaign->status === 'scheduled') bg-info
                            @elseif($campaign->status === 'draft') bg-secondary
                            @else bg-light text-dark
                            @endif" style="font-size:.7rem">
                            {{ ucfirst($campaign->status) }}
                        </span>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-send fs-1 d-block mb-2 opacity-25"></i>
                        <p class="small mb-2">No campaigns yet</p>
                        <a href="{{ route('dashboard.campaigns.create') }}" class="btn btn-sm btn-primary">Create Campaign</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const ctx = document.getElementById('engagementChart');
const chartData = @json($chartData);

new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartData.labels,
        datasets: [
            {
                label: 'Opens',
                data: chartData.opens,
                borderColor: '#2563EB',
                backgroundColor: 'rgba(37,99,235,.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
            },
            {
                label: 'Clicks',
                data: chartData.clicks,
                borderColor: '#10B981',
                backgroundColor: 'rgba(16,185,129,.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.04)' } }
        }
    }
});
</script>
@endpush
