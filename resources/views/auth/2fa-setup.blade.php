<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enable Two-Factor Authentication — SmartMailer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1E293B 0%, #2563EB 100%); min-height: 100vh; }
        .auth-card { border-radius: 16px; border: none; max-width: 480px; }
        .qr-box { background: #f8f9fa; border-radius: 12px; padding: 1.5rem; text-align: center; }
        .secret-box { font-family: monospace; font-size: 1.1rem; letter-spacing: 2px; background: #f0f4ff; padding: .75rem 1rem; border-radius: 8px; word-break: break-all; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
    <div class="card auth-card p-4 shadow-lg" style="width:100%">
        <div class="text-center mb-4">
            <div style="font-size:2.5rem">🔐</div>
            <h5 class="fw-bold mt-2">Enable Two-Factor Authentication</h5>
            <p class="text-muted small">Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.)</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="qr-box mb-3">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($qrCode) }}"
                 alt="QR Code" width="180" height="180" class="mb-3">
            <p class="text-muted small mb-1">Can't scan? Enter this secret manually:</p>
            <div class="secret-box">{{ $secret }}</div>
        </div>

        <form method="POST" action="{{ route('dashboard.2fa.enable') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Enter 6-digit verification code to confirm:</label>
                <input type="text" name="code" class="form-control form-control-lg text-center"
                       placeholder="000000" maxlength="6" pattern="\d{6}" autofocus required
                       style="font-size: 1.5rem; letter-spacing: .5rem;">
            </div>
            <button type="submit" class="btn btn-success w-100 fw-semibold">Enable 2FA</button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('dashboard.settings.index') }}" class="text-muted small">Cancel</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
