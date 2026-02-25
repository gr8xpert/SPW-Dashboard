@extends('layouts.client')

@section('title', 'Assigned Tickets')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-ticket-detailed me-2 text-primary"></i>Assigned Tickets</h4>
        <p class="text-muted mb-0">Tickets assigned to you for resolution</p>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-inbox fs-4 text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Open</div>
                    <div class="fw-bold fs-4">{{ $counts['open'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 p-3">
                    <i class="bi bi-gear-wide fs-4 text-info"></i>
                </div>
                <div>
                    <div class="text-muted small">In Progress</div>
                    <div class="fw-bold fs-4">{{ $counts['in_progress'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-hourglass-split fs-4 text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small">Awaiting Reply</div>
                    <div class="fw-bold fs-4">{{ $counts['awaiting_reply'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3">
                    <i class="bi bi-check-circle fs-4 text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Closed (This Month)</div>
                    <div class="fw-bold fs-4">{{ $counts['closed'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('webmaster.tickets.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0"
                           placeholder="Search by ticket #, subject, or client..."
                           value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="open"           {{ request('status') === 'open'           ? 'selected' : '' }}>Open</option>
                    <option value="in_progress"    {{ request('status') === 'in_progress'    ? 'selected' : '' }}>In Progress</option>
                    <option value="awaiting_reply" {{ request('status') === 'awaiting_reply' ? 'selected' : '' }}>Awaiting Reply</option>
                    <option value="closed"         {{ request('status') === 'closed'         ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="priority" class="form-select">
                    <option value="">All Priorities</option>
                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    <option value="high"   {{ request('priority') === 'high'   ? 'selected' : '' }}>High</option>
                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="low"    {{ request('priority') === 'low'    ? 'selected' : '' }}>Low</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="sort" class="form-select">
                    <option value="latest"        {{ request('sort', 'latest') === 'latest'        ? 'selected' : '' }}>Newest First</option>
                    <option value="oldest"        {{ request('sort') === 'oldest'                  ? 'selected' : '' }}>Oldest First</option>
                    <option value="priority_desc" {{ request('sort') === 'priority_desc'           ? 'selected' : '' }}>Priority (High-Low)</option>
                    <option value="updated"       {{ request('sort') === 'updated'                 ? 'selected' : '' }}>Recently Updated</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Tickets Table --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th width="70">#</th>
                    <th>Subject</th>
                    <th>Client</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Hours Logged</th>
                    <th>Updated</th>
                    <th width="80">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets ?? [] as $ticket)
                    <tr class="{{ $ticket->priority === 'urgent' ? 'table-danger table-opacity-25' : '' }}">
                        <td class="text-muted fw-medium">#{{ $ticket->id }}</td>
                        <td>
                            <a href="{{ route('webmaster.tickets.show', $ticket) }}" class="text-decoration-none fw-medium">
                                {{ $ticket->subject }}
                            </a>
                        </td>
                        <td class="small">
                            <i class="bi bi-building me-1 text-muted"></i>
                            {{ $ticket->client->company_name ?? 'Unknown' }}
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $ticket->category ?? 'General')) }}</span>
                        </td>
                        <td>
                            @php
                                $priorityColors = [
                                    'low'    => 'secondary',
                                    'medium' => 'info',
                                    'high'   => 'warning',
                                    'urgent' => 'danger',
                                ];
                                $pColor = $priorityColors[$ticket->priority ?? 'medium'] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $pColor }} bg-opacity-10 text-{{ $pColor }} border border-{{ $pColor }} border-opacity-25">
                                {{ ucfirst($ticket->priority ?? 'Medium') }}
                            </span>
                        </td>
                        <td>
                            @php
                                $statusColors = [
                                    'open'            => 'primary',
                                    'in_progress'     => 'info',
                                    'awaiting_reply'  => 'warning',
                                    'closed'          => 'secondary',
                                ];
                                $sColor = $statusColors[$ticket->status ?? 'open'] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $sColor }} bg-opacity-10 text-{{ $sColor }} border border-{{ $sColor }} border-opacity-25">
                                {{ str_replace('_', ' ', ucfirst($ticket->status ?? 'Open')) }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ number_format($ticket->hours_logged ?? 0, 1) }}h</td>
                        <td class="text-muted small">{{ $ticket->updated_at->diffForHumans() }}</td>
                        <td>
                            <a href="{{ route('webmaster.tickets.show', $ticket) }}"
                               class="btn btn-sm btn-outline-primary" title="Work on ticket">
                                <i class="bi bi-wrench"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-ticket-detailed fs-1 d-block mb-2 opacity-25"></i>
                            <p class="small mb-0">No tickets assigned to you at the moment.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($tickets) && $tickets->hasPages())
        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing {{ $tickets->firstItem() }}--{{ $tickets->lastItem() }}
                of {{ $tickets->total() }} tickets
            </small>
            {{ $tickets->links() }}
        </div>
    @endif
</div>
@endsection
