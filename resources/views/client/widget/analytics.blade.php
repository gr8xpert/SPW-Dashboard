@extends('layouts.client')

@section('title', 'Widget Analytics')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-graph-up me-2 text-primary"></i>Widget Analytics</h4>
        <p class="text-muted mb-0">Track how visitors interact with your property search widget</p>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" action="{{ route('dashboard.widget.analytics') }}" class="d-flex gap-2">
            <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="7"  {{ request('period', '30') == '7'  ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ request('period', '30') == '30' ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ request('period', '30') == '90' ? 'selected' : '' }}>Last 90 days</option>
            </select>
        </form>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-search fs-4 text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Searches</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['searches'] ?? 0) }}</div>
                    @if(isset($stats['searches_change']))
                        <small class="{{ $stats['searches_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="bi bi-{{ $stats['searches_change'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ abs($stats['searches_change']) }}%
                        </small>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3">
                    <i class="bi bi-eye fs-4 text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Property Views</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['views'] ?? 0) }}</div>
                    @if(isset($stats['views_change']))
                        <small class="{{ $stats['views_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="bi bi-{{ $stats['views_change'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ abs($stats['views_change']) }}%
                        </small>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-envelope-paper fs-4 text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small">Inquiries</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['inquiries'] ?? 0) }}</div>
                    @if(isset($stats['inquiries_change']))
                        <small class="{{ $stats['inquiries_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="bi bi-{{ $stats['inquiries_change'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ abs($stats['inquiries_change']) }}%
                        </small>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 p-3">
                    <i class="bi bi-percent fs-4 text-info"></i>
                </div>
                <div>
                    <div class="text-muted small">Conversion Rate</div>
                    <div class="fw-bold fs-4">{{ $stats['conversion_rate'] ?? '0.0' }}%</div>
                    <small class="text-muted">views to inquiries</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Chart Area --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Activity Over Time</h6>
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-primary active" data-chart="all">All</button>
            <button type="button" class="btn btn-outline-primary" data-chart="searches">Searches</button>
            <button type="button" class="btn btn-outline-primary" data-chart="views">Views</button>
            <button type="button" class="btn btn-outline-primary" data-chart="inquiries">Inquiries</button>
        </div>
    </div>
    <div class="card-body">
        <canvas id="widgetAnalyticsChart" height="300"></canvas>
    </div>
</div>

<div class="row g-4">
    {{-- Top Searched Locations --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-geo-alt me-2 text-primary"></i>Top Searched Locations</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Location</th>
                                <th class="text-end">Searches</th>
                                <th class="text-end">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topLocations ?? [] as $location)
                                <tr>
                                    <td class="fw-medium">{{ $location['name'] }}</td>
                                    <td class="text-end">{{ number_format($location['count']) }}</td>
                                    <td class="text-end text-muted">{{ $location['percentage'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        No search data available yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Property Types --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-house-door me-2 text-primary"></i>Top Property Types</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Property Type</th>
                                <th class="text-end">Views</th>
                                <th class="text-end">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topPropertyTypes ?? [] as $type)
                                <tr>
                                    <td class="fw-medium">{{ $type['name'] }}</td>
                                    <td class="text-end">{{ number_format($type['count']) }}</td>
                                    <td class="text-end text-muted">{{ $type['percentage'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        No property type data available yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const ctx = document.getElementById('widgetAnalyticsChart');
const chartData = @json($chartData ?? ['labels' => [], 'searches' => [], 'views' => [], 'inquiries' => []]);

const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartData.labels,
        datasets: [
            {
                label: 'Searches',
                data: chartData.searches,
                borderColor: '#2563EB',
                backgroundColor: 'rgba(37,99,235,.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
            },
            {
                label: 'Views',
                data: chartData.views,
                borderColor: '#10B981',
                backgroundColor: 'rgba(16,185,129,.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
            },
            {
                label: 'Inquiries',
                data: chartData.inquiries,
                borderColor: '#F59E0B',
                backgroundColor: 'rgba(245,158,11,.08)',
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

document.querySelectorAll('[data-chart]').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('[data-chart]').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const mode = this.dataset.chart;
        chart.data.datasets.forEach(ds => {
            if (mode === 'all') {
                ds.hidden = false;
            } else {
                ds.hidden = ds.label.toLowerCase() !== mode;
            }
        });
        chart.update();
    });
});
</script>
@endpush
