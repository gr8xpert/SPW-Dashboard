<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SmartMailer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1E293B 0%, #2563EB 100%); min-height: 100vh; }
        .auth-card { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.2); max-width: 420px; }
        .brand-logo { font-size: 1.4rem; font-weight: 700; color: #2563EB; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
    <div class="w-100" style="max-width: 420px;">
        <div class="text-center mb-4">
            <div class="brand-logo text-white">
                <i class="bi bi-envelope-paper-fill me-2"></i>SmartMailer
            </div>
            <p class="text-white-50 mt-1">Email Marketing Platform</p>
        </div>

        <div class="card auth-card p-4">
            <h5 class="mb-1 fw-bold">Welcome back</h5>
            <p class="text-muted small mb-4">Sign in to your account</p>

            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email address</label>
                    <input type="email" name="email" class="form-control form-control-lg"
                           value="{{ old('email') }}" placeholder="you@company.com" required autofocus>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <label class="form-label fw-semibold">Password</label>
                        <a href="{{ route('password.request') }}" class="small text-primary text-decoration-none">Forgot password?</a>
                    </div>
                    <input type="password" name="password" class="form-control form-control-lg"
                           placeholder="••••••••" required>
                </div>
                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label text-muted small" for="remember">Keep me signed in</label>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold">
                    Sign In
                </button>
            </form>

            <hr class="my-4">
            <p class="text-center text-muted small mb-0">
                Don't have an account?
                <a href="{{ route('register') }}" class="text-primary fw-semibold text-decoration-none">Start free trial</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
