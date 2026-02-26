@extends('layouts.admin')

@section('title', 'New Plan — Smart Property Management Admin')
@section('page-title', 'New Plan')

@section('page-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-tags me-2 text-primary"></i>Create Plan</h4>
        <p class="text-muted mb-0">Define a new subscription plan and its limits</p>
    </div>
    <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Plans
    </a>
</div>

<div class="row">

    {{-- Main form --}}
    <div class="col-lg-8">
        <form method="POST" action="{{ route('admin.plans.store') }}">
            @csrf

            {{-- Plan Details --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold py-3">
                    <i class="bi bi-info-circle me-2 text-muted"></i>Plan Details
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        {{-- Name --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="name">
                                Plan Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}"
                                   placeholder="e.g. Starter, Professional, Enterprise"
                                   required maxlength="50" autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Slug --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="slug">
                                Slug <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="slug" name="slug"
                                   class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug') }}"
                                   placeholder="e.g. starter, professional"
                                   required maxlength="50">
                            <div class="form-text">Auto-generated from name. Use lowercase letters and hyphens only.</div>
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Monthly Price --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="price_monthly">
                                Monthly Price ($) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                                <input type="number" id="price_monthly" name="price_monthly"
                                       step="0.01" min="0"
                                       class="form-control @error('price_monthly') is-invalid @enderror"
                                       value="{{ old('price_monthly', '0.00') }}"
                                       required>
                            </div>
                            @error('price_monthly')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Yearly Price --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="price_yearly">
                                Yearly Price ($) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                                <input type="number" id="price_yearly" name="price_yearly"
                                       step="0.01" min="0"
                                       class="form-control @error('price_yearly') is-invalid @enderror"
                                       value="{{ old('price_yearly', '0.00') }}"
                                       required>
                            </div>
                            @error('price_yearly')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Usage Limits --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold py-3">
                    <i class="bi bi-speedometer2 me-2 text-muted"></i>Usage Limits
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="max_contacts">
                                Max Contacts <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="max_contacts" name="max_contacts" min="0"
                                   class="form-control @error('max_contacts') is-invalid @enderror"
                                   value="{{ old('max_contacts', 500) }}" required>
                            @error('max_contacts')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="max_emails_per_month">
                                Max Emails / Month <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="max_emails_per_month" name="max_emails_per_month" min="0"
                                   class="form-control @error('max_emails_per_month') is-invalid @enderror"
                                   value="{{ old('max_emails_per_month', 1000) }}" required>
                            @error('max_emails_per_month')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="max_users">
                                Max Users <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="max_users" name="max_users" min="1"
                                   class="form-control @error('max_users') is-invalid @enderror"
                                   value="{{ old('max_users', 1) }}" required>
                            @error('max_users')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="max_image_storage_mb">
                                Image Storage (MB) <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="max_image_storage_mb" name="max_image_storage_mb" min="0"
                                   class="form-control @error('max_image_storage_mb') is-invalid @enderror"
                                   value="{{ old('max_image_storage_mb', 50) }}" required>
                            @error('max_image_storage_mb')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="max_templates">
                                Max Templates <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="max_templates" name="max_templates" min="0"
                                   class="form-control @error('max_templates') is-invalid @enderror"
                                   value="{{ old('max_templates', 5) }}" required>
                            @error('max_templates')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Features --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold py-3">
                    <i class="bi bi-list-check me-2 text-muted"></i>Features
                </div>
                <div class="card-body">
                    <label class="form-label fw-semibold" for="features">
                        Features List <span class="text-muted fw-normal small">(JSON array)</span>
                    </label>
                    <textarea id="features" name="features"
                              class="form-control font-monospace @error('features') is-invalid @enderror"
                              rows="5"
                              placeholder='["Feature 1", "Feature 2", "Feature 3"]'>{{ old('features', '[]') }}</textarea>
                    <div class="form-text">Enter a valid JSON array of feature strings, e.g. ["Unlimited campaigns", "Priority support"]</div>
                    @error('features')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Create Plan
                </button>
                <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </form>
    </div>

    {{-- Sidebar settings --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold py-3">
                <i class="bi bi-toggles me-2 text-muted"></i>Settings
            </div>
            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" class="form-control"
                           value="{{ old('sort_order', 0) }}">
                    <div class="form-text">Lower numbers appear first in listings.</div>
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                           value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="is_active">Plan is Active</label>
                    <div class="form-text text-muted">Inactive plans are not shown to new clients.</div>
                </div>

            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
// Auto-generate slug from name input
const nameInput  = document.getElementById('name');
const slugInput  = document.getElementById('slug');

nameInput?.addEventListener('input', function () {
    if (!slugInput.dataset.modified) {
        slugInput.value = this.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }
});

slugInput?.addEventListener('input', function () {
    this.dataset.modified = '1';
});
</script>
@endpush
