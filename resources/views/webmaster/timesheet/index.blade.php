@extends('layouts.client')

@section('title', 'Timesheet')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Timesheet</h4>
        <p class="text-muted mb-0">Hours logged across all assigned tickets</p>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-calendar-week fs-4 text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">This Week</div>
                    <div class="fw-bold fs-4">{{ number_format($summary['this_week'] ?? 0, 1) }}h</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3">
                    <i class="bi bi-calendar-month fs-4 text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">This Month</div>
                    <div class="fw-bold fs-4">{{ number_format($summary['this_month'] ?? 0, 1) }}h</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 p-3">
                    <i class="bi bi-ticket-detailed fs-4 text-info"></i>
                </div>
                <div>
                    <div class="text-muted small">Active Tickets</div>
                    <div class="fw-bold fs-4">{{ $summary['active_tickets'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-bar-chart fs-4 text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small">All Time</div>
                    <div class="fw-bold fs-4">{{ number_format($summary['all_time'] ?? 0, 1) }}h</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('webmaster.timesheet.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-medium mb-1">Date From</label>
                <input type="date" name="date_from" class="form-control"
                       value="{{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-medium mb-1">Date To</label>
                <input type="date" name="date_to" class="form-control"
                       value="{{ request('date_to', now()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-medium mb-1">Client</label>
                <select name="client_id" class="form-select">
                    <option value="">All Clients</option>
                    @foreach($clients ?? [] as $client)
                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->company_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Per-Client Summary --}}
@if(!empty($clientSummary ?? []))
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom pt-4 pb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-building me-2 text-primary"></i>Hours by Client</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Client</th>
                    <th class="text-end">Hours (Filtered Period)</th>
                    <th class="text-end">Tickets Worked</th>
                    <th class="text-end">Credit Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clientSummary as $cs)
                    <tr>
                        <td class="fw-medium">
                            <i class="bi bi-building me-1 text-muted"></i>
                            {{ $cs['company_name'] }}
                        </td>
                        <td class="text-end fw-bold">{{ number_format($cs['hours'], 1) }}h</td>
                        <td class="text-end">{{ $cs['tickets_count'] }}</td>
                        <td class="text-end {{ $cs['credit_balance'] <= 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($cs['credit_balance'], 1) }}h
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Time Entries Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul me-2 text-primary"></i>Time Entries</h6>
        @if(isset($entries) && $entries->count())
            <span class="text-muted small">
                Total: <strong>{{ number_format($entries->sum('hours'), 1) }}h</strong>
            </span>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Ticket</th>
                    <th>Description</th>
                    <th class="text-end">Hours</th>
                    <th width="80">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries ?? [] as $entry)
                    <tr>
                        <td class="text-muted small">{{ $entry->date->format('M d, Y') }}</td>
                        <td class="small">{{ $entry->ticket->client->company_name ?? 'Unknown' }}</td>
                        <td>
                            <a href="{{ route('webmaster.tickets.show', $entry->ticket_id) }}" class="text-decoration-none small">
                                #{{ $entry->ticket_id }}
                            </a>
                        </td>
                        <td class="small">{{ $entry->description }}</td>
                        <td class="text-end fw-bold">{{ number_format($entry->hours, 1) }}h</td>
                        <td>
                            <div class="d-flex gap-1">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editEntryModal{{ $entry->id }}"
                                        title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('webmaster.timesheet.destroy', $entry) }}"
                                      onsubmit="return confirm('Delete this time entry?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-clock-history fs-1 d-block mb-2 opacity-25"></i>
                            <p class="small mb-0">No time entries found for the selected period.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($entries) && $entries->hasPages())
        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing {{ $entries->firstItem() }}--{{ $entries->lastItem() }}
                of {{ $entries->total() }} entries
            </small>
            {{ $entries->links() }}
        </div>
    @endif
</div>

{{-- Edit Entry Modals --}}
@foreach($entries ?? [] as $entry)
<div class="modal fade" id="editEntryModal{{ $entry->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('webmaster.timesheet.update', $entry) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Time Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ $entry->date->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Hours</label>
                        <input type="number" name="hours" class="form-control"
                               min="0.1" max="24" step="0.1"
                               value="{{ $entry->hours }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Description</label>
                        <input type="text" name="description" class="form-control"
                               value="{{ $entry->description }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
