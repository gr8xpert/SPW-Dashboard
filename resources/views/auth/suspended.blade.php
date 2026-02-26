<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Suspended — Smart Property Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <div class="text-center" style="max-width: 440px;">
        <div style="font-size:4rem">⚠️</div>
        <h4 class="fw-bold mt-3">Account Suspended</h4>
        <p class="text-muted">Your account has been suspended. Please contact support to resolve this issue.</p>
        <a href="mailto:support@smartmailer.com" class="btn btn-primary">Contact Support</a>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf<button class="btn btn-link text-muted small">Sign out</button>
        </form>
    </div>
</body>
</html>
