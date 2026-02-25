@extends('layouts.client')

@section('title', 'My Tickets')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-ticket-detailed me-2 text-primary"></i>My Tickets</h4>
        <p class="text-muted mb-0">View and manage your support requests</p>
    </div>
    <a href="{{ route('dashboard.tickets.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Ticket
    </a>
</div>

{{-- Status Filter Tabs --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <ul class="nav nav-pills nav-fill gap-2">
            @php
                $currentStatus = request('status', 'all');
                $statusCounts = $statusCounts ?? [];
            @endphp
            <li class="nav-item">
                <a class="nav-link {{ $currentStatus === 'all' ? 'active' : '' }}"
                   href="{{ route('dashboard.tickets.index') }}">
                    All <span class="badge bg-white text-dark ms-1">{{ $statusCounts['all'] ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $currentStatus === 'open' ? 'active' : '' }}"
                   href="{{ route('dashboard.tickets.index', ['status' => 'open']) }}">
                    Open <span class="badge bg-white text-dark ms-1">{{ $statusCounts['open'] ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $currentStatus === 'in_progress' ? 'active' : '' }}"
                   href="{{ route('dashboard.tickets.index', ['status' => 'in_progress']) }}">
                    In Progress <span class="badge bg-white text-dark ms-1">{{ $statusCounts['in_progress'] ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $currentStatus === 'awaiting_reply' ? 'active' : '' }}"
                   href="{{ route('dashboard.tickets.index', ['status' => 'awaiting_reply']) }}">
                    Awaiting Reply <span class="badge bg-white text-dark ms-1">{{ $statusCounts['awaiting_reply'] ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $currentStatus === 'closed' ? 'active' : '' }}"
                   href="{{ route('dashboard.tickets.index', ['status' => 'closed']) }}">
                    Closed <span class="badge bg-white text-dark ms-1">{{ $statusCounts['closed'] ?? 0 }}</span>
                </a>
            </li>
        </ul>
    </div>
</div>

{{-- Tickets Table --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th width="80">#</th>
                    <th>Subject</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                    <th width="80">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets ?? [] as $ticket)
                    <tr>
                        <td class="text-muted fw-medium">#{{ $ticket->id }}</td>
                        <td>
                            <a href="{{ route('dashboard.tickets.show', $ticket) }}" class="text-decoration-none fw-medium">
                                {{ $ticket->subject }}
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ ucfirst($ticket->category ?? 'General') }}</span>
                        </td>
                        <td>
                            @php
                                $priorityColors = [
                                    'low'      => 'secondary',
                                    'medium'   => 'info',
                                    'high'     => 'warning',
                                    'urgent'   => 'danger',
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
                        <td class="text-muted small">{{ $ticket->updated_at->diffForHumans() }}</td>
                        <td>
                            <a href="{{ route('dashboard.tickets.show', $ticket) }}"
                               class="btn btn-sm btn-outline-primary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-ticket-detailed fs-1 d-block mb-2 opacity-25"></i>
                            <p class="small mb-2">You haven't created any tickets yet.</p>
                            <a href="{{ route('dashboard.tickets.create') }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg me-1"></i> Create Your First Ticket
                            </a>
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
