@extends('layouts.client')

@section('title', 'Add Contact')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>Add Contact</h4>
        <p class="text-muted mb-0">Create a new contact in your list</p>
    </div>
    <a href="{{ route('dashboard.contacts.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Contacts
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('dashboard.contacts.store') }}">
                    @csrf

                    <div class="row g-3">
                        {{-- Email --}}
                        <div class="col-12">
                            <label class="form-label fw-medium">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" placeholder="contact@example.com" required>
                            </div>
                            @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- First Name / Last Name --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">First Name</label>
                            <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
                                   value="{{ old('first_name') }}" placeholder="Jane">
                            @error('first_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Last Name</label>
                            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                                   value="{{ old('last_name') }}" placeholder="Doe">
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
                                       value="{{ old('phone') }}" placeholder="+1 555 000 0000">
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
                                       value="{{ old('company') }}" placeholder="Acme Corp">
                            </div>
                            @error('company')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Status --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="subscribed"   {{ old('status', 'subscribed') === 'subscribed'   ? 'selected' : '' }}>Subscribed</option>
                                <option value="unsubscribed" {{ old('status') === 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
                                <option value="bounced"      {{ old('status') === 'bounced'      ? 'selected' : '' }}>Bounced</option>
                                <option value="complained"   {{ old('status') === 'complained'   ? 'selected' : '' }}>Complained</option>
                            </select>
                            @error('status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tags --}}
                        <div class="col-sm-6">
                            <label class="form-label fw-medium">Tags</label>
                            <input type="text" name="tags" class="form-control @error('tags') is-invalid @enderror"
                                   value="{{ old('tags') }}" placeholder="vip, customer, newsletter">
                            <div class="form-text">Separate multiple tags with commas</div>
                            @error('tags')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Lists --}}
                        @if($lists->count())
                        <div class="col-12">
                            <label class="form-label fw-medium">Add to Lists</label>
                            <div class="row g-2">
                                @foreach($lists as $list)
                                    <div class="col-sm-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="lists[]" value="{{ $list->id }}"
                                                   id="list_{{ $list->id }}"
                                                   {{ in_array($list->id, old('lists', [])) ? 'checked' : '' }}>
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

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('dashboard.contacts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus me-1"></i> Add Contact
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
