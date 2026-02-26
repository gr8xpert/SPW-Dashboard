<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Smart Property Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1E293B 0%, #2563EB 100%); min-height: 100vh; }
        .auth-card { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
        .plan-badge { background: #EFF6FF; color: #2563EB; border-radius: 20px; padding: .2rem .75rem; font-size: .8rem; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
    <div class="w-100" style="max-width: 480px;">
        <div class="text-center mb-4">
            <div style="font-size:1.4rem;font-weight:700;color:#fff;">
                <i class="bi bi-buildings me-2"></i>Smart Property Management
            </div>
            <p class="text-white-50 mt-1">Start your 14-day free trial</p>
        </div>

        <div class="card auth-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h5 class="mb-1 fw-bold">Create your account</h5>
                    <p class="text-muted small mb-0">No credit card required</p>
                </div>
                <span class="plan-badge"><i class="bi bi-lightning-charge me-1"></i>Free Plan</span>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register.post') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Company Name</label>
                    <input type="text" name="company_name" class="form-control"
                           value="{{ old('company_name') }}" placeholder="Acme Corp" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Your Name</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name') }}" placeholder="John Doe" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email') }}" placeholder="john@acme.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Min. 8 characters" required minlength="8">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control"
                           placeholder="Repeat password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold">
                    Create Free Account
                </button>
            </form>

            <p class="text-center text-muted small mt-4 mb-0">
                Already have an account?
                <a href="{{ route('login') }}" class="text-primary fw-semibold text-decoration-none">Sign in</a>
            </p>
        </div>

        <p class="text-center text-white-50 small mt-3">
            By registering, you agree to our Terms of Service and Privacy Policy.
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
