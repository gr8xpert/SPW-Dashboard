@extends('layouts.client')

@section('title', 'Widget Status')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-window-stack me-2 text-primary"></i>Widget Status</h4>
        <p class="text-muted mb-0">Overview of your property search widget subscription and configuration</p>
    </div>
    <a href="{{ route('dashboard.widget.setup') }}" class="btn btn-primary">
        <i class="bi bi-gear-wide-connected me-1"></i> Setup Widget
    </a>
</div>

{{-- Subscription Card --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom pt-4 pb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-gem me-2 text-primary"></i>Subscription Details</h6>
    </div>
    <div class="card-body p-4">
        <div class="row align-items-center g-4">
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                        <i class="bi bi-gem fs-3 text-primary"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4">{{ $subscription->plan->name ?? 'No Plan' }}</div>
                        @php
                            $statusColors = [
                                'active'    => 'success',
                                'trialing'  => 'info',
                                'expired'   => 'danger',
                                'cancelled' => 'secondary',
                            ];
                            $statusColor = $statusColors[$subscription->status ?? 'expired'] ?? 'secondary';
                        @endphp
                        <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} border border-{{ $statusColor }} border-opacity-25">
                            {{ ucfirst($subscription->status ?? 'Inactive') }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small mb-1">Registered Domain</div>
                <div class="fw-semibold">
                    <i class="bi bi-globe me-1 text-primary"></i>
                    {{ $subscription->domain ?? 'Not configured' }}
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small mb-1">Renewal Date</div>
                <div class="fw-semibold">
                    <i class="bi bi-calendar-event me-1 text-primary"></i>
                    {{ isset($subscription->expires_at) ? $subscription->expires_at->format('M d, Y') : 'N/A' }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Plan Features --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-list-check me-2 text-primary"></i>Plan Features</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    @forelse($features ?? [] as $feature)
                        <div class="col-md-6">
                            <div class="d-flex align-items-start gap-2">
                                @if($feature['enabled'] ?? true)
                                    <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0" style="font-size: 13px;"></i>
                                @else
                                    <i class="bi bi-x-circle-fill text-muted mt-1 flex-shrink-0" style="font-size: 13px;"></i>
                                @endif
                                <span class="small {{ !($feature['enabled'] ?? true) ? 'text-muted' : '' }}">
                                    {{ $feature['name'] }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-3">
                            <i class="bi bi-list-check fs-1 d-block mb-2 opacity-25"></i>
                            <p class="small mb-0">No features available for your current plan.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-primary"></i>Quick Stats (This Month)</h6>
            </div>
            <div class="card-body p-4">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted small">Property Searches</span>
                        <span class="fw-bold">{{ number_format($stats['searches'] ?? 0) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted small">Property Views</span>
                        <span class="fw-bold">{{ number_format($stats['views'] ?? 0) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted small">Inquiries Received</span>
                        <span class="fw-bold">{{ number_format($stats['inquiries'] ?? 0) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted small">AI Searches Used</span>
                        <span class="fw-bold">{{ number_format($stats['ai_searches'] ?? 0) }}</span>
                    </li>
                </ul>

                <div class="mt-3">
                    <a href="{{ route('dashboard.widget.analytics') }}" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-graph-up me-1"></i> View Full Analytics
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
