@extends('layouts.admin')

@section('page-title', 'Ticket #' . $ticket->id)

@section('page-content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.tickets.index') }}" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left"></i> Back to Tickets
            </a>
            <h1 class="h3 mb-0 mt-1">Ticket #{{ $ticket->id }}: {{ $ticket->subject }}</h1>
        </div>
        <div class="d-flex gap-2">
            @switch($ticket->priority)
                @case('urgent')
                    <span class="badge bg-danger fs-6">Urgent</span>
                    @break
                @case('high')
                    <span class="badge bg-warning text-dark fs-6">High</span>
                    @break
                @case('medium')
                    <span class="badge bg-info text-dark fs-6">Medium</span>
                    @break
                @default
                    <span class="badge bg-secondary fs-6">Low</span>
            @endswitch

            @switch($ticket->status)
                @case('open')
                    <span class="badge bg-primary fs-6">Open</span>
                    @break
                @case('in_progress')
                    <span class="badge bg-info fs-6">In Progress</span>
                    @break
                @case('awaiting_reply')
                    <span class="badge bg-warning text-dark fs-6">Awaiting Reply</span>
                    @break
                @case('resolved')
                    <span class="badge bg-success fs-6">Resolved</span>
                    @break
                @case('closed')
                    <span class="badge bg-dark fs-6">Closed</span>
                    @break
            @endswitch
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- Ticket Messages --}}
        <div class="col-lg-8">
            {{-- Original Message --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <div>
                        <strong>{{ $ticket->user?->name ?? $ticket->email ?? 'Unknown' }}</strong>
                        <span class="text-muted small ms-2">{{ $ticket->created_at->format('M d, Y g:i A') }}</span>
                    </div>
                    <span class="badge bg-light text-dark">Original</span>
                </div>
                <div class="card-body">
                    <div class="ticket-message">{!! nl2br(e($ticket->body)) !!}</div>
                </div>
            </div>

            {{-- Replies --}}
            @foreach($ticket->messages as $message)
                <div class="card mb-3 {{ $message->is_admin ? 'border-primary' : '' }}">
                    <div class="card-header d-flex justify-content-between {{ $message->is_admin ? 'bg-primary bg-opacity-10' : '' }}">
                        <div>
                            <strong>{{ $message->user?->name ?? 'Unknown' }}</strong>
                            @if($message->is_admin)
                                <span class="badge bg-primary ms-1">Staff</span>
                            @endif
                            <span class="text-muted small ms-2">{{ $message->created_at->format('M d, Y g:i A') }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="ticket-message">{!! nl2br(e($message->body)) !!}</div>
                    </div>
                </div>
            @endforeach

            {{-- Reply Form --}}
            @if($ticket->status !== 'closed')
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Post Reply</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}">
                            @csrf
                            <div class="mb-3">
                                <textarea class="form-control" name="body" rows="5" required
                                          placeholder="Type your reply...">{{ old('body') }}</textarea>
                                @error('body')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-reply"></i> Send Reply
                                </button>
                                <button type="submit" name="close_after" value="1" class="btn btn-outline-success">
                                    <i class="bi bi-check-lg"></i> Reply & Resolve
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-secondary text-center">
                    This ticket is closed. <a href="#" onclick="document.getElementById('reopen-form').submit(); return false;">Reopen it</a> to reply.
                    <form id="reopen-form" method="POST" action="{{ route('admin.tickets.reopen', $ticket) }}" class="d-none">
                        @csrf
                        @method('PATCH')
                    </form>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Ticket Details --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ticket Details</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Submitted By</dt>
                        <dd class="col-sm-7">{{ $ticket->user?->name ?? $ticket->email ?? '—' }}</dd>

                        <dt class="col-sm-5">Email</dt>
                        <dd class="col-sm-7">{{ $ticket->user?->email ?? $ticket->email ?? '—' }}</dd>

                        <dt class="col-sm-5">Created</dt>
                        <dd class="col-sm-7">{{ $ticket->created_at->format('M d, Y') }}</dd>

                        <dt class="col-sm-5">Last Reply</dt>
                        <dd class="col-sm-7">{{ $ticket->last_reply_at?->diffForHumans() ?? '—' }}</dd>

                        <dt class="col-sm-5">Assigned To</dt>
                        <dd class="col-sm-7">{{ $ticket->assignee?->name ?? 'Unassigned' }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Assign Form --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Assign Ticket</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.tickets.assign', $ticket) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <select name="assigned_to" class="form-select" required>
                                <option value="">Select assignee...</option>
                                @foreach($admins as $admin)
                                    <option value="{{ $admin->id }}" @selected($ticket->assigned_to == $admin->id)>
                                        {{ $admin->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-person-check"></i> Assign
                        </button>
                    </form>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body d-grid gap-2">
                    {{-- Change Priority --}}
                    <form method="POST" action="{{ route('admin.tickets.update-priority', $ticket) }}" class="d-flex gap-2">
                        @csrf
                        @method('PATCH')
                        <select name="priority" class="form-select form-select-sm">
                            <option value="low" @selected($ticket->priority === 'low')>Low</option>
                            <option value="medium" @selected($ticket->priority === 'medium')>Medium</option>
                            <option value="high" @selected($ticket->priority === 'high')>High</option>
                            <option value="urgent" @selected($ticket->priority === 'urgent')>Urgent</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">Set Priority</button>
                    </form>

                    <hr class="my-1">

                    {{-- Status Actions --}}
                    @if($ticket->status !== 'resolved')
                        <form method="POST" action="{{ route('admin.tickets.resolve', $ticket) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-check-circle"></i> Mark Resolved
                            </button>
                        </form>
                    @endif

                    @if($ticket->status !== 'closed')
                        <form method="POST" action="{{ route('admin.tickets.close', $ticket) }}"
                              onsubmit="return confirm('Are you sure you want to close this ticket?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-dark btn-sm w-100">
                                <i class="bi bi-x-circle"></i> Close Ticket
                            </button>
                        </form>
                    @endif

                    @if($ticket->status === 'closed' || $ticket->status === 'resolved')
                        <form method="POST" action="{{ route('admin.tickets.reopen', $ticket) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                                <i class="bi bi-arrow-counterclockwise"></i> Reopen Ticket
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
