@extends('layouts.admin')

@section('page-title', 'Edit Widget Client')

@section('page-content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Widget Client: {{ $client->domain }}</h1>
        <a href="{{ route('admin.widget-clients.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Clients
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Client Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.widget-clients.update', $client) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="domain" class="form-label">Domain</label>
                            <input type="text" class="form-control" id="domain" name="domain"
                                   value="{{ old('domain', $client->domain) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="api_key" class="form-label">API Key</label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" id="api_key" name="api_key"
                                       value="{{ old('api_key', $client->api_key) }}" readonly>
                                <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('api_key').value)">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="plan" class="form-label">Plan</label>
                                <select class="form-select" id="plan" name="plan" required>
                                    <option value="free" @selected(old('plan', $client->plan) === 'free')>Free</option>
                                    <option value="basic" @selected(old('plan', $client->plan) === 'basic')>Basic</option>
                                    <option value="pro" @selected(old('plan', $client->plan) === 'pro')>Pro</option>
                                    <option value="enterprise" @selected(old('plan', $client->plan) === 'enterprise')>Enterprise</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="subscription_status" class="form-label">Subscription Status</label>
                                <select class="form-select" id="subscription_status" name="subscription_status" required>
                                    <option value="active" @selected(old('subscription_status', $client->subscription_status) === 'active')>Active</option>
                                    <option value="grace" @selected(old('subscription_status', $client->subscription_status) === 'grace')>Grace Period</option>
                                    <option value="expired" @selected(old('subscription_status', $client->subscription_status) === 'expired')>Expired</option>
                                    <option value="manual" @selected(old('subscription_status', $client->subscription_status) === 'manual')>Manual</option>
                                    <option value="internal" @selected(old('subscription_status', $client->subscription_status) === 'internal')>Internal</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="expires_at" class="form-label">Expires At</label>
                                <input type="date" class="form-control" id="expires_at" name="expires_at"
                                       value="{{ old('expires_at', $client->expires_at?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="grace_period_ends_at" class="form-label">Grace Period Ends At</label>
                                <input type="date" class="form-control" id="grace_period_ends_at" name="grace_period_ends_at"
                                       value="{{ old('grace_period_ends_at', $client->grace_period_ends_at?->format('Y-m-d')) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="admin_override" name="admin_override"
                                       value="1" @checked(old('admin_override', $client->admin_override))>
                                <label class="form-check-label" for="admin_override">
                                    Admin Override (bypass subscription checks)
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $client->notes) }}</textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Save Changes
                            </button>
                            <a href="{{ route('admin.widget-clients.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Client Info</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Created</dt>
                        <dd class="col-sm-7">{{ $client->created_at->format('M d, Y') }}</dd>
                        <dt class="col-sm-5">Updated</dt>
                        <dd class="col-sm-7">{{ $client->updated_at->format('M d, Y') }}</dd>
                        <dt class="col-sm-5">License Key</dt>
                        <dd class="col-sm-7">
                            <code>{{ $client->license_key ?? 'None' }}</code>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Danger Zone</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Permanently delete this client and all associated data.</p>
                    <form method="POST" action="{{ route('admin.widget-clients.destroy', $client) }}"
                          onsubmit="return confirm('Are you sure you want to delete this client? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-trash"></i> Delete Client
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
