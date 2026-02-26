@extends('layouts.app')

@section('content')
<!-- Admin Sidebar -->
<aside class="sidebar" style="background: #0F172A;">
    <div class="brand">
        <h5><i class="bi bi-shield-check text-danger me-2"></i>SPW Admin</h5>
        <small class="text-white-50">Super Admin Panel</small>
    </div>

    <nav class="mt-2 pb-4">
        <p class="nav-section">Overview</p>
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('admin.subscription-status') }}" class="nav-link {{ request()->routeIs('admin.subscription-status') ? 'active' : '' }}">
            <i class="bi bi-heart-pulse"></i> Subscription Health
        </a>

        <p class="nav-section">Clients & Billing</p>
        <a href="{{ route('admin.clients.index') }}" class="nav-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
            <i class="bi bi-buildings"></i> Clients
        </a>
        <a href="{{ route('admin.widget-clients.index') }}" class="nav-link {{ request()->routeIs('admin.widget-clients.*') ? 'active' : '' }}">
            <i class="bi bi-window-stack"></i> Widget Config
        </a>
        <a href="{{ route('admin.plans.index') }}" class="nav-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
            <i class="bi bi-tags"></i> Plans
        </a>

        <p class="nav-section">Support</p>
        <a href="{{ route('admin.tickets.index') }}" class="nav-link {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
            <i class="bi bi-ticket-detailed"></i> Tickets
        </a>
        <a href="{{ route('admin.webmasters.index') }}" class="nav-link {{ request()->routeIs('admin.webmasters.*') ? 'active' : '' }}">
            <i class="bi bi-person-gear"></i> Webmasters
        </a>
        <a href="{{ route('admin.credits.overview') }}" class="nav-link {{ request()->routeIs('admin.credits.*') ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i> Credit Hours
        </a>

        <p class="nav-section">Content</p>
        <a href="{{ route('admin.knowledge-base.index') }}" class="nav-link {{ request()->routeIs('admin.knowledge-base.*') ? 'active' : '' }}">
            <i class="bi bi-book"></i> Knowledge Base
        </a>

        <p class="nav-section">Email</p>
        <a href="{{ route('admin.suppressions.index') }}" class="nav-link {{ request()->routeIs('admin.suppressions.*') ? 'active' : '' }}">
            <i class="bi bi-slash-circle"></i> Suppressions
        </a>

        <p class="nav-section">System</p>
        <a href="{{ route('admin.audit-log.index') }}" class="nav-link {{ request()->routeIs('admin.audit-log.*') ? 'active' : '' }}">
            <i class="bi bi-journal-text"></i> Audit Log
        </a>
    </nav>
</aside>

<!-- Main Content -->
<div class="main-content">
    @if(session('impersonated_by'))
    <div class="alert alert-warning mb-0 rounded-0 text-center py-2" style="font-size: 0.85rem;">
        <i class="bi bi-eye me-1"></i>
        You are viewing as <strong>{{ auth()->user()->client->company_name ?? 'Client' }}</strong> &mdash;
        <a href="{{ route('admin.stop-impersonating') }}" class="fw-bold">Exit impersonation</a>
    </div>
    @endif

    <header class="topbar d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold">@yield('page-title', 'Admin')</h6>
        <div class="dropdown">
            <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle me-1"></i> {{ auth()->user()->name }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </header>

    <main class="p-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('page-content')
    </main>
</div>
@endsection
