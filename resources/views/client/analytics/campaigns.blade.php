@extends('layouts.client')

@section('title', 'Campaign Analytics — Smart Property Management')

@section('page-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <div class="mb-1">
            <a href="{{ route('dashboard.analytics.index') }}" class="text-muted text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i> Back to Analytics Overview
            </a>
        </div>
        <h4 class="fw-bold mb-0"><i class="bi bi-send me-2 text-primary"></i>Campaign Analytics</h4>
        <p class="text-muted mb-0 mt-1">Detailed engagement metrics for every sent campaign</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Campaign Name</th>
                    <th class="text-end">Sent</th>
                    <th class="text-end">Opens</th>
                    <th class="text-end">Clicks</th>
                    <th class="text-end">Bounces</th>
                    <th class="text-end">Unsubs</th>
                    <th class="text-end">Open Rate</th>
                    <th class="text-end">Click Rate</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $campaign)
                    @php
                        $sent       = $campaign->sent_count   ?? 0;
                        $opens      = $campaign->opens_count  ?? 0;
                        $clicks     = $campaign->clicks_count ?? 0;
                        $bounces    = $campaign->bounces_count ?? 0;
                        $unsubs     = $campaign->unsubs_count  ?? 0;
                        $openRate   = $sent > 0 ? round(($opens  / $sent) * 100, 1) : 0;
                        $clickRate  = $sent > 0 ? round(($clicks / $sent) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $campaign->name }}</div>
                        </td>
                        <td class="text-end fw-semibold">{{ number_format($sent) }}</td>
                        <td class="text-end text-success">{{ number_format($opens) }}</td>
                        <td class="text-end text-info">{{ number_format($clicks) }}</td>
                        <td class="text-end text-warning">{{ number_format($bounces) }}</td>
                        <td class="text-end text-danger">{{ number_format($unsubs) }}</td>
                        <td class="text-end">
                            <span class="fw-semibold {{ $openRate >= 20 ? 'text-success' : ($openRate >= 10 ? 'text-warning' : 'text-muted') }}">
                                {{ $openRate }}%
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="fw-semibold {{ $clickRate >= 3 ? 'text-success' : ($clickRate >= 1 ? 'text-warning' : 'text-muted') }}">
                                {{ $clickRate }}%
                            </span>
                        </td>
                        <td class="text-muted small">
                            {{ $campaign->created_at->format('M d, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-send fs-1 d-block mb-2 opacity-25"></i>
                            No sent campaigns found.
                            <a href="{{ route('dashboard.campaigns.create') }}" class="d-block mt-2">
                                Create your first campaign
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($campaigns->hasPages())
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing {{ $campaigns->firstItem() }}–{{ $campaigns->lastItem() }}
                of {{ $campaigns->total() }} campaigns
            </small>
            {{ $campaigns->links() }}
        </div>
    @endif
</div>

@endsection
