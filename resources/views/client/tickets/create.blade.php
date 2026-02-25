@extends('layouts.client')

@section('title', 'Create Ticket')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Create Ticket</h4>
        <p class="text-muted mb-0">Submit a new support request and we'll get back to you shortly</p>
    </div>
    <a href="{{ route('dashboard.tickets.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Tickets
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Ticket Details</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('dashboard.tickets.store') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- Subject --}}
                    <div class="mb-3">
                        <label class="form-label fw-medium">Subject <span class="text-danger">*</span></label>
                        <input type="text"
                               name="subject"
                               class="form-control @error('subject') is-invalid @enderror"
                               value="{{ old('subject') }}"
                               placeholder="Brief summary of your issue"
                               required>
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        {{-- Category --}}
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                                <option value="">Select a category...</option>
                                <option value="widget_issue"  {{ old('category') === 'widget_issue'  ? 'selected' : '' }}>Widget Issue</option>
                                <option value="billing"       {{ old('category') === 'billing'       ? 'selected' : '' }}>Billing</option>
                                <option value="feature_request" {{ old('category') === 'feature_request' ? 'selected' : '' }}>Feature Request</option>
                                <option value="bug_report"    {{ old('category') === 'bug_report'    ? 'selected' : '' }}>Bug Report</option>
                                <option value="account"       {{ old('category') === 'account'       ? 'selected' : '' }}>Account</option>
                                <option value="wordpress"     {{ old('category') === 'wordpress'     ? 'selected' : '' }}>WordPress Plugin</option>
                                <option value="customization" {{ old('category') === 'customization' ? 'selected' : '' }}>Customization</option>
                                <option value="other"         {{ old('category') === 'other'         ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Priority --}}
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                <option value="low"    {{ old('priority', 'medium') === 'low'    ? 'selected' : '' }}>Low -- General question</option>
                                <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium -- Something isn't working right</option>
                                <option value="high"   {{ old('priority', 'medium') === 'high'   ? 'selected' : '' }}>High -- Significant impact</option>
                                <option value="urgent" {{ old('priority', 'medium') === 'urgent' ? 'selected' : '' }}>Urgent -- Widget is completely down</option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="mb-3">
                        <label class="form-label fw-medium">Description <span class="text-danger">*</span></label>
                        <textarea name="description"
                                  class="form-control @error('description') is-invalid @enderror"
                                  rows="6"
                                  placeholder="Please describe your issue in detail. Include steps to reproduce, expected vs. actual behavior, and any relevant URLs."
                                  required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Attachment --}}
                    <div class="mb-4">
                        <label class="form-label fw-medium">Attachment <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="file"
                               name="attachment"
                               class="form-control @error('attachment') is-invalid @enderror"
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.zip,.txt">
                        <div class="form-text">Max 10MB. Accepted: JPG, PNG, GIF, PDF, ZIP, TXT</div>
                        @error('attachment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('dashboard.tickets.index') }}" class="text-muted text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i> Submit Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
