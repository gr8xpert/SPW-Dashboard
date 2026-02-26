@extends('layouts.client')

@section('title', 'Analytics Overview — Smart Property Management')

@section('page-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Analytics Overview</h4>
        <p class="text-muted mb-0">Performance insights across all your campaigns</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard.analytics.campaigns') }}" class="btn btn-outline-primary">
            <i class="bi bi-send me-1"></i> Campaign Analytics
        </a>
        <a href="{{ route('dashboard.analytics.contacts') }}" class="btn btn-outline-secondary">
            <i class="bi bi-people me-1"></i> Contact Analytics
        </a>
    </div>
</div>

{{-- Main Stats Row --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-send-fill fs-4 text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Sent</div>
                    <div class="fw-bold fs-4">{{ number_format($totalSent) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3">
                    <i class="bi bi-eye-fill fs-4 text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Opens</div>
                    <div class="fw-bold fs-4">{{ number_format($totalOpens) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 p-3">
                    <i class="bi bi-cursor-fill fs-4 text-info"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Clicks</div>
                    <div class="fw-bold fs-4">{{ number_format($totalClicks) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Bounces</div>
                    <div class="fw-bold fs-4">{{ number_format($totalBounces) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-danger bg-opacity-10 p-3">
                    <i class="bi bi-person-dash-fill fs-4 text-danger"></i>
                </div>
                <div>
                    <div class="text-muted small">Unsubscribes</div>
                    <div class="fw-bold fs-4">{{ number_format($totalUnsubs) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Rate Highlights --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #2563EB !important;">
            <div class="card-body text-center py-4">
                <div class="text-muted small text-uppercase fw-semibold letter-spacing-1 mb-1">Open Rate</div>
                <div class="display-5 fw-bold text-primary">{{ number_format($openRate, 1) }}%</div>
                <div class="text-muted small mt-1">of all emails sent were opened</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #10B981 !important;">
            <div class="card-body text-center py-4">
                <div class="text-muted small text-uppercase fw-semibold letter-spacing-1 mb-1">Click Rate</div>
                <div class="display-5 fw-bold text-success">{{ number_format($clickRate, 1) }}%</div>
                <div class="text-muted small mt-1">of all emails sent received a click</div>
            </div>
        </div>
    </div>
</div>

{{-- Engagement Chart --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between pt-3">
        <h6 class="fw-semibold mb-0">
            <i class="bi bi-graph-up me-2 text-primary"></i>Opens &amp; Clicks — Last 30 Days
        </h6>
        <span class="badge bg-light text-dark border">Last 30 days</span>
    </div>
    <div class="card-body">
        <canvas id="analyticsChart" height="280"></canvas>
    </div>
</div>

{{-- Top Campaigns Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between pt-3">
        <h6 class="fw-semibold mb-0">
            <i class="bi bi-trophy me-2 text-warning"></i>Top Performing Campaigns
        </h6>
        <a href="{{ route('dashboard.analytics.campaigns') }}" class="btn btn-sm btn-outline-primary">
            View All
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Campaign Name</th>
                    <th class="text-end">Sent</th>
                    <th class="text-muted">Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topCampaigns as $campaign)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $campaign->name }}</div>
                        </td>
                        <td class="text-end">
                            <span class="fw-semibold">{{ number_format($campaign->sent_count ?? 0) }}</span>
                        </td>
                        <td class="text-muted small">{{ $campaign->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-5 text-muted">
                            <i class="bi bi-bar-chart fs-1 d-block mb-2 opacity-25"></i>
                            No campaign data available yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const ctx = document.getElementById('analyticsChart');
    const labels    = @json($labels);
    const openData  = @json($openData);
    const clickData = @json($clickData);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Opens',
                    data: openData,
                    borderColor: '#2563EB',
                    backgroundColor: 'rgba(37,99,235,.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                },
                {
                    label: 'Clicks',
                    data: clickData,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16,185,129,.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString() } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.04)' } }
            }
        }
    });
}());
</script>
@endpush
