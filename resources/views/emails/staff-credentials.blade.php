<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Your staff account is ready</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f8fafc; color: #0d0d14; }
        .wrap { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
        .header { background: #0d0d14; padding: 28px 32px; }
        .header-brand { color: #818cf8; font-size: 18px; font-weight: 700; letter-spacing: -.02em; }
        .header-sub { color: #94a3b8; font-size: 13px; margin-top: 4px; }
        .body { padding: 32px; }
        h2 { font-size: 20px; font-weight: 700; margin-bottom: 8px; }
        p { font-size: 14px; color: #374151; line-height: 1.6; margin-bottom: 16px; }
        .creds { background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 10px; padding: 20px 24px; margin: 20px 0; }
        .cred-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #ede9fe; font-size: 14px; }
        .cred-row:last-child { border-bottom: none; }
        .cred-label { color: #6b7280; font-weight: 500; }
        .cred-value { font-family: 'Courier New', monospace; font-weight: 700; color: #111827; }
        .cta { display: block; text-align: center; background: #4f46e5; color: #fff; text-decoration: none; padding: 13px 24px; border-radius: 8px; font-weight: 700; font-size: 15px; margin: 24px 0 0; }
        .warning { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #92400e; margin-top: 20px; }
        .footer { padding: 20px 32px; border-top: 1px solid #f1f5f9; font-size: 12px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
<div class="wrap">

    <div class="header">
        <div class="header-brand">BookInStack</div>
        <div class="header-sub">Staff Account Credentials</div>
    </div>

    <div class="body">
        <h2>Welcome, {{ $staff->name }} 👋</h2>
        <p>
            <strong>{{ $admin->business_name }}</strong> has added you as a staff member
            on their BookInStack account. Use the credentials below to log in.
        </p>

        <div class="creds">
            <div class="cred-row">
                <span class="cred-label">Login URL</span>
                <span class="cred-value">{{ config('app.url') }}/login</span>
            </div>
            <div class="cred-row">
                <span class="cred-label">Email</span>
                <span class="cred-value">{{ $staff->email }}</span>
            </div>
            <div class="cred-row">
                <span class="cred-label">Password</span>
                <span class="cred-value">{{ $plainPassword }}</span>
            </div>
        </div>

        <a href="{{ config('app.url') }}/login" class="cta">
            Log in to your account →
        </a>

        <div class="warning">
            🔒 <strong>Please change your password</strong> after your first login.
            Do not share these credentials with anyone.
        </div>
    </div>

    <div class="footer">
        This email was sent by BookInStack on behalf of {{ $admin->business_name }}.
        If you weren't expecting this, please ignore it.
    </div>

</div>
</body>
</html>