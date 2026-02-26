@extends('layouts.client')

@section('title', 'Campaigns — Smart Property Management')

@section('page-content')

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Campaigns</h4>
        <p class="text-muted mb-0">Manage and monitor all your email campaigns</p>
    </div>
    <a href="{{ route('dashboard.campaigns.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Campaign
    </a>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-primary bg-opacity-10">
                    <i class="bi bi-megaphone text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Campaigns</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-success bg-opacity-10">
                    <i class="bi bi-send-check text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Sent</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['sent']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-secondary bg-opacity-10">
                    <i class="bi bi-file-earmark-text text-secondary"></i>
                </div>
                <div>
                    <div class="text-muted small">Draft</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['draft']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box bg-info bg-opacity-10">
                    <i class="bi bi-calendar-event text-info"></i>
                </div>
                <div>
                    <div class="text-muted small">Scheduled</div>
                    <div class="fw-bold fs-4">{{ number_format($stats['scheduled']) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Form --}}
<div class="card stat-card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('dashboard.campaigns.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5 col-lg-4">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control border-start-0"
                        placeholder="Search by name or subject…"
                    >
                </div>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach(['draft','scheduled','queued','sending','paused','sent','cancelled','failed'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                            {{ ucfirst($s) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                @if(request('search') || request('status'))
                    <a href="{{ route('dashboard.campaigns.index') }}" class="btn btn-outline-secondary ms-1">
                        <i class="bi bi-x-lg me-1"></i> Clear
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Campaigns Table --}}
<div class="card stat-card">
    <div class="card-body p-0">
        @if($campaigns->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-send fs-1 d-block mb-3 opacity-25"></i>
                <p class="mb-1 fw-semibold">No campaigns found</p>
                <p class="small mb-3">
                    @if(request('search') || request('status'))
                        Try adjusting your filters.
                    @else
                        Get started by creating your first campaign.
                    @endif
                </p>
                @unless(request('search') || request('status'))
                    <a href="{{ route('dashboard.campaigns.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> New Campaign
                    </a>
                @endunless
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th class="text-end">Recipients</th>
                            <th class="text-end">Open Rate</th>
                            <th>Created</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($campaigns as $campaign)
                            @php
                                $openRate = $campaign->total_sent > 0
                                    ? round($campaign->total_opened / $campaign->total_sent * 100, 1)
                                    : 0;

                                $badgeClass = match($campaign->status) {
                                    'sent'       => 'bg-success',
                                    'draft'      => 'bg-secondary',
                                    'scheduled'  => 'bg-primary',
                                    'sending'    => 'bg-warning text-dark',
                                    'queued'     => 'bg-warning text-dark',
                                    'paused'     => 'bg-info text-dark',
                                    'cancelled'  => 'bg-dark',
                                    'failed'     => 'bg-danger',
                                    default      => 'bg-secondary',
                                };
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold text-truncate" style="max-width: 200px;">
                                        <a href="{{ route('dashboard.campaigns.show', $campaign) }}"
                                           class="text-decoration-none text-dark stretched-link-override">
                                            {{ $campaign->name }}
                                        </a>
                                    </div>
                                    @if($campaign->completed_at)
                                        <div class="text-muted" style="font-size:.73rem">
                                            Completed {{ $campaign->completed_at->diffForHumans() }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $campaign->subject }}">
                                        {{ $campaign->subject }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $badgeClass }}">
                                        {{ ucfirst($campaign->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    {{ number_format($campaign->total_sent) }}
                                </td>
                                <td class="text-end">
                                    @if($campaign->total_sent > 0)
                                        <span class="fw-semibold {{ $openRate >= 20 ? 'text-success' : ($openRate >= 10 ? 'text-warning' : 'text-danger') }}">
                                            {{ $openRate }}%
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        {{ $campaign->created_at->format('M j, Y') }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('dashboard.campaigns.show', $campaign) }}"
                                           class="btn btn-sm btn-outline-secondary"
                                           title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if(in_array($campaign->status, ['draft', 'scheduled', 'paused']))
                                            <a href="{{ route('dashboard.campaigns.edit', $campaign) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endif
                                        <form method="POST"
                                              action="{{ route('dashboard.campaigns.destroy', $campaign) }}"
                                              onsubmit="return confirm('Delete campaign \'{{ addslashes($campaign->name) }}\'? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($campaigns->hasPages())
                <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top">
                    <small class="text-muted">
                        Showing {{ $campaigns->firstItem() }}–{{ $campaigns->lastItem() }} of {{ $campaigns->total() }} campaigns
                    </small>
                    {{ $campaigns->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

@endsection
