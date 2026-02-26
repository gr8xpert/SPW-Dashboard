@extends('layouts.client')

@section('title', 'Contact Analytics — Smart Property Management')

@section('page-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <div class="mb-1">
            <a href="{{ route('dashboard.analytics.index') }}" class="text-muted text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i> Back to Analytics Overview
            </a>
        </div>
        <h4 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>Contact Analytics</h4>
        <p class="text-muted mb-0 mt-1">Audience health and engagement breakdown</p>
    </div>
</div>

{{-- Status Breakdown Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3">
                    <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Subscribed</div>
                    <div class="fw-bold fs-4">
                        {{ number_format($byStatus['subscribed']->count ?? 0) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-person-dash-fill fs-4 text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small">Unsubscribed</div>
                    <div class="fw-bold fs-4">
                        {{ number_format($byStatus['unsubscribed']->count ?? 0) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-danger bg-opacity-10 p-3">
                    <i class="bi bi-exclamation-octagon-fill fs-4 text-danger"></i>
                </div>
                <div>
                    <div class="text-muted small">Bounced</div>
                    <div class="fw-bold fs-4">
                        {{ number_format($byStatus['bounced']->count ?? 0) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-dark bg-opacity-10 p-3">
                    <i class="bi bi-flag-fill fs-4 text-dark"></i>
                </div>
                <div>
                    <div class="text-muted small">Complained</div>
                    <div class="fw-bold fs-4">
                        {{ number_format($byStatus['complained']->count ?? 0) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Top Engaged Contacts Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pt-3">
        <h6 class="fw-semibold mb-0">
            <i class="bi bi-star-fill me-2 text-warning"></i>Top Engaged Contacts
        </h6>
        <p class="text-muted small mb-0 mt-1">Contacts ranked by lead score and engagement tier</p>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name / Email</th>
                    <th class="text-end">Lead Score</th>
                    <th>Engagement Tier</th>
                    <th width="100">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topEngaged as $contact)
                    @php
                        $tierMap = [
                            'hot'  => 'danger',
                            'warm' => 'warning',
                            'cold' => 'info',
                        ];
                        $tierColor = $tierMap[$contact->engagement_tier] ?? 'secondary';
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-medium">
                                {{ trim($contact->first_name . ' ' . $contact->last_name) ?: '—' }}
                            </div>
                            <div class="text-muted small">{{ $contact->email }}</div>
                        </td>
                        <td class="text-end">
                            <span class="fw-bold">{{ number_format($contact->lead_score) }}</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $tierColor }} bg-opacity-15 text-{{ $tierColor }} border border-{{ $tierColor }} border-opacity-25 text-capitalize">
                                @if($contact->engagement_tier === 'hot')
                                    <i class="bi bi-fire me-1"></i>
                                @elseif($contact->engagement_tier === 'warm')
                                    <i class="bi bi-thermometer-half me-1"></i>
                                @else
                                    <i class="bi bi-snow me-1"></i>
                                @endif
                                {{ ucfirst($contact->engagement_tier ?? 'cold') }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('dashboard.contacts.show', $contact) }}"
                               class="btn btn-sm btn-outline-secondary" title="View Contact">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                            No engagement data available yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
