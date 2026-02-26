@extends('layouts.client')

@section('title', 'New Automation — Smart Property Management')

@section('page-content')

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-robot me-2 text-primary"></i>New Automation</h4>
        <p class="text-muted mb-0">Set up a new automated email workflow</p>
    </div>
    <a href="{{ route('dashboard.automations.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Automations
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('dashboard.automations.store') }}">
                    @csrf

                    <div class="row g-3">

                        {{-- Name --}}
                        <div class="col-12">
                            <label class="form-label fw-medium" for="name">
                                Automation Name <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}"
                                placeholder="e.g. Welcome Series, Re-engagement Flow"
                                required
                                autofocus
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Trigger --}}
                        <div class="col-12">
                            <label class="form-label fw-medium" for="trigger_type">
                                Trigger <span class="text-danger">*</span>
                            </label>
                            <select
                                id="trigger_type"
                                name="trigger_type"
                                class="form-select @error('trigger_type') is-invalid @enderror"
                                required
                            >
                                <option value="" disabled {{ old('trigger_type') ? '' : 'selected' }}>
                                    — Select a trigger —
                                </option>
                                <option value="contact_added"
                                    {{ old('trigger_type') === 'contact_added' ? 'selected' : '' }}>
                                    When a contact is added
                                </option>
                                <option value="list_subscribed"
                                    {{ old('trigger_type') === 'list_subscribed' ? 'selected' : '' }}>
                                    When contact is added to a list
                                </option>
                                <option value="tag_added"
                                    {{ old('trigger_type') === 'tag_added' ? 'selected' : '' }}>
                                    When a tag is added
                                </option>
                                <option value="contact_updated"
                                    {{ old('trigger_type') === 'contact_updated' ? 'selected' : '' }}>
                                    When contact is updated
                                </option>
                                <option value="date_field"
                                    {{ old('trigger_type') === 'date_field' ? 'selected' : '' }}>
                                    On a specific date field (e.g. birthday)
                                </option>
                                <option value="manual"
                                    {{ old('trigger_type') === 'manual' ? 'selected' : '' }}>
                                    Manual trigger
                                </option>
                                <option value="engagement_drop"
                                    {{ old('trigger_type') === 'engagement_drop' ? 'selected' : '' }}>
                                    When engagement drops
                                </option>
                            </select>
                            @error('trigger_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Info note --}}
                        <div class="col-12">
                            <div class="alert alert-info border-0 bg-info bg-opacity-10 mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                After creating, you can add steps to build your workflow.
                            </div>
                        </div>

                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('dashboard.automations.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-robot me-1"></i> Create Automation
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

@endsection
