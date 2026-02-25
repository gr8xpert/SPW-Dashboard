<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unsubscribed — SmartMailer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <div style="max-width: 440px; width: 100%;">
        <div class="card shadow-sm border-0 rounded-3 p-4 text-center">
            <div style="font-size:3rem">✅</div>
            <h5 class="fw-bold mt-3">You've been unsubscribed</h5>
            <p class="text-muted">
                <strong>{{ $contact->email }}</strong> has been removed from our mailing list.
                You will no longer receive marketing emails.
            </p>
            <p class="text-muted small">Changed your mind?
                Contact us at <a href="mailto:support@smartmailer.com">support@smartmailer.com</a>
            </p>
        </div>
    </div>
</body>
</html>
