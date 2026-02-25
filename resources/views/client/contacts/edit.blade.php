@extends('layouts.client')

@section('title', 'Edit Contact')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Contact</h4>
        <p class="text-muted mb-0">{{ $contact->email }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard.contacts.show', $contact) }}" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1"></i> View
        </a>
        <a href="{{ route('dashboard.contacts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form id="editContactForm" method="POST" action="{{ route('dashboard.contacts.update', $contact) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        {{-- Email --}}
                        <div class="col-12">
                            <label class="form-label fw-medium">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $contact->email) }}" required>
                            </div>
                            @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- First Name / Last Name --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">First Name</label>
                            <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
                                   value="{{ old('first_name', $contact->first_name) }}" placeholder="Jane">
                            @error('first_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Last Name</label>
                            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                                   value="{{ old('last_name', $contact->last_name) }}" placeholder="Doe">
                            @error('last_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Phone --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Phone</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $contact->phone) }}" placeholder="+1 555 000 0000">
                            </div>
                            @error('phone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Company --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Company</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-building"></i></span>
                                <input type="text" name="company" class="form-control @error('company') is-invalid @enderror"
                                       value="{{ old('company', $contact->company) }}" placeholder="Acme Corp">
                            </div>
                            @error('company')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Status --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach(['subscribed','unsubscribed','bounced','complained'] as $s)
                                    <option value="{{ $s }}"
                                        {{ old('status', $contact->status) === $s ? 'selected' : '' }}>
                                        {{ ucfirst($s) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tags --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Tags</label>
                            <input type="text" name="tags" class="form-control @error('tags') is-invalid @enderror"
                                   value="{{ old('tags', implode(', ', $contact->tags ?? [])) }}"
                                   placeholder="vip, customer, newsletter">
                            <div class="form-text">Separate multiple tags with commas</div>
                            @error('tags')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Lists --}}
                        @if($lists->count())
                        <div class="col-12">
                            <label class="form-label fw-medium">List Memberships</label>
                            <div class="row g-2">
                                @foreach($lists as $list)
                                    <div class="col-sm-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="lists[]" value="{{ $list->id }}"
                                                   id="list_{{ $list->id }}"
                                                   {{ in_array($list->id, old('lists', $contactListIds)) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="list_{{ $list->id }}">
                                                {{ $list->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" form="deleteContactForm" class="btn btn-outline-danger btn-sm"
                                onclick="return confirm('Permanently delete this contact?')">
                            <i class="bi bi-trash me-1"></i> Delete Contact
                        </button>

                        <div class="d-flex gap-2">
                            <a href="{{ route('dashboard.contacts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>

                {{-- Delete form (outside edit form to avoid nesting) --}}
                <form id="deleteContactForm" method="POST"
                      action="{{ route('dashboard.contacts.destroy', $contact) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
