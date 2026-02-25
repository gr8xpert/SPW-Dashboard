@extends('layouts.client')

@section('title', $smtpAccount->name . ' — SMTP Account')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-server me-2 text-primary"></i>{{ $smtpAccount->name }}</h4>
        <p class="text-muted mb-0">{{ $smtpAccount->provider }} · {{ $smtpAccount->host }}:{{ $smtpAccount->port }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard.smtp-accounts.edit', $smtpAccount) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="{{ route('dashboard.smtp-accounts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold py-3">Connection Details</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th class="text-muted fw-normal ps-0">Provider</th><td class="text-capitalize">{{ $smtpAccount->provider }}</td></tr>
                    <tr><th class="text-muted fw-normal ps-0">Host</th><td>{{ $smtpAccount->host }}</td></tr>
                    <tr><th class="text-muted fw-normal ps-0">Port</th><td>{{ $smtpAccount->port }}</td></tr>
                    <tr><th class="text-muted fw-normal ps-0">Encryption</th><td class="text-uppercase">{{ $smtpAccount->encryption }}</td></tr>
                    <tr><th class="text-muted fw-normal ps-0">Username</th><td>{{ $smtpAccount->username }}</td></tr>
                    <tr><th class="text-muted fw-normal ps-0">From Email</th><td>{{ $smtpAccount->from_email ?? 'Not set' }}</td></tr>
                    <tr><th class="text-muted fw-normal ps-0">From Name</th><td>{{ $smtpAccount->from_name ?? 'Not set' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold py-3">Status & Limits</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th class="text-muted fw-normal ps-0">Verified</th>
                        <td>
                            @if($smtpAccount->is_verified)
                                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Verified</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">Pending</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal ps-0">Default</th>
                        <td>{{ $smtpAccount->is_default ? 'Yes' : 'No' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal ps-0">Daily Limit</th>
                        <td>{{ $smtpAccount->daily_limit ? number_format($smtpAccount->daily_limit) : 'Unlimited' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal ps-0">Sent Today</th>
                        <td>{{ number_format($smtpAccount->emails_sent_today ?? 0) }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal ps-0">Reputation</th>
                        <td>{{ $smtpAccount->reputation_score ?? 100 }}%</td>
                    </tr>
                </table>

                <div class="mt-3 d-flex gap-2">
                    <form method="POST" action="{{ route('dashboard.smtp-accounts.test', $smtpAccount) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-send me-1"></i> Send Test
                        </button>
                    </form>
                    <form method="POST" action="{{ route('dashboard.smtp-accounts.set-default', $smtpAccount) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-star me-1"></i> Set as Default
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
