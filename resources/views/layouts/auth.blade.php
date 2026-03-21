<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Sign In') — BookInStack</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
        }

        /* ── Left panel — brand ───────────────────────────────── */
        .auth-left {
            flex: 1;
            background: #0d0d14;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        /* Gradient orbs */
        .auth-left::before, .auth-left::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            opacity: .3;
            pointer-events: none;
        }

        .auth-left::before {
            width: 420px; height: 420px;
            background: radial-gradient(circle, #4f46e5, transparent);
            top: -80px; left: -80px;
        }

        .auth-left::after {
            width: 320px; height: 320px;
            background: radial-gradient(circle, #7c3aed, transparent);
            bottom: -60px; right: -60px;
        }

        .auth-left-inner { position: relative; z-index: 1; }

        .auth-logo {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.4px;
            text-decoration: none;
            display: block;
            margin-bottom: 60px;
        }

        .auth-logo span { color: #818cf8; }

        .auth-tagline {
            flex: 1;
        }

        .auth-tagline h2 {
            font-size: clamp(26px, 3vw, 36px);
            font-weight: 800;
            color: #fff;
            line-height: 1.15;
            letter-spacing: -.03em;
            margin-bottom: 14px;
        }

        .auth-tagline h2 span {
            background: linear-gradient(135deg, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .auth-tagline p {
            font-size: 15px;
            color: #64748b;
            line-height: 1.65;
            max-width: 340px;
        }

        /* Code snippet on the left panel */
        .auth-code {
            margin-top: 40px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 12px;
            padding: 18px 20px;
        }

        .auth-code pre {
            font-family: 'DM Mono', monospace;
            font-size: 12px;
            line-height: 1.7;
            color: #cdd6f4;
        }

        .kw  { color: #cba6f7; }
        .str { color: #a6e3a1; }
        .fn  { color: #89dceb; }
        .cm  { color: #45475a; }
        .nm  { color: #fab387; }

        /* Testimonial / stat strip */
        .auth-stats {
            display: flex;
            gap: 24px;
            padding-top: 40px;
            border-top: 1px solid rgba(255,255,255,.06);
            flex-wrap: wrap;
        }

        .auth-stat .num {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.04em;
        }

        .auth-stat .lbl {
            font-size: 12px;
            color: #475569;
            margin-top: 2px;
        }

        /* ── Right panel — form ───────────────────────────────── */
        .auth-right {
            width: 560px;
            flex-shrink: 0;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            overflow-y: auto;
        }

        .auth-form-wrap { width: 100%; max-width: 360px; }

        .auth-form-wrap h1 {
            font-size: 24px;
            font-weight: 800;
            color: #0d0d14;
            letter-spacing: -.03em;
            margin-bottom: 6px;
        }

        .auth-form-wrap .subtitle {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        /* Form fields */
        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #0d0d14;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            color: #0d0d14;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            background: #fff;
        }

        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79,70,229,.1);
        }

        .field-error { color: #dc2626; font-size: 12px; margin-top: 4px; }

        .btn-submit {
            width: 100%;
            padding: 11px;
            background: #4f46e5;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: all .15s;
            margin-top: 4px;
        }

        .btn-submit:hover { background: #4338ca; }

        .auth-switch {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #64748b;
        }

        .auth-switch a {
            color: #4f46e5;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-switch a:hover { text-decoration: underline; }

        .alert {
            padding: 11px 13px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .alert-error   { background: #fee2e2; color: #dc2626; }
        .alert-success { background: #dcfce7; color: #15803d; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 18px 0;
            color: #94a3b8;
            font-size: 12px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-top: 1px solid #e2e8f0;
        }

        /* ── Responsive ── hide left panel on small screens ───── */
        @media (max-width: 768px) {
            .auth-left  { display: none; }
            .auth-right {
                width: 100%;
                padding: 32px 24px;
                align-items: flex-start;
                padding-top: 60px;
            }
        }
    </style>
</head>
<body>

<!-- ── Left brand panel ──────────────────────────────────────────────────── -->
<div class="auth-left">
    <div class="auth-left-inner">
        <a href="{{ url('/') }}" class="auth-logo">BookIn<span>Stack</span></a>

        <div class="auth-tagline">
            <h2>Take bookings.<br /><span>Get paid instantly.</span></h2>
            <p>
                BookInStack gives your business a complete booking and payment system.
                Set up once, earn every time a customer books.
            </p>

            <div style="display:flex;flex-direction:column;gap:10px;margin-top:20px;">
                @foreach([
                    ['💳','Payments straight to your bank account'],
                    ['📋','Manage all bookings from one simple dashboard'],
                    ['🎟','QR tickets, staff check-in, attendance tracking'],
                    ['💬','Let customers negotiate price via WhatsApp'],
                ] as [$icon,$text])
                <div style="display:flex;align-items:center;gap:12px;padding:11px 14px;background:rgba(255,255,255,.07);border-radius:9px;border:1px solid rgba(255,255,255,.1);">
                    <span style="font-size:18px;flex-shrink:0;">{{ $icon }}</span>
                    <span style="font-size:13px;color:rgba(255,255,255,.8);line-height:1.4;">{{ $text }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="auth-stats">
        <div class="auth-stat">
            <div class="num">₦0</div>
            <div class="lbl">Monthly fee</div>
        </div>
        <div class="auth-stat">
            <div class="num">5min</div>
            <div class="lbl">To go live</div>
        </div>
    </div>
</div>

<!-- ── Right form panel ──────────────────────────────────────────────────── -->
<div class="auth-right">
    <div class="auth-form-wrap">

        {{-- Back to home on mobile (since left panel is hidden) --}}
        <div style="margin-bottom:28px; display:none;" class="mobile-back">
            <a href="{{ url('/') }}" style="font-size:20px; font-weight:800; color:#0d0d14; text-decoration:none; letter-spacing:-.3px;">
                BookIn<span style="color:#4f46e5;">Stack</span>
            </a>
        </div>

        @yield('content')

    </div>
</div>

<style>
    @media (max-width: 768px) {
        .mobile-back { display: block !important; }
    }
</style>

</body>
</html>