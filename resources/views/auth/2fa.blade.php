<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication — Smart Property Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1E293B 0%, #2563EB 100%); min-height: 100vh; }
        .auth-card { border-radius: 16px; border: none; max-width: 380px; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
    <div class="card auth-card p-4 shadow-lg" style="max-width:380px; width:100%">
        <div class="text-center mb-4">
            <div style="font-size:2.5rem">🔐</div>
            <h5 class="fw-bold mt-2">Two-Factor Authentication</h5>
            <p class="text-muted small">Enter the 6-digit code from your authenticator app</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('2fa.verify') }}">
            @csrf
            <div class="mb-3">
                <input type="text" name="code" class="form-control form-control-lg text-center"
                       placeholder="000000" maxlength="6" pattern="\d{6}" autofocus required
                       style="font-size: 1.5rem; letter-spacing: .5rem;">
            </div>
            <button type="submit" class="btn btn-primary w-100">Verify</button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-muted small">Back to login</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
