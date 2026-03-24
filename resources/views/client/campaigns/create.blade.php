@extends('layouts.client')

@section('title', 'New Campaign — Smart Property Management')

@section('page-content')

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('dashboard.campaigns.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="fw-bold mb-0">New Campaign</h4>
            <p class="text-muted mb-0 small">Fill in the details below to create a new campaign</p>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('dashboard.campaigns.store') }}" id="campaignForm">
    @csrf

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
                            value="{{ old('name') }}"
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
                            value="{{ old('subject') }}"
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
                                value="{{ old('from_name', auth()->user()->client->company_name ?? '') }}"
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
                                value="{{ old('from_email') }}"
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
                            value="{{ old('reply_to') }}"
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
                <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center justify-content-between">
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
                    >{{ old('html_body') }}</textarea>
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
                            <option value="{{ $list->id }}" {{ old('list_id') == $list->id ? 'selected' : '' }}>
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
                    @if($lists->isEmpty())
                        <div class="form-text text-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            No lists found.
                            <a href="{{ route('dashboard.lists.index') }}">Create one first.</a>
                        </div>
                    @endif
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
                            <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
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
                            <option value="{{ $smtp->id }}" {{ old('smtp_account_id') == $smtp->id ? 'selected' : '' }}>
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
                            No SMTP accounts configured. Using server sendmail (limited for bulk).
                            <a href="{{ route('dashboard.smtp-accounts.index') }}">Add SMTP account</a> for better deliverability.
                        </div>
                    @else
                        <div class="form-text">
                            Select an SMTP account for best deliverability. Fallback uses server sendmail.
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
                    >{{ old('plain_text') }}</textarea>
                    @error('plain_text')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Recommended for deliverability. Shown to recipients who cannot view HTML.</div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-floppy me-2"></i>Save as Draft
                </button>
                <a href="{{ route('dashboard.campaigns.index') }}" class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>

        </div>{{-- /RIGHT COLUMN --}}

    </div>{{-- /row --}}
</form>

@endsection
