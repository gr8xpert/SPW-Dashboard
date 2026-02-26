<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email — Smart Property Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #F8FAFC; min-height: 100vh; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
    <div class="text-center" style="max-width: 440px;">
        <div style="font-size: 4rem;">📧</div>
        <h4 class="fw-bold mt-3">Verify your email</h4>
        <p class="text-muted">
            We've sent a verification link to <strong>{{ auth()->user()->email }}</strong>.
            Click the link in the email to activate your account.
        </p>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn btn-primary">Resend Verification Email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button class="btn btn-link text-muted small">Sign out</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
