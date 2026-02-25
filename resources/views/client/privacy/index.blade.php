@extends('layouts.client')

@section('title', 'Privacy & Data')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i>Privacy & Data</h4>
        <p class="text-muted mb-0">Manage your data in compliance with GDPR and privacy regulations</p>
    </div>
</div>

{{-- Privacy Overview --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom pt-4 pb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Your Privacy Rights</h6>
    </div>
    <div class="card-body p-4">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2 flex-shrink-0">
                        <i class="bi bi-download fs-5 text-primary"></i>
                    </div>
                    <div>
                        <h6 class="fw-semibold mb-1">Right to Access</h6>
                        <p class="text-muted small mb-0">
                            You can request a copy of all personal data we store about you and your account.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-2 flex-shrink-0">
                        <i class="bi bi-pencil-square fs-5 text-warning"></i>
                    </div>
                    <div>
                        <h6 class="fw-semibold mb-1">Right to Rectification</h6>
                        <p class="text-muted small mb-0">
                            You can update your personal information at any time via your account settings.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-3 bg-danger bg-opacity-10 p-2 flex-shrink-0">
                        <i class="bi bi-trash3 fs-5 text-danger"></i>
                    </div>
                    <div>
                        <h6 class="fw-semibold mb-1">Right to Erasure</h6>
                        <p class="text-muted small mb-0">
                            You can request permanent deletion of your account and all associated data.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Export Data --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-file-earmark-zip me-2 text-primary"></i>Export My Data</h6>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    Download a complete archive of your account data including your profile, widget configuration,
                    inquiry contacts, analytics data, and support tickets.
                </p>

                <div class="alert alert-info border-0 bg-info bg-opacity-10 small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    The export will be generated as a ZIP file containing JSON and CSV files. This may take a few minutes.
                </div>

                @if(isset($lastExport))
                    <div class="mb-3 small text-muted">
                        <i class="bi bi-clock me-1"></i>
                        Last export: {{ $lastExport->created_at->format('M d, Y H:i') }}
                        @if($lastExport->status === 'ready')
                            &mdash; <a href="{{ route('dashboard.privacy.download-export', $lastExport) }}">Download</a>
                        @elseif($lastExport->status === 'processing')
                            &mdash; <span class="text-warning">Processing...</span>
                        @endif
                    </div>
                @endif

                <form method="POST" action="{{ route('dashboard.privacy.export') }}"
                      onsubmit="return confirm('Request a full export of your account data?')">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-download me-1"></i> Request Data Export
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete Account --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 border-danger border-opacity-25">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Delete My Account</h6>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    Permanently delete your account and all associated data. This action cannot be undone.
                </p>

                <div class="alert alert-danger border-0 bg-danger bg-opacity-10 small mb-3">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Warning:</strong> This will permanently delete:
                    <ul class="mb-0 mt-1">
                        <li>Your account and profile</li>
                        <li>Widget configuration and license key</li>
                        <li>All inquiry contacts and analytics data</li>
                        <li>All support tickets and credit history</li>
                        <li>Email marketing data (contacts, campaigns, templates)</li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('dashboard.privacy.delete') }}" id="deleteAccountForm">
                    @csrf
                    @method('DELETE')

                    <div class="mb-3">
                        <label class="form-label fw-medium text-danger">
                            Type <strong>DELETE</strong> to confirm
                        </label>
                        <input type="text"
                               name="confirmation"
                               class="form-control @error('confirmation') is-invalid @enderror"
                               placeholder="DELETE"
                               required
                               id="deleteConfirmInput">
                        @error('confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-danger w-100" id="deleteAccountBtn" disabled>
                        <i class="bi bi-trash3 me-1"></i> Permanently Delete Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('deleteConfirmInput').addEventListener('input', function () {
    document.getElementById('deleteAccountBtn').disabled = this.value !== 'DELETE';
});

document.getElementById('deleteAccountForm').addEventListener('submit', function (e) {
    if (!confirm('This is your final confirmation. Delete your account and all data permanently?')) {
        e.preventDefault();
    }
});
</script>
@endpush
