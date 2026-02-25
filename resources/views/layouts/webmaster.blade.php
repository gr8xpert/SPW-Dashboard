@extends('layouts.app')

@section('content')
<!-- Webmaster Sidebar -->
<aside class="sidebar" style="background: #1a2332;">
    <div class="brand">
        <h5><i class="bi bi-wrench-adjustable text-info me-2"></i>SPW</h5>
        <small class="text-white-50">Webmaster Panel</small>
    </div>

    <nav class="mt-2 pb-4">
        <p class="nav-section">My Work</p>
        <a href="{{ route('dashboard.my-tickets.index') }}" class="nav-link {{ request()->routeIs('dashboard.my-tickets.*') ? 'active' : '' }}">
            <i class="bi bi-ticket-detailed"></i> My Tickets
        </a>
        <a href="{{ route('dashboard.my-timesheet.index') }}" class="nav-link {{ request()->routeIs('dashboard.my-timesheet.*') ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i> Timesheet
        </a>
    </nav>
</aside>

<!-- Main Content -->
<div class="main-content">
    <header class="topbar d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold">@yield('page-title', 'Webmaster')</h6>
        <div class="dropdown">
            <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle me-1"></i> {{ auth()->user()->name }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item text-danger">Logout</button>
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
