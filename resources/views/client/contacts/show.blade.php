@extends('layouts.client')

@section('title', 'Contact: ' . $contact->email)

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-person-circle me-2 text-primary"></i>Contact Profile</h4>
        <p class="text-muted mb-0">{{ $contact->email }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard.contacts.edit', $contact) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="{{ route('dashboard.contacts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-4">
    {{-- Contact Details --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:72px;height:72px;">
                    <i class="bi bi-person fs-1 text-primary"></i>
                </div>
                <h5 class="mb-1">
                    {{ trim($contact->first_name . ' ' . $contact->last_name) ?: 'No Name' }}
                </h5>
                <p class="text-muted mb-2">{{ $contact->email }}</p>
                @php
                    $badges = [
                        'subscribed'   => 'success',
                        'unsubscribed' => 'warning',
                        'bounced'      => 'danger',
                        'complained'   => 'dark',
                    ];
                    $badge = $badges[$contact->status] ?? 'secondary';
                @endphp
                <span class="badge bg-{{ $badge }} bg-opacity-10 text-{{ $badge }} border border-{{ $badge }} border-opacity-25 fs-6 px-3 py-2">
                    {{ ucfirst($contact->status) }}
                </span>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-medium border-bottom">
                <i class="bi bi-info-circle me-2 text-muted"></i>Details
            </div>
            <div class="card-body p-0">
                <dl class="mb-0">
                    @if($contact->phone)
                    <div class="d-flex align-items-center px-4 py-3 border-bottom">
                        <dt class="text-muted small me-auto" style="min-width:90px;">Phone</dt>
                        <dd class="mb-0 text-end">{{ $contact->phone }}</dd>
                    </div>
                    @endif
                    @if($contact->company)
                    <div class="d-flex align-items-center px-4 py-3 border-bottom">
                        <dt class="text-muted small me-auto" style="min-width:90px;">Company</dt>
                        <dd class="mb-0 text-end">{{ $contact->company }}</dd>
                    </div>
                    @endif
                    <div class="d-flex align-items-center px-4 py-3 border-bottom">
                        <dt class="text-muted small me-auto" style="min-width:90px;">Added</dt>
                        <dd class="mb-0 text-end">{{ $contact->created_at->format('M d, Y') }}</dd>
                    </div>
                    <div class="d-flex align-items-center px-4 py-3">
                        <dt class="text-muted small me-auto" style="min-width:90px;">Updated</dt>
                        <dd class="mb-0 text-end">{{ $contact->updated_at->format('M d, Y') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        @if($contact->tags && count($contact->tags))
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-medium border-bottom">
                <i class="bi bi-tags me-2 text-muted"></i>Tags
            </div>
            <div class="card-body">
                @foreach($contact->tags as $tag)
                    <span class="badge bg-light text-dark border me-1 mb-1 px-2 py-1">
                        <i class="bi bi-tag me-1"></i>{{ $tag }}
                    </span>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Right Column --}}
    <div class="col-lg-8">
        {{-- List Memberships --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <span class="fw-medium"><i class="bi bi-collection me-2 text-muted"></i>List Memberships</span>
                <span class="badge bg-primary rounded-pill">{{ $contact->lists->count() }}</span>
            </div>
            <div class="card-body">
                @if($contact->lists->count())
                    <div class="row g-2">
                        @foreach($contact->lists as $list)
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center p-3 rounded-3 bg-light">
                                    <i class="bi bi-collection text-primary me-2"></i>
                                    <div>
                                        <div class="fw-medium small">{{ $list->name }}</div>
                                        @if($list->pivot && $list->pivot->added_at)
                                            <div class="text-muted" style="font-size:0.75rem;">
                                                Added {{ \Carbon\Carbon::parse($list->pivot->added_at)->format('M d, Y') }}
                                            </div>
                                        @endif
                                    </div>
                                    <a href="{{ route('dashboard.lists.show', $list) }}"
                                       class="btn btn-sm btn-outline-secondary ms-auto">
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">This contact is not in any lists.
                        <a href="{{ route('dashboard.contacts.edit', $contact) }}">Add to a list</a>
                    </p>
                @endif
            </div>
        </div>

        {{-- Email History --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-medium border-bottom">
                <i class="bi bi-clock-history me-2 text-muted"></i>Email Activity History
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Campaign</th>
                            <th>Event</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($emailHistory as $event)
                            <tr>
                                <td>
                                    <span class="fw-medium">
                                        {{ $event->campaign->name ?? 'Unknown Campaign' }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $eventBadges = [
                                            'sent'        => ['bg-secondary', 'bi-send'],
                                            'delivered'   => ['bg-info',      'bi-inbox'],
                                            'opened'      => ['bg-success',   'bi-eye'],
                                            'clicked'     => ['bg-primary',   'bi-cursor'],
                                            'bounced'     => ['bg-danger',    'bi-exclamation-triangle'],
                                            'complained'  => ['bg-dark',      'bi-flag'],
                                            'unsubscribed'=> ['bg-warning',   'bi-x-circle'],
                                        ];
                                        [$bg, $icon] = $eventBadges[$event->event_type] ?? ['bg-secondary', 'bi-circle'];
                                    @endphp
                                    <span class="badge {{ $bg }} bg-opacity-10 text-{{ str_replace('bg-', '', $bg) }} border">
                                        <i class="bi {{ $icon }} me-1"></i>{{ ucfirst($event->event_type) }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ $event->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-4 d-block mb-1 opacity-25"></i>
                                    No email activity recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
