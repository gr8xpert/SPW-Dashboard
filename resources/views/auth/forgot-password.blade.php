<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Smart Property Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1E293B 0%, #2563EB 100%); min-height: 100vh; }
        .auth-card { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.2); max-width: 420px; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
    <div class="w-100" style="max-width: 420px;">
        <div class="card auth-card p-4">
            <h5 class="fw-bold mb-1">Reset your password</h5>
            <p class="text-muted small mb-4">Enter your email and we'll send you a reset link.</p>

            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email address</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('login') }}" class="text-muted small">Back to login</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
