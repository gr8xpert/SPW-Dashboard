@extends('layouts.client')

@section('title', 'Edit Team Member')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Edit Team Member: {{ $team->name }}</h4>
        <p class="text-muted mb-0">Update role and details for this team member</p>
    </div>
    <a href="{{ route('dashboard.team.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Team
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                {{-- Member summary badge --}}
                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold"
                         style="width:48px; height:48px; font-size:20px; flex-shrink:0;">
                        {{ strtoupper(substr($team->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="fw-bold">{{ $team->name }}</div>
                        <div class="text-muted small">{{ $team->email }}</div>
                    </div>
                    <div class="ms-auto">
                        @php
                            $roleColors = ['admin' => 'danger', 'editor' => 'warning', 'viewer' => 'info'];
                            $roleColor  = $roleColors[$team->role] ?? 'secondary';
                        @endphp
                        <span class="badge bg-{{ $roleColor }} bg-opacity-10 text-{{ $roleColor }} border border-{{ $roleColor }} border-opacity-25 text-capitalize">
                            {{ ucfirst($team->role) }}
                        </span>
                    </div>
                </div>

                <form method="POST" action="{{ route('dashboard.team.update', $team) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">

                        {{-- Name --}}
                        <div class="col-12">
                            <label class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text"
                                       name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $team->name) }}"
                                       placeholder="Full name"
                                       required>
                            </div>
                            @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Role --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Role <span class="text-danger">*</span></label>
                            <select name="role"
                                    class="form-select @error('role') is-invalid @enderror"
                                    required>
                                <option value="admin"  {{ old('role', $team->role) === 'admin'  ? 'selected' : '' }}>
                                    Admin — Full access
                                </option>
                                <option value="editor" {{ old('role', $team->role) === 'editor' ? 'selected' : '' }}>
                                    Editor — Create &amp; edit
                                </option>
                                <option value="viewer" {{ old('role', $team->role) === 'viewer' ? 'selected' : '' }}>
                                    Viewer — Read only
                                </option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text" id="roleHint">
                                @if(old('role', $team->role) === 'admin')
                                    Full access to all features, settings, team, and billing.
                                @elseif(old('role', $team->role) === 'viewer')
                                    Read-only access. Can view campaigns, contacts, and analytics.
                                @else
                                    Can create and edit campaigns, templates, contacts, and lists.
                                @endif
                            </div>
                        </div>

                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-12">
                            <p class="fw-medium mb-0 text-muted small text-uppercase">Change Password</p>
                        </div>

                        {{-- New Password --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password"
                                       name="password"
                                       id="passwordField"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Leave blank to keep current"
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePassword('passwordField', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                <i class="bi bi-shield-lock me-1"></i>
                                Leave blank to keep the current password.
                            </div>
                            @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password Confirmation --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password"
                                       name="password_confirmation"
                                       id="passwordConfirmField"
                                       class="form-control"
                                       placeholder="Repeat new password"
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePassword('passwordConfirmField', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('dashboard.team.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy me-1"></i> Save Changes
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
