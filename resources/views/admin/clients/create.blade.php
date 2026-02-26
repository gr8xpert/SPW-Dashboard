@extends('layouts.admin')

@section('title', 'Add Client — Admin')
@section('page-title', 'Add Client')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Client</h4>
        <p class="text-muted mb-0">Create a new widget client with admin account</p>
    </div>
    <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Clients
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route('admin.clients.store') }}">
    @csrf

    <div class="row">
        <div class="col-lg-8">
            {{-- Company Info --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom pt-4 pb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-building me-2 text-primary"></i>Company Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('company_name') is-invalid @enderror"
                               id="company_name" name="company_name" value="{{ old('company_name') }}" required>
                        @error('company_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="domain" class="form-label">Widget Domain <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('domain') is-invalid @enderror"
                                   id="domain" name="domain" value="{{ old('domain') }}"
                                   placeholder="example.com" required>
                            <div class="form-text">The website where the widget will be embedded</div>
                            @error('domain')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="owner_email" class="form-label">Owner Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('owner_email') is-invalid @enderror"
                                   id="owner_email" name="owner_email" value="{{ old('owner_email') }}" required>
                            <div class="form-text">Also used as the dashboard login email</div>
                            @error('owner_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="api_url" class="form-label">Property API URL</label>
                            <input type="url" class="form-control @error('api_url') is-invalid @enderror"
                                   id="api_url" name="api_url" value="{{ old('api_url') }}"
                                   placeholder="https://api.example.com/properties">
                            <div class="form-text">Client's CRM / property feed endpoint</div>
                            @error('api_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="default_language" class="form-label">Default Language</label>
                            <select class="form-select" id="default_language" name="default_language">
                                <option value="en" @selected(old('default_language', 'en') === 'en')>English</option>
                                <option value="mt" @selected(old('default_language') === 'mt')>Maltese</option>
                                <option value="it" @selected(old('default_language') === 'it')>Italian</option>
                                <option value="fr" @selected(old('default_language') === 'fr')>French</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Admin Account --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom pt-4 pb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-person-lock me-2 text-primary"></i>Dashboard Admin Account</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">An admin user will be created with the owner email above and this password.</p>
                    <div class="mb-0">
                        <label for="admin_password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('admin_password') is-invalid @enderror"
                               id="admin_password" name="admin_password" required minlength="8">
                        @error('admin_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Plan & Subscription --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom pt-4 pb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-gem me-2 text-primary"></i>Plan & Subscription</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="plan_id" class="form-label">Plan <span class="text-danger">*</span></label>
                        <select class="form-select @error('plan_id') is-invalid @enderror" id="plan_id" name="plan_id" required>
                            <option value="">Select a plan...</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>
                                    {{ $plan->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('plan_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="subscription_status" class="form-label">Subscription Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="subscription_status" name="subscription_status" required>
                            <option value="active" @selected(old('subscription_status', 'active') === 'active')>Active</option>
                            <option value="manual" @selected(old('subscription_status') === 'manual')>Manual</option>
                            <option value="internal" @selected(old('subscription_status') === 'internal')>Internal</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="billing_source" class="form-label">Billing Source <span class="text-danger">*</span></label>
                        <select class="form-select" id="billing_source" name="billing_source" required>
                            <option value="manual" @selected(old('billing_source', 'manual') === 'manual')>Manual</option>
                            <option value="paddle" @selected(old('billing_source') === 'paddle')>Paddle</option>
                            <option value="internal" @selected(old('billing_source') === 'internal')>Internal</option>
                        </select>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="admin_override" name="admin_override"
                               value="1" @checked(old('admin_override'))>
                        <label class="form-check-label" for="admin_override">
                            Admin Override
                        </label>
                        <div class="form-text">Bypass subscription checks — widget always active</div>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-lg me-1"></i> Create Client
                </button>
                <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>
@endsection
