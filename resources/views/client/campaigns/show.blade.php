@extends('layouts.client')

@section('title', '{{ $campaign->name }} — SmartMailer')

@section('page-content')

{{-- Page Header --}}
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('dashboard.campaigns.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h4 class="fw-bold mb-0">{{ $campaign->name }}</h4>
                <span class="badge fs-6
                    @if($campaign->status === 'sent') bg-success
                    @elseif($campaign->status === 'draft') bg-secondary
                    @elseif($campaign->status === 'scheduled') bg-primary
                    @elseif(in_array($campaign->status, ['sending','queued'])) bg-warning text-dark
                    @elseif($campaign->status === 'paused') bg-info text-dark
                    @elseif($campaign->status === 'cancelled') bg-dark
                    @elseif($campaign->status === 'failed') bg-danger
                    @else bg-secondary
                    @endif">
                    {{ ucfirst($campaign->status) }}
                </span>
            </div>
            <p class="text-muted mb-0 small mt-1">
                Subject: <em>{{ $campaign->subject }}</em>
                &mdash;
                Created {{ $campaign->created_at->format('M j, Y') }}
                @if($campaign->completed_at)
                    &mdash; Completed {{ $campaign->completed_at->diffForHumans() }}
                @endif
            </p>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex flex-wrap gap-2">
        {{-- Pause (if sending) --}}
        @if($campaign->status === 'sending')
            <form method="POST"
                  action="{{ route('dashboard.campaigns.pause', $campaign) }}"
                  onsubmit="return confirm('Pause this campaign? You can resume it later.')">
                @csrf
                @method('POST')
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-pause-circle me-1"></i>Pause
                </button>
            </form>
        @endif

        {{-- Cancel (if sending or scheduled) --}}
        @if(in_array($campaign->status, ['sending', 'scheduled', 'queued']))
            <form method="POST"
                  action="{{ route('dashboard.campaigns.cancel', $campaign) }}"
                  onsubmit="return confirm('Cancel this campaign? This cannot be undone.')">
                @csrf
                @method('POST')
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
            </form>
        @endif

        {{-- Edit (if not sent / cancelled) --}}
        @if(in_array($campaign->status, ['draft', 'scheduled', 'paused']))
            <a href="{{ route('dashboard.campaigns.edit', $campaign) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
        @endif
    </div>
</div>


{{-- ============================================================ --}}
{{-- RATE HIGHLIGHTS --}}
{{-- ============================================================ --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card text-center h-100 border-0 shadow-sm">
            <div class="card-body py-4">
                <div class="display-6 fw-bold text-primary mb-1">{{ $stats['open_rate'] }}%</div>
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.05em">Open Rate</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card text-center h-100 border-0 shadow-sm">
            <div class="card-body py-4">
                <div class="display-6 fw-bold text-success mb-1">{{ $stats['click_rate'] }}%</div>
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.05em">Click Rate</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card text-center h-100 border-0 shadow-sm">
            <div class="card-body py-4">
                <div class="display-6 fw-bold text-dark mb-1">{{ number_format($stats['sent']) }}</div>
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.05em">Total Sent</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card text-center h-100 border-0 shadow-sm">
            <div class="card-body py-4">
                <div class="display-6 fw-bold text-secondary mb-1">{{ number_format($stats['delivered']) }}</div>
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.05em">Delivered</div>
            </div>
        </div>
    </div>
</div>


{{-- ============================================================ --}}
{{-- STAT CARDS ROW 2 --}}
{{-- ============================================================ --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-primary bg-opacity-10 flex-shrink-0">
                    <i class="bi bi-send text-primary"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.73rem">Sent</div>
                    <div class="fw-bold fs-5">{{ number_format($stats['sent']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-success bg-opacity-10 flex-shrink-0">
                    <i class="bi bi-check2-all text-success"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.73rem">Delivered</div>
                    <div class="fw-bold fs-5">{{ number_format($stats['delivered']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-warning bg-opacity-10 flex-shrink-0">
                    <i class="bi bi-eye text-warning"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.73rem">Opens</div>
                    <div class="fw-bold fs-5">{{ number_format($stats['opens']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-info bg-opacity-10 flex-shrink-0">
                    <i class="bi bi-cursor text-info"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.73rem">Clicks</div>
                    <div class="fw-bold fs-5">{{ number_format($stats['clicks']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-danger bg-opacity-10 flex-shrink-0">
                    <i class="bi bi-exclamation-octagon text-danger"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.73rem">Bounces</div>
                    <div class="fw-bold fs-5">{{ number_format($stats['bounces']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-secondary bg-opacity-10 flex-shrink-0">
                    <i class="bi bi-person-dash text-secondary"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.73rem">Unsubscribes</div>
                    <div class="fw-bold fs-5">{{ number_format($stats['unsubscribes']) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- ============================================================ --}}
{{-- ENGAGEMENT CHART + RECENT EVENTS --}}
{{-- ============================================================ --}}
<div class="row g-4">

    {{-- Engagement Doughnut --}}
    <div class="col-lg-4">
        <div class="card stat-card h-100">
            <div class="card-header bg-transparent border-0 pt-3 pb-0">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-pie-chart me-2 text-primary"></i>Engagement Breakdown
                </h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <div style="max-width: 220px; width: 100%;">
                    <canvas id="engagementDoughnut"></canvas>
                </div>
                <div class="mt-3 w-100">
                    @php
                        $chartItems = [
                            ['label' => 'Opens',        'value' => $stats['opens'],        'color' => 'bg-warning'],
                            ['label' => 'Clicks',       'value' => $stats['clicks'],       'color' => 'bg-info'],
                            ['label' => 'Bounces',      'value' => $stats['bounces'],      'color' => 'bg-danger'],
                            ['label' => 'Unsubscribes', 'value' => $stats['unsubscribes'], 'color' => 'bg-secondary'],
                        ];
                    @endphp
                    @foreach($chartItems as $item)
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle d-inline-block {{ $item['color'] }}"
                                      style="width:10px;height:10px;"></span>
                                <span class="small text-muted">{{ $item['label'] }}</span>
                            </div>
                            <span class="small fw-semibold">{{ number_format($item['value']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Events Table --}}
    <div class="col-lg-8">
        <div class="card stat-card h-100">
            <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center justify-content-between">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-activity me-2 text-primary"></i>Recent Activity
                </h6>
                <span class="badge bg-primary bg-opacity-10 text-primary">
                    Last {{ $recentEvents->count() }} events
                </span>
            </div>
            <div class="card-body p-0">
                @if($recentEvents->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                        <p class="small mb-0">No events recorded yet.</p>
                        @if($campaign->status === 'draft')
                            <p class="small">Send or schedule this campaign to start tracking.</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Contact</th>
                                    <th>Event</th>
                                    <th>URL</th>
                                    <th class="pe-4">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentEvents as $event)
                                    @php
                                        $eventBadge = match($event->event_type) {
                                            'open'        => 'bg-success',
                                            'click'       => 'bg-primary',
                                            'bounce'      => 'bg-danger',
                                            'unsubscribe' => 'bg-warning text-dark',
                                            'complained'  => 'bg-dark',
                                            default       => 'bg-secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            @if($event->contact)
                                                <div class="fw-semibold small">{{ $event->contact->email }}</div>
                                                @if($event->contact->first_name || $event->contact->last_name)
                                                    <div class="text-muted" style="font-size:.73rem">
                                                        {{ trim($event->contact->first_name . ' ' . $event->contact->last_name) }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-muted small">Unknown</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $eventBadge }}">
                                                @if($event->event_type === 'open') <i class="bi bi-eye me-1"></i>
                                                @elseif($event->event_type === 'click') <i class="bi bi-cursor me-1"></i>
                                                @elseif($event->event_type === 'bounce') <i class="bi bi-exclamation-octagon me-1"></i>
                                                @elseif($event->event_type === 'unsubscribe') <i class="bi bi-person-dash me-1"></i>
                                                @elseif($event->event_type === 'complained') <i class="bi bi-flag me-1"></i>
                                                @endif
                                                {{ ucfirst($event->event_type) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($event->event_type === 'click' && $event->link_url)
                                                <a href="{{ $event->link_url }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="text-truncate d-inline-block small text-decoration-none"
                                                   style="max-width: 260px;"
                                                   title="{{ $event->link_url }}">
                                                    <i class="bi bi-box-arrow-up-right me-1 text-muted"></i>
                                                    {{ $event->link_url }}
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="pe-4">
                                            <span class="text-muted small" title="{{ $event->created_at->toDateTimeString() }}">
                                                {{ $event->created_at->diffForHumans() }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>{{-- /row --}}

@endsection

@push('scripts')
<script>
(function () {
    const ctx = document.getElementById('engagementDoughnut');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Opens', 'Clicks', 'Bounces', 'Unsubscribes'],
            datasets: [{
                data: [
                    {{ $stats['opens'] }},
                    {{ $stats['clicks'] }},
                    {{ $stats['bounces'] }},
                    {{ $stats['unsubscribes'] }}
                ],
                backgroundColor: [
                    'rgba(234,179,8,.75)',
                    'rgba(6,182,212,.75)',
                    'rgba(239,68,68,.75)',
                    'rgba(107,114,128,.75)'
                ],
                borderColor: [
                    'rgb(234,179,8)',
                    'rgb(6,182,212)',
                    'rgb(239,68,68)',
                    'rgb(107,114,128)'
                ],
                borderWidth: 1,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                            return ` ${ctx.label}: ${ctx.parsed.toLocaleString()} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
})();
</script>
@endpush
