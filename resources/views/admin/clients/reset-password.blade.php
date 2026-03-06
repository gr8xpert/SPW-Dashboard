@extends('layouts.admin')

@section('title', 'Reset Password — Smart Property Management Admin')
@section('page-title', 'Reset User Password')

@section('page-content')

<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('admin.clients.show', $client) }}" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Back to {{ $client->company_name }}
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4">
                    <h5 class="fw-bold mb-1">
                        <i class="bi bi-key me-2 text-warning"></i>Reset Password
                    </h5>
                    <p class="text-muted mb-0">
                        Set a new password for <strong>{{ $user->name }}</strong> ({{ $user->email }})
                    </p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.clients.update-password', [$client, $user]) }}">
                        @csrf

                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" name="password" id="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   required minlength="8" autofocus>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Minimum 8 characters</div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="form-control" required minlength="8">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-check-lg me-1"></i> Update Password
                            </button>
                            <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
