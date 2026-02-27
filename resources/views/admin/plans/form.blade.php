@extends('layouts.admin')

@section('title', isset($plan->id) ? 'Edit Plan' : 'New Plan')
@section('page-title', isset($plan->id) ? 'Edit Plan' : 'New Plan')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold">
            <i class="bi bi-tags me-2 text-primary"></i>
            {{ isset($plan->id) ? 'Edit Plan: ' . $plan->name : 'Create New Plan' }}
        </h4>
        <p class="text-muted mb-0">{{ isset($plan->id) ? 'Update plan details and limits' : 'Define a new subscription plan' }}</p>
    </div>
    <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Plans
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ isset($plan->id) ? route('admin.plans.update', $plan) : route('admin.plans.store') }}">
            @csrf
            @if(isset($plan->id))
                @method('PUT')
            @endif

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold py-3">Plan Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $plan->name ?? '') }}" required maxlength="50">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
                            <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug', $plan->slug ?? '') }}" required maxlength="50"
                                   placeholder="e.g. starter, professional">
                            <div class="form-text">URL-safe identifier. Use lowercase letters and hyphens only.</div>
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Monthly Price (€) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" name="price_monthly" step="0.01" min="0"
                                       class="form-control @error('price_monthly') is-invalid @enderror"
                                       value="{{ old('price_monthly', $plan->price_monthly ?? '0.00') }}" required>
                            </div>
                            @error('price_monthly')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Yearly Price (€) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" name="price_yearly" step="0.01" min="0"
                                       class="form-control @error('price_yearly') is-invalid @enderror"
                                       value="{{ old('price_yearly', $plan->price_yearly ?? '0.00') }}" required>
                            </div>
                            @error('price_yearly')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold py-3">Usage Limits</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Max Contacts <span class="text-danger">*</span></label>
                            <input type="number" name="max_contacts" min="0"
                                   class="form-control @error('max_contacts') is-invalid @enderror"
                                   value="{{ old('max_contacts', $plan->max_contacts ?? 500) }}" required>
                            @error('max_contacts')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Max Emails/Month <span class="text-danger">*</span></label>
                            <input type="number" name="max_emails_per_month" min="0"
                                   class="form-control @error('max_emails_per_month') is-invalid @enderror"
                                   value="{{ old('max_emails_per_month', $plan->max_emails_per_month ?? 1000) }}" required>
                            @error('max_emails_per_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Max Users <span class="text-danger">*</span></label>
                            <input type="number" name="max_users" min="1"
                                   class="form-control @error('max_users') is-invalid @enderror"
                                   value="{{ old('max_users', $plan->max_users ?? 1) }}" required>
                            @error('max_users')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Max Templates <span class="text-danger">*</span></label>
                            <input type="number" name="max_templates" min="0"
                                   class="form-control @error('max_templates') is-invalid @enderror"
                                   value="{{ old('max_templates', $plan->max_templates ?? 5) }}" required>
                            @error('max_templates')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Image Storage (MB) <span class="text-danger">*</span></label>
                            <input type="number" name="max_image_storage_mb" min="0"
                                   class="form-control @error('max_image_storage_mb') is-invalid @enderror"
                                   value="{{ old('max_image_storage_mb', $plan->max_image_storage_mb ?? 50) }}" required>
                            @error('max_image_storage_mb')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold py-3">Features List</div>
                <div class="card-body">
                    <label class="form-label fw-semibold">
                        Features <span class="text-danger">*</span>
                        <span class="text-muted small fw-normal">(JSON array)</span>
                    </label>
                    <textarea name="features[]" id="featuresInput" class="form-control @error('features') is-invalid @enderror"
                              rows="2" style="display:none"></textarea>

                    {{-- Alpine-powered dynamic feature list --}}
                    <div x-data="featureEditor()" x-init="init()">
                        <div class="mb-2" x-show="features.length > 0">
                            <template x-for="(feature, index) in features" :key="index">
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" name="features[]" class="form-control"
                                           x-model="features[index]" placeholder="Feature description">
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                            @click="remove(index)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" @click="add()">
                            <i class="bi bi-plus-lg me-1"></i> Add Feature
                        </button>
                    </div>

                    @error('features')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>
                    {{ isset($plan->id) ? 'Update Plan' : 'Create Plan' }}
                </button>
                <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent fw-semibold py-3">Settings</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control"
                           value="{{ old('sort_order', $plan->sort_order ?? 0) }}">
                    <div class="form-text">Lower numbers appear first</div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1"
                           {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="isActive">Plan is Active</label>
                    <div class="form-text">Inactive plans are not shown to clients</div>
                </div>
            </div>
        </div>

        @if(isset($plan->id))
        <div class="card border-0 shadow-sm border-danger">
            <div class="card-header bg-transparent fw-semibold py-3 text-danger">Danger Zone</div>
            <div class="card-body">
                <p class="text-muted small">Deleting a plan will fail if clients are subscribed to it.</p>
                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}"
                      onsubmit="return confirm('Delete this plan? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-trash me-1"></i> Delete Plan
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function featureEditor() {
    return {
        features: @json(old('features', isset($plan) ? ($plan->features ?? []) : [])),
        init() {},
        add() { this.features.push(''); },
        remove(index) { this.features.splice(index, 1); },
    }
}

// Auto-generate slug from name
document.querySelector('[name="name"]')?.addEventListener('input', function() {
    const slugField = document.getElementById('slug');
    if (!slugField.dataset.modified) {
        slugField.value = this.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }
});
document.getElementById('slug')?.addEventListener('input', function() {
    this.dataset.modified = '1';
});
</script>
@endpush
