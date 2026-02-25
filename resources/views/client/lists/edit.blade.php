@extends('layouts.client')

@section('title', 'Edit List')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit List</h4>
        <p class="text-muted mb-0">{{ $list->name }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard.lists.show', $list) }}" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1"></i> View
        </a>
        <a href="{{ route('dashboard.lists.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form id="editListForm" method="POST" action="{{ route('dashboard.lists.update', $list) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="form-label fw-medium">List Name <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                               class="form-control form-control-lg @error('name') is-invalid @enderror"
                               value="{{ old('name', $list->name) }}"
                               required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Description</label>
                        <textarea name="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Describe this list...">{{ old('description', $list->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-danger btn-sm"
                                onclick="if(confirm('Delete this list? Contacts will not be deleted.')) document.getElementById('deleteListForm').submit()">
                            <i class="bi bi-trash me-1"></i> Delete List
                        </button>

                        <div class="d-flex gap-2">
                            <a href="{{ route('dashboard.lists.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>

                {{-- Delete form outside edit form --}}
                <form id="deleteListForm" method="POST"
                      action="{{ route('dashboard.lists.destroy', $list) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
