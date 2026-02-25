@extends('layouts.client')

@section('title', 'Help Center')

@section('page-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-question-circle me-2 text-primary"></i>Help Center</h4>
        <p class="text-muted mb-0">Find answers to common questions and learn how to use your widget</p>
    </div>
    <a href="{{ route('dashboard.tickets.create') }}" class="btn btn-outline-primary">
        <i class="bi bi-ticket-detailed me-1"></i> Contact Support
    </a>
</div>

{{-- Search --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('dashboard.help.index') }}">
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text"
                       name="q"
                       class="form-control border-start-0"
                       placeholder="Search articles..."
                       value="{{ request('q') }}">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>
</div>

{{-- Quick Links --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 text-center">
            <div class="card-body p-4">
                <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-rocket-takeoff fs-5 text-primary"></i>
                </div>
                <h6 class="fw-semibold mb-1">Getting Started</h6>
                <p class="text-muted small mb-0">Installation and first-time setup guide</p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 text-center">
            <div class="card-body p-4">
                <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-palette fs-5 text-success"></i>
                </div>
                <h6 class="fw-semibold mb-1">Customization</h6>
                <p class="text-muted small mb-0">Styling, colors, and layout options</p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 text-center">
            <div class="card-body p-4">
                <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-wordpress fs-5 text-warning"></i>
                </div>
                <h6 class="fw-semibold mb-1">WordPress</h6>
                <p class="text-muted small mb-0">Plugin installation and shortcodes</p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 text-center">
            <div class="card-body p-4">
                <div class="rounded-circle bg-info bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-credit-card fs-5 text-info"></i>
                </div>
                <h6 class="fw-semibold mb-1">Billing</h6>
                <p class="text-muted small mb-0">Plans, payments, and invoices</p>
            </div>
        </div>
    </div>
</div>

{{-- Articles by Category --}}
@forelse($categories ?? [] as $category)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex align-items-center justify-content-between">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-{{ $category['icon'] ?? 'folder' }} me-2 text-primary"></i>
                {{ $category['name'] }}
            </h6>
            <span class="badge bg-light text-muted border">{{ count($category['articles'] ?? []) }} articles</span>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                @forelse($category['articles'] ?? [] as $article)
                    <a href="{{ route('dashboard.help.show', $article['slug'] ?? $article['id'] ?? '#') }}"
                       class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 px-4">
                        <i class="bi bi-file-earmark-text text-muted"></i>
                        <div class="flex-grow-1">
                            <div class="fw-medium small">{{ $article['title'] }}</div>
                            @if(!empty($article['excerpt']))
                                <div class="text-muted small text-truncate" style="max-width: 500px;">{{ $article['excerpt'] }}</div>
                            @endif
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>
                @empty
                    <div class="list-group-item text-center text-muted py-3">
                        <small>No articles in this category yet.</small>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@empty
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-book fs-1 d-block mb-2 opacity-25"></i>
            <p class="small mb-2">Knowledge base articles are being added. Check back soon!</p>
            <a href="{{ route('dashboard.tickets.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-ticket-detailed me-1"></i> Submit a Support Ticket
            </a>
        </div>
    </div>
@endforelse

{{-- Still need help? --}}
<div class="card border-0 shadow-sm mt-4 bg-primary bg-opacity-10">
    <div class="card-body p-4 text-center">
        <h6 class="fw-bold mb-2">Still need help?</h6>
        <p class="text-muted small mb-3">Can't find what you're looking for? Our support team is here to help.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('dashboard.tickets.create') }}" class="btn btn-primary">
                <i class="bi bi-ticket-detailed me-1"></i> Open a Ticket
            </a>
            <a href="mailto:support@smartpropertywidget.com" class="btn btn-outline-primary">
                <i class="bi bi-envelope me-1"></i> Email Us
            </a>
        </div>
    </div>
</div>
@endsection
