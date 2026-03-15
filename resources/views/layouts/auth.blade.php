<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Sign In') — BookStackIn</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHZpZXdCb3g9JzAgMCAzMiAzMic+PHJlY3Qgd2lkdGg9JzMyJyBoZWlnaHQ9JzMyJyByeD0nNicgZmlsbD0nIzRmNDZlNScvPjx0ZXh0IHg9JzUwJScgeT0nNTQlJyBkb21pbmFudC1iYXNlbGluZT0nbWlkZGxlJyB0ZXh0LWFuY2hvcj0nbWlkZGxlJyBmb250LWZhbWlseT0nc3lzdGVtLXVpJyBmb250LXdlaWdodD0nNzAwJyBmb250LXNpemU9JzE0JyBmaWxsPSd3aGl0ZSc+QjwvdGV4dD48L3N2Zz4=" />
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

        
        .btn-block {
            width: 100%; padding: 11px;
            background: #4f46e5; color: #fff;
            font-size: 14px; font-weight: 600;
            border: none; border-radius: 8px;
            cursor: pointer; transition: background .15s;
            font-family: 'DM Sans', sans-serif;
        }

        .btn-block:hover { background: #4338ca; }

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

         .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #64748b;
        }

        .auth-footer a { color: #4f46e5; font-weight: 500; text-decoration: none; }
        .auth-footer a:hover { text-decoration: underline; }

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
        <a href="{{ url('/') }}" class="auth-logo">BookStack<span>In</span></a>

        <div class="auth-tagline">
            <h2>Get paid for every<br /><span>booking you take</span></h2>
            <p>
                Integrate bookings and Paystack payments into any website
                with a single script tag. Verify once, go live instantly.
            </p>

            <div class="auth-code">
                <pre><span class="cm">// Three lines. That's your entire flow.</span>
<span class="fn">Booking</span>.<span class="fn">init</span>({ <span class="fn">publicKey</span>: <span class="str">'pk_live_xxx'</span> });
<span class="kw">const</span> b = <span class="kw">await</span> <span class="fn">Booking</span>.<span class="fn">create</span>({ amount: <span class="nm">500000</span> });
<span class="kw">await</span> <span class="fn">Booking</span>.<span class="fn">pay</span>(b.reference);</pre>
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
            <div class="lbl">Setup time</div>
        </div>
    </div>
</div>

<!-- ── Right form panel ──────────────────────────────────────────────────── -->
<div class="auth-right">
    <div class="auth-form-wrap">

        {{-- Back to home on mobile (since left panel is hidden) --}}
        <div style="margin-bottom:28px; display:none;" class="mobile-back">
            <a href="{{ url('/') }}" style="font-size:20px; font-weight:800; color:#0d0d14; text-decoration:none; letter-spacing:-.3px;">
                Book<span style="color:#4f46e5;">Stack</span>
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