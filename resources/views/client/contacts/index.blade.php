@extends('layouts.client')

@section('title', 'Contacts')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-people me-2 text-primary"></i>Contacts</h4>
        <p class="text-muted mb-0">Manage your email contacts and subscribers</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-upload me-1"></i> Import CSV
        </button>
        <a href="{{ route('dashboard.contacts.export') }}" class="btn btn-outline-secondary">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
        <a href="{{ route('dashboard.contacts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Contact
        </a>
    </div>
</div>

{{-- Stats Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-people fs-4 text-primary"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ number_format($total) }}</div>
                    <div class="text-muted small">Total Contacts</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3">
                    <i class="bi bi-check-circle fs-4 text-success"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ number_format($subscribed) }}</div>
                    <div class="text-muted small">Subscribed</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-x-circle fs-4 text-warning"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ number_format($unsubscribed) }}</div>
                    <div class="text-muted small">Unsubscribed</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('dashboard.contacts.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0"
                           placeholder="Search by email, name, company..."
                           value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="subscribed"   {{ request('status') === 'subscribed'   ? 'selected' : '' }}>Subscribed</option>
                    <option value="unsubscribed" {{ request('status') === 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
                    <option value="bounced"      {{ request('status') === 'bounced'      ? 'selected' : '' }}>Bounced</option>
                    <option value="complained"   {{ request('status') === 'complained'   ? 'selected' : '' }}>Complained</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="list" class="form-select">
                    <option value="">All Lists</option>
                    @foreach($lists as $list)
                        <option value="{{ $list->id }}" {{ request('list') == $list->id ? 'selected' : '' }}>
                            {{ $list->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

{{-- Bulk Actions --}}
<form method="POST" action="{{ route('dashboard.contacts.bulk-action') }}" id="bulkForm">
    @csrf

    <div id="bulkToolbar" class="card border-0 shadow-sm mb-3 d-none">
        <div class="card-body py-2 d-flex align-items-center gap-3">
            <span class="text-muted small" id="selectedCount">0 selected</span>
            <select name="action" class="form-select form-select-sm w-auto">
                <option value="">Bulk Action</option>
                <option value="subscribe">Mark Subscribed</option>
                <option value="unsubscribe">Mark Unsubscribed</option>
                <option value="add_to_list">Add to List</option>
                <option value="delete">Delete</option>
            </select>
            <select name="list_id" class="form-select form-select-sm w-auto" id="bulkListSelect" style="display:none;">
                <option value="">Select List...</option>
                @foreach($lists as $list)
                    <option value="{{ $list->id }}">{{ $list->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-danger"
                    onclick="return confirm('Apply bulk action to selected contacts?')">
                Apply
            </button>
        </div>
    </div>

    {{-- Contacts Table --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="40">
                            <input type="checkbox" class="form-check-input" id="selectAll">
                        </th>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Lists</th>
                        <th>Added</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contacts as $contact)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input contact-checkbox"
                                       name="ids[]" value="{{ $contact->id }}">
                            </td>
                            <td>
                                <a href="{{ route('dashboard.contacts.show', $contact) }}"
                                   class="text-decoration-none fw-medium">
                                    {{ $contact->email }}
                                </a>
                            </td>
                            <td>
                                {{ trim($contact->first_name . ' ' . $contact->last_name) ?: '—' }}
                            </td>
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
                            <td>
                                @if($contact->lists && $contact->lists->count())
                                    @foreach($contact->lists->take(2) as $list)
                                        <span class="badge bg-light text-dark border me-1">{{ $list->name }}</span>
                                    @endforeach
                                    @if($contact->lists->count() > 2)
                                        <span class="text-muted small">+{{ $contact->lists->count() - 2 }} more</span>
                                    @endif
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $contact->created_at->format('M d, Y') }}</td>
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
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-contact-btn" title="Delete"
                                            data-url="{{ route('dashboard.contacts.destroy', $contact) }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                                No contacts found.
                                <a href="{{ route('dashboard.contacts.create') }}">Add your first contact</a>
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
</form>

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('dashboard.contacts.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import Contacts from CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info border-0 bg-info bg-opacity-10">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>CSV Format:</strong> Your file must include an <code>email</code> column.
                        Optional: <code>first_name</code>, <code>last_name</code>, <code>phone</code>, <code>company</code>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                        <div class="form-text">Max file size: 10MB</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Select All checkbox
    document.getElementById('selectAll').addEventListener('change', function () {
        document.querySelectorAll('.contact-checkbox').forEach(cb => cb.checked = this.checked);
        updateBulkToolbar();
    });

    document.querySelectorAll('.contact-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkToolbar);
    });

    function updateBulkToolbar() {
        const checked = document.querySelectorAll('.contact-checkbox:checked');
        const toolbar = document.getElementById('bulkToolbar');
        const countLabel = document.getElementById('selectedCount');

        if (checked.length > 0) {
            toolbar.classList.remove('d-none');
            countLabel.textContent = checked.length + ' selected';
        } else {
            toolbar.classList.add('d-none');
        }
    }

    // Show list selector when "Add to List" is chosen
    document.querySelector('[name="action"]').addEventListener('change', function () {
        const listSelect = document.getElementById('bulkListSelect');
        listSelect.style.display = this.value === 'add_to_list' ? '' : 'none';
    });

    // Individual delete buttons (outside nested form issue)
    document.querySelectorAll('.delete-contact-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!confirm('Delete this contact?')) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.dataset.url;
            form.innerHTML = `@csrf <input type="hidden" name="_method" value="DELETE">`;
            document.body.appendChild(form);
            form.submit();
        });
    });
</script>
@endpush
@endsection
