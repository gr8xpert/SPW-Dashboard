@extends('layouts.client')

@section('title', 'Widget Analytics')

@php
// TEMP FIX: Load all properties directly if not passed from controller
if (empty($allProperties) && !empty(auth()->user()->client->domain)) {
    $analyticsService = app(\App\Services\WidgetAnalyticsService::class);
    $data = $analyticsService->getAllAnalytics(auth()->user()->client->domain, $period ?? '30');
    $allProperties = $data['properties']['properties'] ?? [];
    $topProperties = array_slice($allProperties, 0, 10);
}
@endphp

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-graph-up me-2 text-primary"></i>Widget Analytics</h4>
        <p class="text-muted mb-0">Track how visitors interact with your property search widget</p>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" action="{{ url()->current() }}" class="d-flex gap-2">
            <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="7"   {{ ($period ?? '30') == '7'   ? 'selected' : '' }}>Last 7 days</option>
                <option value="30"  {{ ($period ?? '30') == '30'  ? 'selected' : '' }}>Last 30 days</option>
                <option value="90"  {{ ($period ?? '30') == '90'  ? 'selected' : '' }}>Last 90 days</option>
                <option value="all" {{ ($period ?? '30') == 'all' ? 'selected' : '' }}>All time</option>
            </select>
        </form>
    </div>
</div>

{{-- API Down Banner --}}
@if(!empty($apiDown))
<div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <div>Analytics data is temporarily unavailable. Some information may be incomplete. Please try again later.</div>
</div>
@endif

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-search fs-4 text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Searches</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['searches'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3">
                    <i class="bi bi-eye fs-4 text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Property Views</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['property_views'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 p-3">
                    <i class="bi bi-hand-index fs-4 text-info"></i>
                </div>
                <div>
                    <div class="text-muted small">Card Clicks</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['card_clicks'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-danger bg-opacity-10 p-3">
                    <i class="bi bi-heart fs-4 text-danger"></i>
                </div>
                <div>
                    <div class="text-muted small">Wishlist Adds</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['wishlist_adds'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-envelope-paper fs-4 text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small">Inquiries</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['inquiries'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-secondary bg-opacity-10 p-3">
                    <i class="bi bi-file-pdf fs-4 text-secondary"></i>
                </div>
                <div>
                    <div class="text-muted small">PDF Downloads</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['pdf_downloads'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Charts Row --}}
<div class="row g-4 mb-4">
    {{-- Activity Trends Line Chart --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
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
    </div>

    {{-- Event Breakdown Donut --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-pie-chart me-2 text-primary"></i>Event Breakdown</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                @if(($stats['searches'] ?? 0) + ($stats['property_views'] ?? 0) + ($stats['inquiries'] ?? 0) > 0)
                    <canvas id="eventBreakdownChart" height="260"></canvas>
                @else
                    <p class="text-muted mb-0">No event data yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Properties Section with Tabs --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom pt-4 pb-3">
        <div class="d-flex align-items-center justify-content-between">
            <h6 class="fw-bold mb-0"><i class="bi bi-building me-2 text-primary"></i>Property Analytics</h6>
            <div class="d-flex gap-2 align-items-center">
                {{-- Search Box --}}
                <input type="text" id="propertySearch" class="form-control form-control-sm" placeholder="Filter by ref..." style="width: 150px;">
                {{-- Tab Buttons --}}
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary active" data-tab="top">Top 10</button>
                    <button type="button" class="btn btn-outline-primary" data-tab="all">Top 100</button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0" id="propertiesTable">
                <thead class="table-light sticky-top">
                    <tr>
                        <th>Property Ref</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th class="text-end">Views</th>
                        <th class="text-end">Clicks</th>
                        <th class="text-end">Wishlist</th>
                        <th class="text-end">Inquiries</th>
                        <th class="text-end">PDFs</th>
                        <th class="text-end">Unique Users</th>
                    </tr>
                </thead>
                <tbody id="propertiesTableBody" data-total="{{ count($allProperties ?? []) }}">
                    @forelse($allProperties ?? $topProperties ?? [] as $property)
                        <tr data-ref="{{ strtolower($property['property_ref'] ?? '') }}" class="{{ $loop->index >= 10 ? 'd-none all-property' : 'top-property' }}">
                            <td class="fw-medium">{{ $property['property_ref'] ?? '-' }}</td>
                            <td>{{ $property['location'] ?: '-' }}</td>
                            <td>{{ $property['property_type'] ?: '-' }}</td>
                            <td class="text-end">{{ number_format($property['views'] ?? 0) }}</td>
                            <td class="text-end">{{ number_format($property['clicks'] ?? 0) }}</td>
                            <td class="text-end">{{ number_format($property['wishlist_adds'] ?? 0) }}</td>
                            <td class="text-end">{{ number_format($property['inquiries'] ?? 0) }}</td>
                            <td class="text-end">{{ number_format($property['pdf_downloads'] ?? 0) }}</td>
                            <td class="text-end">{{ number_format($property['unique_users'] ?? 0) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No property data available yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-top py-2">
        <small class="text-muted" id="propertyCount">
            Showing <span id="visibleCount">{{ min(10, count($allProperties ?? $topProperties ?? [])) }}</span> of <span id="totalCount">{{ count($allProperties ?? $topProperties ?? []) }}</span> most active properties
        </small>
        <!-- DEBUG: allProperties={{ count($allProperties ?? []) }} topProperties={{ count($topProperties ?? []) }} allDefined={{ isset($allProperties) ? 'yes' : 'no' }} topDefined={{ isset($topProperties) ? 'yes' : 'no' }} controllerDebug={{ json_encode($debugInfo ?? 'not set') }} rendered={{ now()->timestamp }} -->
    </div>
</div>

{{-- Search Insights --}}
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
                                <th class="text-end">Searches</th>
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
const chartData = @json($chartData);

// Activity Trends Line Chart
const ctx = document.getElementById('widgetAnalyticsChart');
if (ctx) {
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
}

// Event Breakdown Donut Chart
const donutCtx = document.getElementById('eventBreakdownChart');
if (donutCtx) {
    new Chart(donutCtx, {
        type: 'doughnut',
        data: {
            labels: ['Searches', 'Property Views', 'Card Clicks', 'Wishlist Adds', 'Inquiries'],
            datasets: [{
                data: [
                    {{ $stats['searches'] ?? 0 }},
                    {{ $stats['property_views'] ?? 0 }},
                    {{ $stats['card_clicks'] ?? 0 }},
                    {{ $stats['wishlist_adds'] ?? 0 }},
                    {{ $stats['inquiries'] ?? 0 }}
                ],
                backgroundColor: ['#2563EB', '#10B981', '#06B6D4', '#EF4444', '#F59E0B'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 12, usePointStyle: true, pointStyle: 'circle' }
                }
            }
        }
    });
}

// Property table tabs and search - wrap in DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('[data-tab]');
    const tableBody = document.getElementById('propertiesTableBody');
    const searchInput = document.getElementById('propertySearch');
    const visibleCountEl = document.getElementById('visibleCount');

    console.log('Property tabs init - rows in DOM:', tableBody?.querySelectorAll('tr').length, 'server expected:', tableBody?.dataset.total);

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            tabButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const mode = this.dataset.tab;
            const rows = tableBody?.querySelectorAll('tr') || [];
            let visibleCount = 0;

            console.log('Tab clicked:', mode, 'rows:', rows.length);

            rows.forEach((row, index) => {
                if (mode === 'all') {
                    row.classList.remove('d-none');
                    visibleCount++;
                } else {
                    if (index < 10) {
                        row.classList.remove('d-none');
                        visibleCount++;
                    } else {
                        row.classList.add('d-none');
                    }
                }
            });

            if (visibleCountEl) visibleCountEl.textContent = visibleCount;
            if (searchInput) searchInput.value = '';
        });
    });

    // Property search
    searchInput?.addEventListener('input', function() {
        const search = this.value.toLowerCase().trim();
        const rows = tableBody?.querySelectorAll('tr') || [];
        let visibleCount = 0;

        // Switch to "All" view when searching
        if (search) {
            tabButtons.forEach(b => b.classList.remove('active'));
            document.querySelector('[data-tab="all"]')?.classList.add('active');
        }

        rows.forEach(row => {
            const ref = row.dataset.ref || '';
            if (!search || ref.includes(search)) {
                row.classList.remove('d-none');
                visibleCount++;
            } else {
                row.classList.add('d-none');
            }
        });

        if (visibleCountEl) visibleCountEl.textContent = visibleCount;
    });
});
</script>
@endpush
