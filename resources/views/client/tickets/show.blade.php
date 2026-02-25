@extends('layouts.client')

@section('title', 'Ticket #' . $ticket->id)

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-ticket-detailed me-2 text-primary"></i>Ticket #{{ $ticket->id }}</h4>
        <p class="text-muted mb-0">{{ $ticket->subject }}</p>
    </div>
    <a href="{{ route('dashboard.tickets.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Tickets
    </a>
</div>

<div class="row g-4">
    {{-- Message Thread --}}
    <div class="col-lg-8">
        {{-- Original Message --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-primary bg-opacity-10 border-0 pt-3 pb-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-person-fill text-white" style="font-size: 14px;"></i>
                        </div>
                        <div>
                            <span class="fw-semibold small">{{ $ticket->user->name ?? 'You' }}</span>
                            <span class="text-muted small ms-2">{{ $ticket->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Original</span>
                </div>
            </div>
            <div class="card-body">
                <div class="small" style="white-space: pre-line;">{{ $ticket->description }}</div>
                @if($ticket->attachment_path)
                    <div class="mt-3 pt-3 border-top">
                        <a href="{{ Storage::url($ticket->attachment_path) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                            <i class="bi bi-paperclip me-1"></i> Attachment
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Replies --}}
        @forelse($ticket->messages ?? [] as $message)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header {{ $message->is_staff ? 'bg-success bg-opacity-10' : 'bg-light' }} border-0 pt-3 pb-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle {{ $message->is_staff ? 'bg-success' : 'bg-primary' }} d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="bi bi-{{ $message->is_staff ? 'headset' : 'person-fill' }} text-white" style="font-size: 14px;"></i>
                            </div>
                            <div>
                                <span class="fw-semibold small">{{ $message->user->name ?? 'Support' }}</span>
                                @if($message->is_staff)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 ms-1" style="font-size: .65rem;">Staff</span>
                                @endif
                                <span class="text-muted small ms-2">{{ $message->created_at->format('M d, Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="small" style="white-space: pre-line;">{{ $message->body }}</div>
                    @if($message->attachment_path)
                        <div class="mt-3 pt-3 border-top">
                            <a href="{{ Storage::url($message->attachment_path) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                <i class="bi bi-paperclip me-1"></i> Attachment
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            {{-- No replies yet; original message shown above --}}
        @endforelse

        {{-- Reply Form --}}
        @if($ticket->status !== 'closed')
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom pt-4 pb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-reply me-2 text-primary"></i>Post a Reply</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('dashboard.tickets.reply', $ticket) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <textarea name="body"
                                      class="form-control @error('body') is-invalid @enderror"
                                      rows="4"
                                      placeholder="Write your reply..."
                                      required>{{ old('body') }}</textarea>
                            @error('body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <input type="file"
                                   name="attachment"
                                   class="form-control form-control-sm"
                                   accept=".jpg,.jpeg,.png,.gif,.pdf,.zip,.txt">
                            <div class="form-text">Optional attachment. Max 10MB.</div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-1"></i> Send Reply
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="alert alert-secondary text-center">
                <i class="bi bi-lock me-1"></i> This ticket is closed. <a href="{{ route('dashboard.tickets.create') }}">Open a new ticket</a> if you need further assistance.
            </div>
        @endif
    </div>

    {{-- Ticket Info Sidebar --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Ticket Info</h6>
            </div>
            <div class="card-body p-4">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted small">Status</dt>
                    <dd class="col-7">
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
                            {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                        </span>
                    </dd>

                    <dt class="col-5 text-muted small">Priority</dt>
                    <dd class="col-7">
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
                            {{ ucfirst($ticket->priority) }}
                        </span>
                    </dd>

                    <dt class="col-5 text-muted small">Category</dt>
                    <dd class="col-7 small">{{ ucfirst(str_replace('_', ' ', $ticket->category ?? 'General')) }}</dd>

                    <dt class="col-5 text-muted small">Created</dt>
                    <dd class="col-7 small">{{ $ticket->created_at->format('M d, Y') }}</dd>

                    <dt class="col-5 text-muted small">Last Update</dt>
                    <dd class="col-7 small">{{ $ticket->updated_at->diffForHumans() }}</dd>

                    @if($ticket->assigned_to)
                        <dt class="col-5 text-muted small">Assigned To</dt>
                        <dd class="col-7 small">{{ $ticket->assignee->name ?? 'Staff' }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        @if($ticket->status !== 'closed')
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body p-4 text-center">
                    <p class="text-muted small mb-3">Issue resolved?</p>
                    <form method="POST" action="{{ route('dashboard.tickets.close', $ticket) }}"
                          onsubmit="return confirm('Close this ticket?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-check-circle me-1"></i> Close Ticket
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
