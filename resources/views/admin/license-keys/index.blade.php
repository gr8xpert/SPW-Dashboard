@extends('layouts.admin')

@section('page-title', 'License Keys')

@section('page-content')
<div class="container-fluid">
    <h1 class="h3 mb-4">License Keys</h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Generate New License Key --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Generate New License Key</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.license-keys.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label for="client_id" class="form-label">Client</label>
                    <select class="form-select" id="client_id" name="client_id" required>
                        <option value="">Select client...</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->domain }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="plan" class="form-label">Plan</label>
                    <select class="form-select" id="plan" name="plan" required>
                        <option value="free">Free</option>
                        <option value="basic">Basic</option>
                        <option value="pro" selected>Pro</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="expires_at" class="form-label">Expires At</label>
                    <input type="date" class="form-control" id="expires_at" name="expires_at">
                    <div class="form-text">Leave blank for no expiration.</div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-key"></i> Generate Key
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Newly Generated Key Display --}}
    @if(session('generated_key'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <strong>New License Key Generated:</strong>
            <div class="input-group mt-2" style="max-width: 500px;">
                <input type="text" class="form-control font-monospace" id="generated-key" value="{{ session('generated_key') }}" readonly>
                <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('generated-key').value)">
                    <i class="bi bi-clipboard"></i> Copy
                </button>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- License Keys Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">All License Keys</h5>
            <span class="badge bg-secondary">{{ $licenseKeys->total() }} total</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>License Key</th>
                            <th>Client</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Activated At</th>
                            <th>Expires At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($licenseKeys as $key)
                            <tr>
                                <td>{{ $key->id }}</td>
                                <td>
                                    <code class="user-select-all">{{ Str::limit($key->key, 20) }}</code>
                                </td>
                                <td>{{ $key->client?->domain ?? '—' }}</td>
                                <td><span class="badge bg-info text-dark">{{ ucfirst($key->plan) }}</span></td>
                                <td>
                                    @if($key->revoked_at)
                                        <span class="badge bg-danger">Revoked</span>
                                    @elseif($key->activated_at)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Unused</span>
                                    @endif
                                </td>
                                <td>{{ $key->activated_at?->format('M d, Y') ?? '—' }}</td>
                                <td>{{ $key->expires_at?->format('M d, Y') ?? 'Never' }}</td>
                                <td>
                                    @unless($key->revoked_at)
                                        <form method="POST" action="{{ route('admin.license-keys.revoke', $key) }}" class="d-inline"
                                              onsubmit="return confirm('Revoke this license key?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x-lg"></i> Revoke
                                            </button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No license keys found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($licenseKeys->hasPages())
            <div class="card-footer">
                {{ $licenseKeys->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
