@extends('layouts.client')

@section('title', 'Add SMTP Account')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-server me-2 text-primary"></i>Add SMTP Account</h4>
        <p class="text-muted mb-0">Connect a new email sending account or provider</p>
    </div>
    <a href="{{ route('dashboard.smtp-accounts.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to SMTP Accounts
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('dashboard.smtp-accounts.store') }}">
                    @csrf

                    <div class="row g-3">

                        {{-- Account Name --}}
                        <div class="col-12">
                            <label class="form-label fw-medium">Account Name <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}"
                                   placeholder="e.g. Primary SendGrid, AWS SES Production"
                                   required>
                            <div class="form-text">A friendly label to identify this account.</div>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Provider --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Provider <span class="text-danger">*</span></label>
                            <select name="provider" id="providerSelect"
                                    class="form-select @error('provider') is-invalid @enderror" required>
                                <option value="">-- Select Provider --</option>
                                <option value="ses"       {{ old('provider') === 'ses'       ? 'selected' : '' }}>Amazon SES</option>
                                <option value="sendgrid"  {{ old('provider') === 'sendgrid'  ? 'selected' : '' }}>SendGrid</option>
                                <option value="mailgun"   {{ old('provider') === 'mailgun'   ? 'selected' : '' }}>Mailgun</option>
                                <option value="postmark"  {{ old('provider') === 'postmark'  ? 'selected' : '' }}>Postmark</option>
                                <option value="smtp"      {{ old('provider') === 'smtp'      ? 'selected' : '' }}>Custom SMTP</option>
                                <option value="platform"  {{ old('provider') === 'platform'  ? 'selected' : '' }}>Platform Default</option>
                            </select>
                            @error('provider')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Encryption --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Encryption</label>
                            <select name="encryption" class="form-select @error('encryption') is-invalid @enderror">
                                <option value="tls"  {{ old('encryption', 'tls') === 'tls'  ? 'selected' : '' }}>TLS</option>
                                <option value="ssl"  {{ old('encryption') === 'ssl'  ? 'selected' : '' }}>SSL</option>
                                <option value="none" {{ old('encryption') === 'none' ? 'selected' : '' }}>None</option>
                            </select>
                            @error('encryption')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Host --}}
                        <div class="col-sm-8">
                            <label class="form-label fw-medium">SMTP Host</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-hdd-network"></i></span>
                                <input type="text"
                                       name="host"
                                       class="form-control @error('host') is-invalid @enderror"
                                       value="{{ old('host') }}"
                                       placeholder="smtp.sendgrid.net">
                            </div>
                            @error('host')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Port --}}
                        <div class="col-sm-4">
                            <label class="form-label fw-medium">Port</label>
                            <input type="number"
                                   name="port"
                                   class="form-control @error('port') is-invalid @enderror"
                                   value="{{ old('port', 587) }}"
                                   min="1" max="65535"
                                   placeholder="587">
                            @error('port')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Username --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Username / API Key</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text"
                                       name="username"
                                       class="form-control @error('username') is-invalid @enderror"
                                       value="{{ old('username') }}"
                                       placeholder="apikey or username"
                                       autocomplete="new-password">
                            </div>
                            @error('username')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Password / Secret</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password"
                                       name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="••••••••••••"
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePassword(this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-12">
                            <p class="fw-medium mb-0 text-muted small text-uppercase">From Address (Optional)</p>
                        </div>

                        {{-- From Email --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">From Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email"
                                       name="from_email"
                                       class="form-control @error('from_email') is-invalid @enderror"
                                       value="{{ old('from_email') }}"
                                       placeholder="hello@yourdomain.com">
                            </div>
                            @error('from_email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- From Name --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">From Name</label>
                            <input type="text"
                                   name="from_name"
                                   class="form-control @error('from_name') is-invalid @enderror"
                                   value="{{ old('from_name') }}"
                                   placeholder="Your Company Name">
                            @error('from_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Daily Limit --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Daily Sending Limit</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-speedometer2"></i></span>
                                <input type="number"
                                       name="daily_limit"
                                       class="form-control @error('daily_limit') is-invalid @enderror"
                                       value="{{ old('daily_limit') }}"
                                       placeholder="Leave blank for unlimited"
                                       min="1">
                                <span class="input-group-text text-muted">emails/day</span>
                            </div>
                            <div class="form-text">Leave blank for no daily limit.</div>
                            @error('daily_limit')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('dashboard.smtp-accounts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i> Add SMTP Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function togglePassword(btn) {
        const input = btn.closest('.input-group').querySelector('input');
        const icon  = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }
</script>
@endpush
@endsection
