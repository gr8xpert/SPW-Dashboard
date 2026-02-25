<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Property Widget')</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --sm-primary: #2563EB;
            --sm-dark: #1E293B;
            --sm-sidebar-width: 260px;
        }
        body { background: #F8FAFC; font-family: 'Segoe UI', system-ui, sans-serif; }
        .sidebar {
            width: var(--sm-sidebar-width);
            min-height: 100vh;
            background: var(--sm-dark);
            position: fixed;
            top: 0; left: 0;
            overflow-y: auto;
            z-index: 1000;
        }
        .sidebar .brand { padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,.1); }
        .sidebar .brand h5 { color: #fff; margin: 0; font-weight: 700; font-size: 1.1rem; }
        .sidebar .nav-link {
            color: rgba(255,255,255,.7);
            padding: .6rem 1.5rem;
            border-radius: 0;
            font-size: .875rem;
            transition: all .15s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,.08);
        }
        .sidebar .nav-link i { width: 20px; margin-right: 8px; }
        .sidebar .nav-section {
            font-size: .7rem;
            font-weight: 600;
            color: rgba(255,255,255,.35);
            padding: 1rem 1.5rem .25rem;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .main-content { margin-left: var(--sm-sidebar-width); }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #E2E8F0;
            padding: .75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .stat-card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .stat-card .icon-box {
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
    @yield('content')

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    @stack('scripts')
</body>
</html>
