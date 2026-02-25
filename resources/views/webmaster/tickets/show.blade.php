@extends('layouts.client')

@section('title', 'Ticket #' . $ticket->id . ' — Work View')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-wrench me-2 text-primary"></i>Ticket #{{ $ticket->id }}</h4>
        <p class="text-muted mb-0">{{ $ticket->subject }}</p>
    </div>
    <a href="{{ route('webmaster.tickets.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Tickets
    </a>
</div>

<div class="row g-4">
    {{-- Left Column: Message Thread + Reply --}}
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
                            <span class="fw-semibold small">{{ $ticket->user->name ?? 'Client' }}</span>
                            <span class="badge bg-light text-dark border ms-1" style="font-size: .65rem;">Client</span>
                            <span class="text-muted small ms-2">{{ $ticket->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
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

        {{-- Reply Thread --}}
        @foreach($ticket->messages ?? [] as $message)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header {{ $message->is_staff ? 'bg-success bg-opacity-10' : 'bg-light' }} border-0 pt-3 pb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle {{ $message->is_staff ? 'bg-success' : 'bg-primary' }} d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-{{ $message->is_staff ? 'headset' : 'person-fill' }} text-white" style="font-size: 14px;"></i>
                        </div>
                        <div>
                            <span class="fw-semibold small">{{ $message->user->name ?? 'Unknown' }}</span>
                            @if($message->is_staff)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 ms-1" style="font-size: .65rem;">Staff</span>
                            @else
                                <span class="badge bg-light text-dark border ms-1" style="font-size: .65rem;">Client</span>
                            @endif
                            <span class="text-muted small ms-2">{{ $message->created_at->format('M d, Y H:i') }}</span>
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
        @endforeach

        {{-- Reply Form --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-reply me-2 text-primary"></i>Post a Reply</h6>
            </div>
            <div class="card-body p-4">
                {{-- Canned Responses --}}
                @if(!empty($cannedResponses ?? []))
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Quick Insert (Canned Response)</label>
                        <select class="form-select form-select-sm" id="cannedResponseSelect">
                            <option value="">Select a canned response...</option>
                            @foreach($cannedResponses as $canned)
                                <option value="{{ $canned['body'] }}">{{ $canned['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <form method="POST" action="{{ route('webmaster.tickets.reply', $ticket) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <textarea name="body"
                                  id="replyBody"
                                  class="form-control @error('body') is-invalid @enderror"
                                  rows="5"
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

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input type="checkbox" name="internal_note" class="form-check-input" id="internalNote" value="1">
                            <label class="form-check-label small text-muted" for="internalNote">
                                <i class="bi bi-lock me-1"></i> Internal note (not visible to client)
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Right Column: Info + Actions --}}
    <div class="col-lg-4">
        {{-- Ticket Info --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Ticket Info</h6>
            </div>
            <div class="card-body p-4">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted small">Client</dt>
                    <dd class="col-7 small fw-medium">{{ $ticket->client->company_name ?? 'Unknown' }}</dd>

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

                    <dt class="col-5 text-muted small">Hours Logged</dt>
                    <dd class="col-7 small fw-bold">{{ number_format($ticket->hours_logged ?? 0, 1) }}h</dd>
                </dl>
            </div>
        </div>

        {{-- Change Status --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-arrow-repeat me-2 text-primary"></i>Update Status</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('webmaster.tickets.update-status', $ticket) }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <select name="status" class="form-select">
                            <option value="open"            {{ $ticket->status === 'open'            ? 'selected' : '' }}>Open</option>
                            <option value="in_progress"     {{ $ticket->status === 'in_progress'     ? 'selected' : '' }}>In Progress</option>
                            <option value="awaiting_reply"  {{ $ticket->status === 'awaiting_reply'  ? 'selected' : '' }}>Awaiting Reply</option>
                            <option value="closed"          {{ $ticket->status === 'closed'          ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-check-lg me-1"></i> Update Status
                    </button>
                </form>
            </div>
        </div>

        {{-- Book Hours --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock me-2 text-primary"></i>Book Hours</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('webmaster.tickets.book-hours', $ticket) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Hours Worked</label>
                        <div class="input-group">
                            <input type="number"
                                   name="hours"
                                   class="form-control @error('hours') is-invalid @enderror"
                                   min="0.1"
                                   max="24"
                                   step="0.1"
                                   value="{{ old('hours', '0.5') }}"
                                   required>
                            <span class="input-group-text">hours</span>
                        </div>
                        @error('hours')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Description</label>
                        <input type="text"
                               name="description"
                               class="form-control @error('description') is-invalid @enderror"
                               value="{{ old('description') }}"
                               placeholder="What did you work on?"
                               required>
                        @error('description')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Date</label>
                        <input type="date"
                               name="date"
                               class="form-control"
                               value="{{ old('date', date('Y-m-d')) }}"
                               required>
                    </div>
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-plus-circle me-1"></i> Log Hours
                    </button>
                </form>

                {{-- Recent Time Entries --}}
                @if(!empty($timeEntries ?? []))
                    <hr>
                    <small class="text-muted fw-semibold d-block mb-2">Recent Entries</small>
                    @foreach($timeEntries->take(5) as $entry)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                            <div>
                                <small class="d-block">{{ $entry->description }}</small>
                                <small class="text-muted">{{ $entry->date->format('M d') }}</small>
                            </div>
                            <span class="badge bg-light text-dark border">{{ number_format($entry->hours, 1) }}h</span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Client Credit Balance --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-center">
                <small class="text-muted d-block mb-1">Client Credit Balance</small>
                <div class="fw-bold fs-4 {{ ($ticket->client->credit_balance ?? 0) <= 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($ticket->client->credit_balance ?? 0, 1) }}h
                </div>
                @if(($ticket->client->credit_balance ?? 0) <= 0)
                    <small class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>No credits remaining</small>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Canned response quick insert
const cannedSelect = document.getElementById('cannedResponseSelect');
if (cannedSelect) {
    cannedSelect.addEventListener('change', function () {
        if (this.value) {
            const textarea = document.getElementById('replyBody');
            textarea.value = textarea.value ? textarea.value + '\n\n' + this.value : this.value;
            this.value = '';
            textarea.focus();
        }
    });
}
</script>
@endpush
