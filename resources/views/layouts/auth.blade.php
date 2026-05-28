<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Sign In') — BookInStack</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet" />

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --ink:      #0f1117;
            --ink-mid:  #3a3f52;
            --muted:    #7e8599;
            --border:   #e8eaef;
            --surface:  #f5f6f8;
            --accent:   #2563eb;
            --accent-h: #1d4ed8;
            --green:    #059669;
            --white:    #ffffff;
        }

        body {
            font-family: 'Sora', sans-serif;
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Left panel ──────────────────────────────────────── */
        .auth-left {
            flex: 1;
            background: var(--ink);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 44px 48px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow: hidden;
        }

        /* Subtle grid */
        .auth-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        /* Single glow */
        .auth-left::after {
            content: '';
            position: absolute;
            width: 480px; height: 480px;
            background: radial-gradient(circle, rgba(37,99,235,.16) 0%, transparent 70%);
            top: -80px; right: -80px;
            pointer-events: none;
        }

        .auth-left-inner {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            justify-content: space-between;
        }

        .auth-logo {
            font-size: 17px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.3px;
            text-decoration: none;
            display: block;
        }

        .auth-logo span { color: var(--accent); }

        /* Main copy block */
        .auth-copy { margin-top: 56px; }

        .auth-eyebrow {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: rgba(255,255,255,.3);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .auth-eyebrow::before {
            content: '';
            display: block;
            width: 18px;
            height: 1px;
            background: var(--accent);
        }

        .auth-copy h2 {
            font-size: clamp(24px, 2.8vw, 34px);
            font-weight: 700;
            color: #fff;
            line-height: 1.1;
            letter-spacing: -.04em;
            margin-bottom: 14px;
        }

        .auth-copy h2 em {
            font-style: normal;
            color: transparent;
            -webkit-text-stroke: 1px rgba(255,255,255,.3);
        }

        .auth-copy p {
            font-size: 14px;
            color: rgba(255,255,255,.35);
            line-height: 1.7;
            max-width: 320px;
        }

        /* Feature points */
        .auth-points {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 36px;
            padding-top: 32px;
            border-top: 1px solid rgba(255,255,255,.06);
        }

        .auth-point {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 13px;
            color: rgba(255,255,255,.45);
            line-height: 1.5;
        }

        .auth-point-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--accent);
            flex-shrink: 0;
            margin-top: 5px;
        }

        /* Bottom stats */
        .auth-stats {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 32px;
            padding-top: 28px;
            border-top: 1px solid rgba(255,255,255,.06);
            flex-wrap: wrap;
        }

        .auth-stat .num {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.04em;
        }

        .auth-stat .lbl {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 10px;
            color: rgba(255,255,255,.25);
            margin-top: 3px;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        /* ── Right panel ─────────────────────────────────────── */
        .auth-right {
            width: 520px;
            flex-shrink: 0;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 44px;
            overflow-y: auto;
            border-left: 1px solid var(--border);
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 360px;
        }

        .auth-form-wrap h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -.035em;
            margin-bottom: 6px;
        }

        .auth-form-wrap .subtitle {
            font-size: 13.5px;
            color: var(--muted);
            margin-bottom: 32px;
            line-height: 1.55;
        }

        /* Form fields */
        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 7px;
            letter-spacing: -.01em;
        }

        .form-control {
            width: 100%;
            padding: 10px 13px;
            border: 1px solid var(--border);
            border-radius: 7px;
            font-size: 13.5px;
            font-family: 'Sora', sans-serif;
            color: var(--ink);
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            background: var(--white);
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }

        .field-error {
            color: #dc2626;
            font-size: 11.5px;
            margin-top: 4px;
        }

        .btn-submit {
            width: 100%;
            padding: 11px;
            background: var(--ink);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-family: 'Sora', sans-serif;
            letter-spacing: -.01em;
            transition: background .15s;
            margin-top: 4px;
        }

        .btn-submit:hover { background: #1a1f2e; }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 13.5px;
            color: var(--muted);
        }

        .auth-footer a {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
        }

        .auth-footer a:hover { text-decoration: underline; }

        .auth-switch {
            text-align: center;
            margin-top: 20px;
            font-size: 13.5px;
            color: var(--muted);
        }

        .auth-switch a {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
        }

        .auth-switch a:hover { text-decoration: underline; }

        .alert {
            padding: 11px 13px;
            border-radius: 7px;
            font-size: 13.5px;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .alert-error   { background: #fee2e2; color: #dc2626; }
        .alert-success { background: #ecfdf5; color: #065f46; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            color: var(--muted);
            font-size: 12px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-top: 1px solid var(--border);
        }

        /* Mobile logo */
        .mobile-logo {
            display: none;
            font-size: 17px;
            font-weight: 700;
            color: var(--ink);
            text-decoration: none;
            letter-spacing: -.3px;
            margin-bottom: 32px;
        }

        .mobile-logo span { color: var(--accent); }

        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 768px) {
            .auth-left  { display: none; }
            .auth-right {
                width: 100%;
                padding: 40px 24px;
                align-items: flex-start;
                border-left: none;
            }
            .mobile-logo { display: block; }
        }
    </style>
</head>
<body>

<!-- Left brand panel -->
<div class="auth-left">
    <div class="auth-left-inner">

        <div>
            <a href="{{ url('/') }}" class="auth-logo">BookIn<span>Stack</span></a>

            <div class="auth-copy">
                <div class="auth-eyebrow">For Nigerian Businesses</div>
                <h2>
                    Take bookings.<br>
                    <em>Get paid instantly.</em>
                </h2>
                <p>
                    A complete booking and payment system for your business.
                    Set up once, earn every time a customer books.
                </p>

                <div class="auth-points">
                    <div class="auth-point">
                        <div class="auth-point-dot"></div>
                        <span>Payments straight to your bank account</span>
                    </div>
                    <div class="auth-point">
                        <div class="auth-point-dot"></div>
                        <span>Manage all bookings from one simple dashboard</span>
                    </div>
                    <div class="auth-point">
                        <div class="auth-point-dot"></div>
                        <span>QR tickets, staff check-in, attendance tracking</span>
                    </div>
                    <div class="auth-point">
                        <div class="auth-point-dot"></div>
                        <span>Let customers negotiate price via WhatsApp</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="auth-stats">
            <div class="auth-stat">
                <div class="num">₦0</div>
                <div class="lbl">Monthly fee</div>
            </div>
            <div class="auth-stat">
                <div class="num">~60s</div>
                <div class="lbl">To go live</div>
            </div>
            <div class="auth-stat">
                <div class="num">95%</div>
                <div class="lbl">Goes to you</div>
            </div>
        </div>

    </div>
</div>

<!-- Right form panel -->
<div class="auth-right">
    <div class="auth-form-wrap">

        <a href="{{ url('/') }}" class="mobile-logo">BookIn<span>Stack</span></a>

        @yield('content')

    </div>
</div>

</body>
</html>
