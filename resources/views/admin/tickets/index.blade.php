@extends('layouts.admin')

@section('page-title', 'Support Tickets')

@section('page-content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Support Tickets</h1>
        <span class="badge bg-secondary fs-6">{{ $tickets->total() }} tickets</span>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.tickets.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="open" @selected(request('status') === 'open')>Open</option>
                        <option value="in_progress" @selected(request('status') === 'in_progress')>In Progress</option>
                        <option value="awaiting_reply" @selected(request('status') === 'awaiting_reply')>Awaiting Reply</option>
                        <option value="resolved" @selected(request('status') === 'resolved')>Resolved</option>
                        <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="priority" class="form-label">Priority</label>
                    <select name="priority" id="priority" class="form-select">
                        <option value="">All Priorities</option>
                        <option value="low" @selected(request('priority') === 'low')>Low</option>
                        <option value="medium" @selected(request('priority') === 'medium')>Medium</option>
                        <option value="high" @selected(request('priority') === 'high')>High</option>
                        <option value="urgent" @selected(request('priority') === 'urgent')>Urgent</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Subject or ticket #..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-outline-secondary flex-grow-1">Filter</button>
                    <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-danger">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tickets Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Submitted By</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Created</th>
                            <th>Last Reply</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                            <tr>
                                <td class="fw-bold">{{ $ticket->id }}</td>
                                <td>
                                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="text-decoration-none">
                                        {{ Str::limit($ticket->subject, 50) }}
                                    </a>
                                </td>
                                <td>{{ $ticket->user?->name ?? $ticket->email ?? '—' }}</td>
                                <td>
                                    @switch($ticket->priority)
                                        @case('urgent')
                                            <span class="badge bg-danger">Urgent</span>
                                            @break
                                        @case('high')
                                            <span class="badge bg-warning text-dark">High</span>
                                            @break
                                        @case('medium')
                                            <span class="badge bg-info text-dark">Medium</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">Low</span>
                                    @endswitch
                                </td>
                                <td>
                                    @switch($ticket->status)
                                        @case('open')
                                            <span class="badge bg-primary">Open</span>
                                            @break
                                        @case('in_progress')
                                            <span class="badge bg-info">In Progress</span>
                                            @break
                                        @case('awaiting_reply')
                                            <span class="badge bg-warning text-dark">Awaiting Reply</span>
                                            @break
                                        @case('resolved')
                                            <span class="badge bg-success">Resolved</span>
                                            @break
                                        @case('closed')
                                            <span class="badge bg-dark">Closed</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>{{ $ticket->assignee?->name ?? 'Unassigned' }}</td>
                                <td>{{ $ticket->created_at->format('M d, Y') }}</td>
                                <td>{{ $ticket->last_reply_at?->diffForHumans() ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No tickets found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($tickets->hasPages())
            <div class="card-footer">
                {{ $tickets->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
