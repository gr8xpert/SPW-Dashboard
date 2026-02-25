@extends('layouts.client')

@section('title', 'Inquiry Contacts')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Inquiry Contacts</h4>
        <p class="text-muted mb-0">People who submitted inquiries through your property widget</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard.widget.inquiry-contacts.export') }}" class="btn btn-outline-secondary">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('dashboard.widget.inquiry-contacts') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0"
                           placeholder="Search by name, email, phone..."
                           value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="new"       {{ request('status') === 'new'       ? 'selected' : '' }}>New</option>
                    <option value="contacted"  {{ request('status') === 'contacted'  ? 'selected' : '' }}>Contacted</option>
                    <option value="converted"  {{ request('status') === 'converted'  ? 'selected' : '' }}>Converted</option>
                    <option value="archived"   {{ request('status') === 'archived'   ? 'selected' : '' }}>Archived</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="date_from" class="form-control"
                       value="{{ request('date_from') }}" placeholder="From date">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Contacts Table --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Property</th>
                    <th>Status</th>
                    <th>Received</th>
                    <th width="100">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts ?? [] as $contact)
                    <tr>
                        <td class="fw-medium">
                            {{ $contact->name ?: '(No name)' }}
                        </td>
                        <td>
                            <a href="mailto:{{ $contact->email }}" class="text-decoration-none">
                                {{ $contact->email }}
                            </a>
                        </td>
                        <td>{{ $contact->phone ?: '--' }}</td>
                        <td>
                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $contact->property_address ?? '' }}">
                                {{ $contact->property_address ?? '--' }}
                            </span>
                        </td>
                        <td>
                            @php
                                $badges = [
                                    'new'       => 'primary',
                                    'contacted' => 'info',
                                    'converted' => 'success',
                                    'archived'  => 'secondary',
                                ];
                                $badge = $badges[$contact->status ?? 'new'] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $badge }} bg-opacity-10 text-{{ $badge }} border border-{{ $badge }} border-opacity-25">
                                {{ ucfirst($contact->status ?? 'New') }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $contact->created_at->format('M d, Y H:i') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#inquiryModal{{ $contact->id }}"
                                        title="View Details">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <form method="POST" action="{{ route('dashboard.widget.inquiry-contacts.update-status', $contact) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto; font-size: .75rem;">
                                        <option value="new"       {{ ($contact->status ?? 'new') === 'new'       ? 'selected' : '' }}>New</option>
                                        <option value="contacted"  {{ ($contact->status ?? '') === 'contacted'     ? 'selected' : '' }}>Contacted</option>
                                        <option value="converted"  {{ ($contact->status ?? '') === 'converted'     ? 'selected' : '' }}>Converted</option>
                                        <option value="archived"   {{ ($contact->status ?? '') === 'archived'      ? 'selected' : '' }}>Archived</option>
                                    </select>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-person-lines-fill fs-1 d-block mb-2 opacity-25"></i>
                            No inquiry contacts found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($contacts) && $contacts->hasPages())
        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing {{ $contacts->firstItem() }}--{{ $contacts->lastItem() }}
                of {{ $contacts->total() }} contacts
            </small>
            {{ $contacts->links() }}
        </div>
    @endif
</div>

{{-- Inquiry Detail Modals --}}
@foreach($contacts ?? [] as $contact)
<div class="modal fade" id="inquiryModal{{ $contact->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-envelope-paper me-2"></i>Inquiry from {{ $contact->name ?: $contact->email }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Name</dt>
                    <dd class="col-sm-8">{{ $contact->name ?: '--' }}</dd>

                    <dt class="col-sm-4 text-muted">Email</dt>
                    <dd class="col-sm-8">{{ $contact->email }}</dd>

                    <dt class="col-sm-4 text-muted">Phone</dt>
                    <dd class="col-sm-8">{{ $contact->phone ?: '--' }}</dd>

                    <dt class="col-sm-4 text-muted">Property</dt>
                    <dd class="col-sm-8">{{ $contact->property_address ?? '--' }}</dd>

                    <dt class="col-sm-4 text-muted">Message</dt>
                    <dd class="col-sm-8">{{ $contact->message ?? '--' }}</dd>

                    <dt class="col-sm-4 text-muted">Received</dt>
                    <dd class="col-sm-8">{{ $contact->created_at->format('M d, Y H:i') }}</dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <a href="mailto:{{ $contact->email }}" class="btn btn-primary">
                    <i class="bi bi-envelope me-1"></i> Reply by Email
                </a>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection
