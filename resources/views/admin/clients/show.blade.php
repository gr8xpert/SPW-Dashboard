@extends('layouts.admin')

@section('title', $client->company_name . ' — SmartMailer Admin')
@section('page-title', 'Client Detail')

@section('page-content')

{{-- Header --}}
<div class="d-flex align-items-start justify-content-between mb-4">
    <div>
        <div class="mb-1">
            <a href="{{ route('admin.clients.index') }}" class="text-muted text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i> Back to Clients
            </a>
        </div>
        <h4 class="fw-bold mb-1">
            {{ $client->company_name }}
            @php
                $statusColors = [
                    'active'    => 'success',
                    'trial'     => 'info',
                    'suspended' => 'danger',
                    'cancelled' => 'secondary',
                ];
                $statusColor = $statusColors[$client->status] ?? 'secondary';
            @endphp
            <span class="badge bg-{{ $statusColor }} ms-2 fs-6">
                {{ ucfirst($client->status) }}
            </span>
        </h4>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        {{-- Suspend / Activate --}}
        @if($client->status !== 'suspended')
            <form method="POST" action="{{ route('admin.clients.suspend', $client) }}">
                @csrf
                <button type="submit" class="btn btn-warning"
                        onclick="return confirm('Suspend {{ addslashes($client->company_name) }}?')">
                    <i class="bi bi-pause-circle me-1"></i> Suspend
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.clients.activate', $client) }}">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-play-circle me-1"></i> Activate
                </button>
            </form>
        @endif

        {{-- Impersonate --}}
        <form method="POST" action="{{ route('admin.clients.impersonate', $client) }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-eye me-1"></i> Impersonate
            </button>
        </form>
    </div>
</div>

{{-- Info Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-tags fs-4 text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Plan</div>
                    <div class="fw-bold">{{ $client->plan->name ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-{{ $statusColor }} bg-opacity-10 p-3">
                    <i class="bi bi-circle-fill fs-4 text-{{ $statusColor }}"></i>
                </div>
                <div>
                    <div class="text-muted small">Status</div>
                    <div class="fw-bold">{{ ucfirst($client->status) }}</div>
                    @if($client->status === 'trial' && $client->trial_ends_at)
                        <div class="text-muted" style="font-size:.75rem">
                            Trial ends {{ $client->trial_ends_at->format('M d, Y') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-secondary bg-opacity-10 p-3">
                    <i class="bi bi-calendar3 fs-4 text-secondary"></i>
                </div>
                <div>
                    <div class="text-muted small">Created</div>
                    <div class="fw-bold">{{ $client->created_at->format('M d, Y') }}</div>
                    <div class="text-muted" style="font-size:.75rem">
                        {{ $client->timezone ?? 'UTC' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 p-3">
                    <i class="bi bi-people fs-4 text-info"></i>
                </div>
                <div>
                    <div class="text-muted small">Users</div>
                    <div class="fw-bold">{{ $client->users->count() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Usage --}}
@if($client->plan)
    @php
        $emailsSent  = $usage->emails_sent ?? 0;
        $emailsLimit = $client->plan->max_emails_per_month ?? 0;
        $usagePct    = $emailsLimit > 0 ? min(100, ($emailsSent / $emailsLimit) * 100) : 0;
        $barClass    = $usagePct >= 90 ? 'bg-danger' : ($usagePct >= 70 ? 'bg-warning' : 'bg-primary');
    @endphp
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-send me-2 text-primary"></i>Monthly Email Usage
            </h6>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small">Emails sent this month</span>
                <span class="fw-semibold small">
                    {{ number_format($emailsSent) }} / {{ number_format($emailsLimit) }}
                </span>
            </div>
            <div class="progress mb-2" style="height: 10px;">
                <div class="progress-bar {{ $barClass }}" style="width: {{ $usagePct }}%" role="progressbar"
                     aria-valuenow="{{ $usagePct }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <small class="text-muted">
                {{ number_format($usagePct, 1) }}% of monthly limit used
                @if($usagePct >= 90)
                    <span class="text-danger ms-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>Near limit
                    </span>
                @endif
            </small>
        </div>
    </div>
@endif

<div class="row g-4">
    {{-- Users Table --}}
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-people me-2 text-info"></i>Users
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($client->users as $user)
                            <tr>
                                <td class="fw-medium">{{ $user->name }}</td>
                                <td class="text-muted small">{{ $user->email }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border text-capitalize">
                                        {{ $user->role ?? 'member' }}
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    {{ $user->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="bi bi-person-x d-block mb-1 opacity-25 fs-3"></i>
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Audit Log --}}
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-clock-history me-2 text-secondary"></i>Audit Log
                </h6>
                <span class="badge bg-light text-dark border">Last 20 entries</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Action</th>
                            <th>Description</th>
                            <th>By</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditLogs as $log)
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace small">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="text-muted small" style="max-width: 260px;">
                                    <span class="d-inline-block text-truncate w-100" title="{{ $log->description }}">
                                        {{ $log->description }}
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    {{ optional($log->user)->name ?? 'System' }}
                                </td>
                                <td class="text-muted small" title="{{ $log->created_at->format('M d, Y H:i:s') }}">
                                    {{ $log->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="bi bi-journal-x d-block mb-1 opacity-25 fs-3"></i>
                                    No audit logs yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
