@extends('layouts.client')

@section('title', 'Automation — Smart Property Management')

@section('page-content')

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-robot me-2 text-primary"></i>{{ $automation->name }}
        </h4>
        <p class="text-muted mb-0">Automation details</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard.automations.edit', $automation) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="{{ route('dashboard.automations.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                @php
                    $triggerLabels = [
                        'contact_added'    => 'Contact Added',
                        'tag_added'        => 'Tag Added',
                        'contact_updated'  => 'Contact Updated',
                        'date_field'       => 'Date Field',
                        'manual'           => 'Manual',
                        'engagement_drop'  => 'Engagement Drop',
                    ];
                    $statusBadge = match($automation->status) {
                        'active' => 'success',
                        'paused' => 'warning',
                        default  => 'secondary',
                    };
                @endphp

                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted fw-medium">Name</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $automation->name }}</dd>

                    <dt class="col-sm-4 text-muted fw-medium">Trigger</dt>
                    <dd class="col-sm-8">
                        {{ $triggerLabels[$automation->trigger_type] ?? ucfirst(str_replace('_', ' ', $automation->trigger_type)) }}
                    </dd>

                    <dt class="col-sm-4 text-muted fw-medium">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-{{ $statusBadge }} bg-opacity-10 text-{{ $statusBadge }} border border-{{ $statusBadge }} border-opacity-25">
                            {{ ucfirst($automation->status) }}
                        </span>
                    </dd>

                    <dt class="col-sm-4 text-muted fw-medium">Created</dt>
                    <dd class="col-sm-8 text-muted small">
                        {{ $automation->created_at->format('M j, Y \a\t g:i A') }}
                    </dd>
                </dl>

            </div>
        </div>
    </div>
</div>

@endsection
