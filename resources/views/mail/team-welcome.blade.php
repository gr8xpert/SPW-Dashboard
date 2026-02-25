<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;">
        <tr>
            <td align="center" style="padding:40px 20px;">
                <table width="560" cellpadding="0" cellspacing="0"
                       style="max-width:560px;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#4a90d9;padding:30px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">
                                {{ config('app.name') }}
                            </h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:40px 30px;">
                            <h2 style="margin:0 0 16px;color:#1a1a2e;font-size:20px;">
                                Welcome to the team, {{ $user->name }}!
                            </h2>
                            <p style="margin:0 0 24px;color:#555;line-height:1.6;">
                                You've been added as a team member on <strong>{{ config('app.name') }}</strong>.
                                Use the credentials below to log in and get started.
                            </p>

                            {{-- Credentials box --}}
                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="background:#f8f9fa;border-radius:6px;margin-bottom:28px;">
                                <tr>
                                    <td style="padding:24px;">
                                        <p style="margin:0 0 4px;color:#888;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Email</p>
                                        <p style="margin:0 0 20px;color:#1a1a2e;font-size:15px;">{{ $user->email }}</p>

                                        <p style="margin:0 0 4px;color:#888;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Temporary Password</p>
                                        <p style="margin:0;display:inline-block;background:#e8f0fe;color:#1a1a2e;font-family:monospace;font-size:15px;padding:8px 14px;border-radius:4px;">{{ $plainPassword }}</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 28px;color:#555;line-height:1.6;">
                                Please log in and update your password as soon as possible to keep your account secure.
                            </p>

                            {{-- CTA --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/login') }}"
                                           style="display:inline-block;background:#4a90d9;color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:6px;font-size:16px;font-weight:600;">
                                            Log In Now
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 30px;background:#f8f9fa;border-top:1px solid #e8e8e8;text-align:center;">
                            <p style="margin:0;color:#aaa;font-size:12px;">
                                This invitation was sent by {{ config('app.name') }}. If you weren't expecting it, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
