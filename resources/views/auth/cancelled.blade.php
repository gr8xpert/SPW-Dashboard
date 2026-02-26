<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Cancelled — Smart Property Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1E293B 0%, #2563EB 100%);
            min-height: 100vh;
        }
        .auth-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
        }
        .brand-logo {
            font-size: 1.4rem;
            font-weight: 700;
        }
        .icon-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">

    <div class="w-100" style="max-width: 460px;">

        {{-- Brand --}}
        <div class="text-center mb-4">
            <div class="brand-logo text-white">
                <i class="bi bi-buildings me-2"></i>Smart Property Management
            </div>
            <p class="text-white-50 mt-1 small">Property Management Platform</p>
        </div>

        {{-- Card --}}
        <div class="card auth-card p-4 p-md-5">

            <div class="text-center mb-4">
                <div class="icon-circle bg-warning bg-opacity-15 mb-3">
                    <i class="bi bi-x-circle text-warning"></i>
                </div>
                <h5 class="fw-bold mb-1">Subscription Cancelled</h5>
                <p class="text-muted mb-0">
                    Your subscription has been cancelled. To reactivate your account,
                    please contact support or re-subscribe.
                </p>
            </div>

            <div class="d-grid gap-2">
                <a href="mailto:support@smartmailer.com" class="btn btn-primary">
                    <i class="bi bi-envelope me-2"></i>Contact Support
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Login
                </a>
            </div>

        </div>

        <p class="text-center text-white-50 small mt-4">
            &copy; {{ date('Y') }} Smart Property Management. All rights reserved.
        </p>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
