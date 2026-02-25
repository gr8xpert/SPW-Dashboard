@extends('layouts.client')

@section('title', 'Invite Team Member')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>Invite Team Member</h4>
        <p class="text-muted mb-0">Add a new member to your SmartMailer team</p>
    </div>
    <a href="{{ route('dashboard.team.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Team
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                <div class="alert alert-info border-0 bg-info bg-opacity-10 mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    The new member will receive login credentials. They can change their password after their first login.
                </div>

                <form method="POST" action="{{ route('dashboard.team.store') }}">
                    @csrf

                    <div class="row g-3">

                        {{-- Name --}}
                        <div class="col-12">
                            <label class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text"
                                       name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}"
                                       placeholder="Jane Doe"
                                       required>
                            </div>
                            @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="col-12">
                            <label class="form-label fw-medium">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email"
                                       name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}"
                                       placeholder="jane@yourdomain.com"
                                       required>
                            </div>
                            @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Role --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Role <span class="text-danger">*</span></label>
                            <select name="role"
                                    class="form-select @error('role') is-invalid @enderror"
                                    required>
                                <option value="">-- Select Role --</option>
                                <option value="admin"  {{ old('role') === 'admin'  ? 'selected' : '' }}>
                                    Admin — Full access
                                </option>
                                <option value="editor" {{ old('role', 'editor') === 'editor' ? 'selected' : '' }}>
                                    Editor — Create &amp; edit
                                </option>
                                <option value="viewer" {{ old('role') === 'viewer' ? 'selected' : '' }}>
                                    Viewer — Read only
                                </option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text" id="roleHint">
                                @if(old('role') === 'admin')
                                    Full access to all features, settings, team, and billing.
                                @elseif(old('role') === 'viewer')
                                    Read-only access. Can view campaigns, contacts, and analytics.
                                @else
                                    Can create and edit campaigns, templates, contacts, and lists.
                                @endif
                            </div>
                        </div>

                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-12">
                            <p class="fw-medium mb-0 text-muted small text-uppercase">Set Initial Password</p>
                        </div>

                        {{-- Password --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password"
                                       name="password"
                                       id="passwordField"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Min. 8 characters"
                                       autocomplete="new-password"
                                       required>
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePassword('passwordField', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password Confirmation --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password"
                                       name="password_confirmation"
                                       id="passwordConfirmField"
                                       class="form-control @error('password_confirmation') is-invalid @enderror"
                                       placeholder="Repeat password"
                                       autocomplete="new-password"
                                       required>
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePassword('passwordConfirmField', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="alert alert-light border mb-0 py-2">
                                <i class="bi bi-lightbulb me-2 text-warning"></i>
                                <small>The member can change their password after logging in for the first time.</small>
                            </div>
                        </div>

                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('dashboard.team.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus me-1"></i> Invite Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function togglePassword(fieldId, btn) {
        const field = document.getElementById(fieldId);
        const icon  = btn.querySelector('i');
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }

    const roleHints = {
        admin:  'Full access to all features, settings, team, and billing.',
        editor: 'Can create and edit campaigns, templates, contacts, and lists.',
        viewer: 'Read-only access. Can view campaigns, contacts, and analytics.',
    };

    document.querySelector('[name="role"]').addEventListener('change', function () {
        const hint = document.getElementById('roleHint');
        hint.textContent = roleHints[this.value] ?? '';
    });
</script>
@endpush
@endsection
