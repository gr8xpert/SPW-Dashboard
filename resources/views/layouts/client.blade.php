@extends('layouts.app')

@section('content')
<!-- Sidebar -->
<aside class="sidebar">
    <div class="brand">
        <h5><i class="bi bi-house-door-fill text-primary me-2"></i>SPW</h5>
        <small class="text-white-50">{{ auth()->user()->client->company_name }}</small>
    </div>

    <nav class="mt-2 pb-4">
        <p class="nav-section">Main</p>
        <a href="{{ route('dashboard.home') }}" class="nav-link {{ request()->routeIs('dashboard.home') || request()->routeIs('dashboard.index') ? 'active' : '' }}">
            <i class="bi bi-grid"></i> Dashboard
        </a>

        <p class="nav-section">Widget</p>
        <a href="{{ route('dashboard.widget.index') }}" class="nav-link {{ request()->routeIs('dashboard.widget.index') ? 'active' : '' }}">
            <i class="bi bi-window-stack"></i> Widget Status
        </a>
        <a href="{{ route('dashboard.widget.analytics') }}" class="nav-link {{ request()->routeIs('dashboard.widget.analytics') ? 'active' : '' }}">
            <i class="bi bi-graph-up"></i> Widget Analytics
        </a>
        <a href="{{ route('dashboard.widget.setup') }}" class="nav-link {{ request()->routeIs('dashboard.widget.setup') ? 'active' : '' }}">
            <i class="bi bi-gear-wide-connected"></i> Widget Setup
        </a>
        <a href="{{ route('dashboard.widget.inquiry-contacts') }}" class="nav-link {{ request()->routeIs('dashboard.widget.inquiry-contacts') ? 'active' : '' }}">
            <i class="bi bi-person-lines-fill"></i> Inquiry Contacts
        </a>

        <p class="nav-section">Email Marketing</p>
        <a href="{{ route('dashboard.campaigns.index') }}" class="nav-link {{ request()->routeIs('dashboard.campaigns.*') ? 'active' : '' }}">
            <i class="bi bi-send"></i> Campaigns
        </a>
        <a href="{{ route('dashboard.contacts.index') }}" class="nav-link {{ request()->routeIs('dashboard.contacts.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Contacts
        </a>
        <a href="{{ route('dashboard.lists.index') }}" class="nav-link {{ request()->routeIs('dashboard.lists.*') ? 'active' : '' }}">
            <i class="bi bi-collection"></i> Lists
        </a>

        <p class="nav-section">Design</p>
        <a href="{{ route('dashboard.templates.index') }}" class="nav-link {{ request()->routeIs('dashboard.templates.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-richtext"></i> Templates
        </a>
        <a href="{{ route('dashboard.images.index') }}" class="nav-link {{ request()->routeIs('dashboard.images.*') ? 'active' : '' }}">
            <i class="bi bi-images"></i> Image Library
        </a>
        <a href="{{ route('dashboard.brand-kit.edit') }}" class="nav-link {{ request()->routeIs('dashboard.brand-kit.*') ? 'active' : '' }}">
            <i class="bi bi-palette"></i> Brand Kit
        </a>

        <p class="nav-section">Automation</p>
        <a href="{{ route('dashboard.automations.index') }}" class="nav-link {{ request()->routeIs('dashboard.automations.*') ? 'active' : '' }}">
            <i class="bi bi-robot"></i> Automations
        </a>

        <p class="nav-section">Analytics</p>
        <a href="{{ route('dashboard.analytics.index') }}" class="nav-link {{ request()->routeIs('dashboard.analytics.*') ? 'active' : '' }}">
            <i class="bi bi-bar-chart"></i> Email Analytics
        </a>

        <p class="nav-section">Support</p>
        <a href="{{ route('dashboard.tickets.index') }}" class="nav-link {{ request()->routeIs('dashboard.tickets.*') ? 'active' : '' }}">
            <i class="bi bi-ticket-detailed"></i> Tickets
        </a>
        <a href="{{ route('dashboard.credits.index') }}" class="nav-link {{ request()->routeIs('dashboard.credits.*') ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i> Credit Hours
        </a>
        <a href="{{ route('dashboard.help.index') }}" class="nav-link {{ request()->routeIs('dashboard.help.*') ? 'active' : '' }}">
            <i class="bi bi-question-circle"></i> Help Center
        </a>

        <p class="nav-section">Account</p>
        <a href="{{ route('dashboard.smtp-accounts.index') }}" class="nav-link {{ request()->routeIs('dashboard.smtp-accounts.*') ? 'active' : '' }}">
            <i class="bi bi-server"></i> SMTP Accounts
        </a>
        <a href="{{ route('dashboard.team.index') }}" class="nav-link {{ request()->routeIs('dashboard.team.*') ? 'active' : '' }}">
            <i class="bi bi-person-badge"></i> Team
        </a>
        <a href="{{ route('dashboard.billing.index') }}" class="nav-link {{ request()->routeIs('dashboard.billing.*') ? 'active' : '' }}">
            <i class="bi bi-credit-card"></i> Billing
        </a>
        <a href="{{ route('dashboard.settings.index') }}" class="nav-link {{ request()->routeIs('dashboard.settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear"></i> Settings
        </a>
        <a href="{{ route('dashboard.privacy.index') }}" class="nav-link {{ request()->routeIs('dashboard.privacy.*') ? 'active' : '' }}">
            <i class="bi bi-shield-check"></i> Privacy
        </a>
    </nav>
</aside>

<!-- Main Content -->
<div class="main-content">
    @if(session('impersonated_by'))
    <div class="alert alert-warning mb-0 rounded-0 text-center py-2" style="font-size: 0.85rem;">
        <i class="bi bi-eye me-1"></i>
        You are viewing as <strong>{{ auth()->user()->client->company_name ?? 'Client' }}</strong> &mdash;
        <a href="{{ route('admin.stop-impersonating') }}" class="fw-bold text-dark">Exit impersonation</a>
    </div>
    @endif

    <!-- Topbar -->
    <header class="topbar d-flex align-items-center justify-content-between">
        <div>
            @yield('page-title-inline', '')
        </div>
        <div class="d-flex align-items-center gap-3">
            @php $usage = auth()->user()->client->getCurrentUsage() @endphp
            @if($usage)
                <small class="text-muted">
                    {{ number_format($usage->emails_sent) }} /
                    {{ number_format(auth()->user()->client->plan->max_emails_per_month) }} emails
                </small>
            @endif
            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i> {{ auth()->user()->name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('dashboard.settings.index') }}">Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="p-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
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

        @yield('page-content')
    </main>
</div>
@endsection
