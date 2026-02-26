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

    {{-- Generate New License Key --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom pt-4 pb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-key me-2 text-primary"></i>Generate New License Key</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.license-keys.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label for="client_id" class="form-label">Client</label>
                    <select class="form-select @error('client_id') is-invalid @enderror" id="client_id" name="client_id" required>
                        <option value="">Select client...</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                                {{ $client->company_name }} {{ $client->domain ? '(' . $client->domain . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="plan_id" class="form-label">Plan</label>
                    <select class="form-select @error('plan_id') is-invalid @enderror" id="plan_id" name="plan_id" required>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>
                                {{ $plan->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('plan_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="expires_in" class="form-label">Expires In</label>
                    <select class="form-select" id="expires_in" name="expires_in">
                        <option value="1_month">1 Month</option>
                        <option value="1_year" selected>1 Year</option>
                        <option value="5_years">5 Years</option>
                        <option value="never">Never</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-key me-1"></i> Generate Key
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- License Keys Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">All License Keys</h6>
            <span class="badge bg-secondary">{{ $licenseKeys->total() }} total</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>License Key</th>
                            <th>Client</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Domain</th>
                            <th>Activated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($licenseKeys as $key)
                            <tr>
                                <td class="text-muted small">{{ $key->id }}</td>
                                <td>
                                    <code class="user-select-all">{{ $key->license_key }}</code>
                                </td>
                                <td>{{ $key->client?->company_name ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-info text-dark">{{ $key->plan?->name ?? '—' }}</span>
                                </td>
                                <td>
                                    @if($key->status === 'revoked')
                                        <span class="badge bg-danger">Revoked</span>
                                    @elseif($key->status === 'activated')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Unused</span>
                                    @endif
                                </td>
                                <td class="small">{{ $key->activated_domain ?? '—' }}</td>
                                <td class="small">{{ $key->activated_at?->format('M d, Y') ?? '—' }}</td>
                                <td>
                                    @if($key->status !== 'revoked')
                                        <form method="POST" action="{{ route('admin.license-keys.revoke', $key) }}" class="d-inline"
                                              onsubmit="return confirm('Revoke this license key?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x-lg"></i> Revoke
                                            </button>
                                        </form>
                                    @endif
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
            <div class="card-footer bg-white border-top">
                {{ $licenseKeys->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
