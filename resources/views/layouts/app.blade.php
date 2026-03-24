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

        /* Full Page Loader */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .page-loader.active {
            display: flex;
        }
        .page-loader .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #E2E8F0;
            border-top-color: var(--sm-primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        .page-loader .loader-text {
            margin-top: 1rem;
            color: #64748B;
            font-size: 0.9rem;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Full Page Loader -->
    <div id="pageLoader" class="page-loader">
        <div class="spinner"></div>
        <div class="loader-text">Loading...</div>
    </div>

    @yield('content')

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    @stack('scripts')

    <script>
        // Page Loader utility
        window.PageLoader = {
            show: function(text) {
                const loader = document.getElementById('pageLoader');
                if (text) {
                    loader.querySelector('.loader-text').textContent = text;
                }
                loader.classList.add('active');
            },
            hide: function() {
                const loader = document.getElementById('pageLoader');
                loader.classList.remove('active');
                loader.querySelector('.loader-text').textContent = 'Loading...';
            }
        };

        // Auto-show loader on form submissions (non-AJAX)
        document.addEventListener('submit', function(e) {
            const form = e.target;
            // Skip if form has data-no-loader attribute
            if (form.hasAttribute('data-no-loader')) return;
            // Skip AJAX forms (those handled by JS)
            if (form.hasAttribute('data-ajax')) return;

            PageLoader.show('Saving changes...');
        });

        // Auto-show loader on links with data-loader attribute
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[data-loader]');
            if (link && !e.defaultPrevented) {
                PageLoader.show(link.getAttribute('data-loader') || 'Loading...');
            }
        });

        // Hide loader when page loads (for back button navigation)
        window.addEventListener('pageshow', function() {
            PageLoader.hide();
        });
    </script>
</body>
</html>
