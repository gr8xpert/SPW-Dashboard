@extends('layouts.client')

@section('title', $list->name)

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold">
            <i class="bi bi-collection me-2 text-primary"></i>{{ $list->name }}
        </h4>
        @if($list->description)
            <p class="text-muted mb-0">{{ $list->description }}</p>
        @else
            <p class="text-muted mb-0">Contact List</p>
        @endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard.lists.edit', $list) }}" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i> Edit List
        </a>
        <a href="{{ route('dashboard.lists.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> All Lists
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-3 fw-bold text-primary">{{ number_format($contacts->total()) }}</div>
            <div class="text-muted small">Total Contacts</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-3 fw-bold text-success">
                {{ number_format($contacts->where('status', 'subscribed')->count()) }}
            </div>
            <div class="text-muted small">Subscribed</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-3 fw-bold text-warning">
                {{ number_format($contacts->where('status', 'unsubscribed')->count()) }}
            </div>
            <div class="text-muted small">Unsubscribed</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-3 fw-bold text-muted">{{ $list->created_at->format('M Y') }}</div>
            <div class="text-muted small">Created</div>
        </div>
    </div>
</div>

{{-- Contacts Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
        <span class="fw-medium"><i class="bi bi-people me-2 text-muted"></i>Contacts in this List</span>
        <a href="{{ route('dashboard.contacts.index', ['list' => $list->id]) }}"
           class="btn btn-sm btn-outline-primary">
            <i class="bi bi-box-arrow-up-right me-1"></i> View in Contacts
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Status</th>
                    <th>Added to List</th>
                    <th width="100">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts as $contact)
                    <tr>
                        <td>
                            <a href="{{ route('dashboard.contacts.show', $contact) }}"
                               class="text-decoration-none fw-medium">
                                {{ $contact->email }}
                            </a>
                        </td>
                        <td>{{ trim($contact->first_name . ' ' . $contact->last_name) ?: '—' }}</td>
                        <td>{{ $contact->company ?: '—' }}</td>
                        <td>
                            @php
                                $badges = [
                                    'subscribed'   => 'success',
                                    'unsubscribed' => 'warning',
                                    'bounced'      => 'danger',
                                    'complained'   => 'dark',
                                ];
                                $badge = $badges[$contact->status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $badge }} bg-opacity-10 text-{{ $badge }} border border-{{ $badge }} border-opacity-25">
                                {{ ucfirst($contact->status) }}
                            </span>
                        </td>
                        <td class="text-muted small">
                            @if($contact->pivot && $contact->pivot->added_at)
                                {{ \Carbon\Carbon::parse($contact->pivot->added_at)->format('M d, Y') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('dashboard.contacts.show', $contact) }}"
                                   class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('dashboard.contacts.edit', $contact) }}"
                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                            No contacts in this list yet.
                            <a href="{{ route('dashboard.contacts.index') }}">Import or add contacts</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($contacts->hasPages())
        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing {{ $contacts->firstItem() }}–{{ $contacts->lastItem() }}
                of {{ $contacts->total() }} contacts
            </small>
            {{ $contacts->links() }}
        </div>
    @endif
</div>
@endsection
