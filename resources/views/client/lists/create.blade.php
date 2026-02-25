@extends('layouts.client')

@section('title', 'Create List')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-collection me-2 text-primary"></i>Create List</h4>
        <p class="text-muted mb-0">Create a new contact list for segmenting your audience</p>
    </div>
    <a href="{{ route('dashboard.lists.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Lists
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('dashboard.lists.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-medium">List Name <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                               class="form-control form-control-lg @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               placeholder="e.g. Newsletter Subscribers"
                               required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Give your list a clear, descriptive name.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Description</label>
                        <textarea name="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Describe who belongs in this list...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Optional. Helps your team understand the list's purpose.</div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('dashboard.lists.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-collection me-1"></i> Create List
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
