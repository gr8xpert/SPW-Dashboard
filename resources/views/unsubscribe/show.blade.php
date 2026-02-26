<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unsubscribe — Smart Property Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <div style="max-width: 440px; width: 100%;">
        <div class="card shadow-sm border-0 rounded-3 p-4 text-center">
            <div style="font-size:3rem">📧</div>
            <h5 class="fw-bold mt-3">Unsubscribe</h5>
            <p class="text-muted">Are you sure you want to unsubscribe <strong>{{ $contact->email }}</strong>?</p>
            <form method="POST" action="{{ route('unsubscribe.process', $token) }}">
                @csrf
                <button type="submit" class="btn btn-danger w-100">Yes, Unsubscribe Me</button>
            </form>
            <p class="text-muted small mt-3 mb-0">You will no longer receive marketing emails.</p>
        </div>
    </div>
</body>
</html>
