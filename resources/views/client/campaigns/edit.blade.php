@extends('layouts.client')

@section('title', 'Edit Campaign — Smart Property Management')

@section('page-content')

{{-- Page Header --}}
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('dashboard.campaigns.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="fw-bold mb-0">Edit Campaign: {{ $campaign->name }}</h4>
            <p class="text-muted mb-0 small">
                Last updated {{ $campaign->updated_at->diffForHumans() }}
                &mdash;
                <span class="badge
                    @if($campaign->status === 'sent') bg-success
                    @elseif($campaign->status === 'draft') bg-secondary
                    @elseif($campaign->status === 'scheduled') bg-primary
                    @elseif(in_array($campaign->status, ['sending','queued'])) bg-warning text-dark
                    @elseif($campaign->status === 'paused') bg-info text-dark
                    @elseif($campaign->status === 'cancelled') bg-dark
                    @elseif($campaign->status === 'failed') bg-danger
                    @else bg-secondary
                    @endif">
                    {{ ucfirst($campaign->status) }}
                </span>
            </p>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex flex-wrap gap-2">
        {{-- Test Email --}}
        <button
            type="button"
            class="btn btn-outline-secondary"
            data-bs-toggle="modal"
            data-bs-target="#testEmailModal"
        >
            <i class="bi bi-envelope-check me-1"></i> Send Test
        </button>

        {{-- Schedule --}}
        @if(in_array($campaign->status, ['draft', 'paused']))
            <button
                type="button"
                class="btn btn-outline-primary"
                data-bs-toggle="modal"
                data-bs-target="#scheduleModal"
            >
                <i class="bi bi-calendar-event me-1"></i> Schedule
            </button>
        @endif

        {{-- Send Now --}}
        @if(in_array($campaign->status, ['draft', 'paused', 'scheduled']))
            <form
                method="POST"
                action="{{ route('dashboard.campaigns.send-now', $campaign) }}"
                onsubmit="return confirm('Send this campaign to all recipients now? This action cannot be undone.')"
            >
                @csrf
                @method('POST')
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-send me-1"></i> Send Now
                </button>
            </form>
        @endif
    </div>
</div>

{{-- Main Edit Form --}}
<form method="POST" action="{{ route('dashboard.campaigns.update', $campaign) }}" id="campaignForm">
    @csrf
    @method('PUT')

    <div class="row g-4">

        {{-- LEFT COLUMN: Content --}}
        <div class="col-lg-8">

            {{-- Campaign Identity --}}
            <div class="card stat-card mb-4">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-tag me-2 text-primary"></i>Campaign Details
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">
                            Campaign Name <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $campaign->name) }}"
                            class="form-control @error('name') is-invalid @enderror"
                            placeholder="e.g. Summer Sale Announcement"
                            required
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Internal name for your reference — not shown to recipients.</div>
                    </div>

                    <div class="mb-0">
                        <label for="subject" class="form-label fw-semibold">
                            Email Subject <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            value="{{ old('subject', $campaign->subject) }}"
                            class="form-control @error('subject') is-invalid @enderror"
                            placeholder="e.g. Don't miss our biggest sale of the year 🎉"
                            required
                        >
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Sender Details --}}
            <div class="card stat-card mb-4">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-person-check me-2 text-primary"></i>Sender Details
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="from_name" class="form-label fw-semibold">
                                From Name <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                id="from_name"
                                name="from_name"
                                value="{{ old('from_name', $campaign->from_name) }}"
                                class="form-control @error('from_name') is-invalid @enderror"
                                placeholder="e.g. Acme Corp"
                                required
                            >
                            @error('from_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="from_email" class="form-label fw-semibold">
                                From Email <span class="text-danger">*</span>
                            </label>
                            <input
                                type="email"
                                id="from_email"
                                name="from_email"
                                value="{{ old('from_email', $campaign->from_email) }}"
                                class="form-control @error('from_email') is-invalid @enderror"
                                placeholder="e.g. hello@acme.com"
                                required
                            >
                            @error('from_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="reply_to" class="form-label fw-semibold">Reply-To Email</label>
                        <input
                            type="email"
                            id="reply_to"
                            name="reply_to"
                            value="{{ old('reply_to', $campaign->reply_to) }}"
                            class="form-control @error('reply_to') is-invalid @enderror"
                            placeholder="Leave blank to use From Email"
                        >
                        @error('reply_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Optional — replies will go here instead of the From address.</div>
                    </div>
                </div>
            </div>

            {{-- Email Body --}}
            <div class="card stat-card">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-code-slash me-2 text-primary"></i>HTML Content
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info d-flex align-items-start gap-2 py-2 mb-3">
                        <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
                        <div class="small">
                            You can paste raw HTML here or select a template from the sidebar to
                            auto-populate this field. For a visual builder experience, use the
                            <a href="{{ route('dashboard.templates.index') }}" class="alert-link">Template Editor</a>
                            to design your email and then select it below.
                        </div>
                    </div>
                    <textarea
                        id="html_body"
                        name="html_body"
                        rows="18"
                        class="form-control font-monospace @error('html_body') is-invalid @enderror"
                        placeholder="Paste your HTML email content here…"
                        style="font-size: .8rem; resize: vertical;"
                    >{{ old('html_body', $campaign->html_content) }}</textarea>
                    @error('html_body')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

        </div>{{-- /LEFT COLUMN --}}

        {{-- RIGHT COLUMN: Settings --}}
        <div class="col-lg-4">

            {{-- Contact List --}}
            <div class="card stat-card mb-4">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-collection me-2 text-primary"></i>Contact List
                    </h6>
                </div>
                <div class="card-body">
                    <label for="list_id" class="form-label fw-semibold">
                        Send To <span class="text-danger">*</span>
                    </label>
                    <select
                        id="list_id"
                        name="list_id"
                        class="form-select @error('list_id') is-invalid @enderror"
                        required
                    >
                        <option value="">— Select a list —</option>
                        @foreach($lists as $list)
                            <option value="{{ $list->id }}"
                                {{ old('list_id', $campaign->list_id) == $list->id ? 'selected' : '' }}>
                                {{ $list->name }}
                                @if(isset($list->contacts_count))
                                    ({{ number_format($list->contacts_count) }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('list_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Template --}}
            <div class="card stat-card mb-4">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-file-earmark-richtext me-2 text-primary"></i>Template
                    </h6>
                </div>
                <div class="card-body">
                    <label for="template_id" class="form-label fw-semibold">Load from Template</label>
                    <select
                        id="template_id"
                        name="template_id"
                        class="form-select @error('template_id') is-invalid @enderror"
                    >
                        <option value="">— None (custom HTML above) —</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}"
                                {{ old('template_id', $campaign->template_id) == $template->id ? 'selected' : '' }}>
                                {{ $template->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('template_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Selecting a template will use its HTML content when sending.</div>
                </div>
            </div>

            {{-- SMTP Account --}}
            <div class="card stat-card mb-4">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-server me-2 text-primary"></i>SMTP Account
                    </h6>
                </div>
                <div class="card-body">
                    <label for="smtp_account_id" class="form-label fw-semibold">Send via</label>
                    <select
                        id="smtp_account_id"
                        name="smtp_account_id"
                        class="form-select @error('smtp_account_id') is-invalid @enderror"
                    >
                        <option value="">— Fallback (Server Sendmail) —</option>
                        @foreach($smtpAccounts as $smtp)
                            <option value="{{ $smtp->id }}"
                                {{ old('smtp_account_id', $campaign->smtp_account_id) == $smtp->id ? 'selected' : '' }}>
                                {{ $smtp->name }} ({{ $smtp->host }})
                            </option>
                        @endforeach
                    </select>
                    @error('smtp_account_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if($smtpAccounts->isEmpty())
                        <div class="form-text text-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            No SMTP accounts. Using sendmail fallback.
                            <a href="{{ route('dashboard.smtp-accounts.index') }}">Add SMTP</a>
                        </div>
                    @else
                        <div class="form-text">
                            Select SMTP for best deliverability. Fallback uses server sendmail.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Plain Text --}}
            <div class="card stat-card mb-4">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-file-text me-2 text-primary"></i>Plain Text Version
                    </h6>
                </div>
                <div class="card-body">
                    <label for="plain_text" class="form-label fw-semibold">Plain Text</label>
                    <textarea
                        id="plain_text"
                        name="plain_text"
                        rows="8"
                        class="form-control @error('plain_text') is-invalid @enderror"
                        placeholder="Plain text fallback for email clients that don't render HTML…"
                        style="resize: vertical; font-size: .85rem;"
                    >{{ old('plain_text', $campaign->plain_text_content) }}</textarea>
                    @error('plain_text')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Recommended for deliverability.</div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-floppy me-2"></i>Save Changes
                </button>
                <a href="{{ route('dashboard.campaigns.show', $campaign) }}" class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>

        </div>{{-- /RIGHT COLUMN --}}

    </div>{{-- /row --}}
</form>


{{-- ============================================================ --}}
{{-- TEST EMAIL MODAL --}}
{{-- ============================================================ --}}
<div class="modal fade" id="testEmailModal" tabindex="-1" aria-labelledby="testEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('dashboard.campaigns.test-send', $campaign) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="testEmailModalLabel">
                        <i class="bi bi-envelope-check me-2 text-primary"></i>Send Test Email
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        A test version of this campaign will be sent to the address below.
                        The email will use the current saved content.
                    </p>
                    <label for="test_email" class="form-label fw-semibold">
                        Send Test To <span class="text-danger">*</span>
                    </label>
                    <input
                        type="email"
                        id="test_email"
                        name="test_email"
                        class="form-control"
                        placeholder="you@example.com"
                        value="{{ auth()->user()->email }}"
                        required
                    >
                    <div class="form-text">Separate multiple addresses with commas.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>Send Test
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ============================================================ --}}
{{-- SCHEDULE MODAL --}}
{{-- ============================================================ --}}
@if(in_array($campaign->status, ['draft', 'paused']))
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('dashboard.campaigns.schedule', $campaign) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="scheduleModalLabel">
                        <i class="bi bi-calendar-event me-2 text-primary"></i>Schedule Campaign
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Choose a date and time to automatically send this campaign.
                        The time is in your account's local timezone.
                    </p>
                    <label for="scheduled_at" class="form-label fw-semibold">
                        Send Date &amp; Time <span class="text-danger">*</span>
                    </label>
                    <input
                        type="datetime-local"
                        id="scheduled_at"
                        name="scheduled_at"
                        class="form-control"
                        value="{{ $campaign->scheduled_at ? $campaign->scheduled_at->format('Y-m-d\TH:i') : '' }}"
                        min="{{ now()->addMinutes(15)->format('Y-m-d\TH:i') }}"
                        required
                    >
                    <div class="form-text">Must be at least 15 minutes from now.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-calendar-check me-1"></i>Confirm Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
