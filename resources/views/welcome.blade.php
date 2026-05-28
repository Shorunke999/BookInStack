<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BookInStack — Bookings & Payments for Businesses</title>
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

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Sora', sans-serif;
            color: var(--ink);
            background: var(--white);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Nav ───────────────────────────────────────────────── */
        nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 clamp(24px, 5vw, 80px);
            z-index: 100;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
        }

        .nav-logo {
            font-size: 16px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -.3px;
            text-decoration: none;
        }

        .nav-logo span { color: var(--accent); }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .nav-link {
            font-size: 13.5px;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
            padding: 7px 14px;
            border-radius: 6px;
            transition: color .15s, background .15s;
            letter-spacing: -.01em;
        }

        .nav-link:hover { color: var(--ink); background: var(--surface); }

        .nav-cta {
            background: var(--ink);
            color: var(--white) !important;
            font-weight: 600;
            letter-spacing: -.01em;
        }

        .nav-cta:hover { background: #1a1f2e !important; }

        /* ── Hero ──────────────────────────────────────────────── */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 120px clamp(24px, 5vw, 80px) 80px;
            background: var(--ink);
            position: relative;
            overflow: hidden;
        }

        /* Subtle grid texture */
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        /* Single focused glow */
        .hero::after {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(37,99,235,.18) 0%, transparent 70%);
            top: -100px; right: -100px;
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 2;
            max-width: 640px;
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,.4);
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            margin-bottom: 32px;
        }

        .hero-tag::before {
            content: '';
            display: block;
            width: 20px;
            height: 1px;
            background: var(--accent);
        }

        .hero h1 {
            font-size: clamp(38px, 5.5vw, 64px);
            font-weight: 700;
            color: #fff;
            line-height: 1.08;
            letter-spacing: -.04em;
            margin-bottom: 24px;
        }

        .hero h1 em {
            font-style: normal;
            color: transparent;
            -webkit-text-stroke: 1px rgba(255,255,255,.35);
        }

        .hero-sub {
            font-size: clamp(15px, 1.8vw, 17px);
            color: rgba(255,255,255,.45);
            line-height: 1.7;
            max-width: 480px;
            margin-bottom: 40px;
            font-weight: 400;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
            padding: 12px 24px;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            letter-spacing: -.01em;
            transition: background .2s, transform .15s;
        }

        .btn-primary:hover {
            background: var(--accent-h);
            transform: translateY(-1px);
        }

        .btn-ghost {
            color: rgba(255,255,255,.5);
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            letter-spacing: -.01em;
            transition: color .15s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-ghost:hover { color: rgba(255,255,255,.85); }

        .btn-ghost::after {
            content: '→';
            transition: transform .15s;
        }

        .btn-ghost:hover::after { transform: translateX(3px); }

        /* ── Three points below CTA ───────────────────────────── */
        .hero-points {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 52px;
            padding-top: 40px;
            border-top: 1px solid rgba(255,255,255,.07);
            max-width: 480px;
        }

        .hero-point {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            font-size: 13.5px;
            color: rgba(255,255,255,.5);
            line-height: 1.5;
        }

        .hero-point-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--accent);
            flex-shrink: 0;
            margin-top: 6px;
        }

        /* ── Stats ─────────────────────────────────────────────── */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: clamp(40px, 7vw, 100px);
            flex-wrap: wrap;
            padding: 36px clamp(24px, 5vw, 80px);
            border-bottom: 1px solid var(--border);
            background: var(--white);
        }

        .stat-item { text-align: center; }

        .stat-item .num {
            font-size: 26px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -.05em;
            line-height: 1;
        }

        .stat-item .lbl {
            font-size: 12px;
            color: var(--muted);
            margin-top: 5px;
            letter-spacing: .01em;
        }

        /* ── Sections ──────────────────────────────────────────── */
        .section {
            padding: clamp(72px, 9vw, 112px) clamp(24px, 5vw, 80px);
        }

        .section-eyebrow {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--accent);
            margin-bottom: 18px;
        }

        .section h2 {
            font-size: clamp(26px, 3.5vw, 38px);
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -.035em;
            line-height: 1.15;
            max-width: 500px;
            margin-bottom: 14px;
        }

        .section-sub {
            font-size: 15.5px;
            color: var(--muted);
            max-width: 440px;
            line-height: 1.65;
            margin-bottom: 56px;
        }

        /* ── Features grid ────────────────────────────────────── */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(268px, 1fr));
            gap: 1px;
            background: var(--border);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .feature-card {
            background: var(--white);
            padding: 28px;
            transition: background .2s;
        }

        .feature-card:hover { background: #fafbfd; }

        .feature-label {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 10px;
            font-weight: 500;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 10px;
        }

        .feature-card h4 {
            font-size: 15px;
            font-weight: 600;
            color: var(--ink);
            letter-spacing: -.02em;
            margin-bottom: 8px;
        }

        .feature-card p {
            font-size: 13.5px;
            color: var(--muted);
            line-height: 1.65;
        }

        /* ── Steps (dark) ─────────────────────────────────────── */
        .steps {
            background: var(--ink);
            padding: clamp(72px, 9vw, 112px) clamp(24px, 5vw, 80px);
        }

        .steps .section-eyebrow { color: rgba(255,255,255,.3); }

        .steps h2 {
            font-size: clamp(26px, 3.5vw, 38px);
            font-weight: 700;
            color: #fff;
            letter-spacing: -.035em;
            line-height: 1.15;
            max-width: 420px;
            margin-bottom: 56px;
        }

        .steps-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0;
            max-width: 900px;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 12px;
            overflow: hidden;
        }

        .step {
            padding: 32px 28px;
            border-right: 1px solid rgba(255,255,255,.07);
            border-bottom: 1px solid rgba(255,255,255,.07);
        }

        .step:last-child { border-right: none; }

        .step-num {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            font-weight: 500;
            color: rgba(255,255,255,.2);
            letter-spacing: .06em;
            margin-bottom: 20px;
        }

        .step h4 {
            font-size: 14.5px;
            font-weight: 600;
            color: #fff;
            letter-spacing: -.02em;
            margin-bottom: 8px;
        }

        .step p {
            font-size: 13px;
            color: rgba(255,255,255,.35);
            line-height: 1.65;
        }

        /* ── Pricing ──────────────────────────────────────────── */
        .pricing-wrap {
            max-width: 440px;
            margin: 0 auto;
            text-align: left;
        }

        .pricing-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 36px;
            background: var(--white);
        }

        .pricing-header {
            margin-bottom: 32px;
        }

        .pricing-badge {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 10px;
            font-weight: 500;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--green);
            background: #ecfdf5;
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            margin-bottom: 16px;
        }

        .pricing-headline {
            font-size: 22px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -.03em;
            margin-bottom: 6px;
        }

        .pricing-desc {
            font-size: 13.5px;
            color: var(--muted);
        }

        .pricing-features {
            list-style: none;
            margin-bottom: 32px;
        }

        .pricing-features li {
            font-size: 14px;
            color: var(--ink-mid);
            padding: 11px 0;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -.01em;
        }

        .pricing-features li:last-child { border-bottom: none; }

        .pricing-features li::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--green);
            flex-shrink: 0;
        }

        .btn-pricing {
            display: block;
            width: 100%;
            background: var(--ink);
            color: #fff;
            padding: 13px;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            letter-spacing: -.01em;
            transition: background .2s;
        }

        .btn-pricing:hover { background: #1a1f2e; }

        /* ── Footer ──────────────────────────────────────────── */
        footer {
            border-top: 1px solid var(--border);
            padding: 28px clamp(24px, 5vw, 80px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            background: var(--white);
        }

        footer .logo {
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -.02em;
        }

        footer .logo span { color: var(--accent); }

        footer p {
            font-size: 12.5px;
            color: var(--muted);
        }

        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 640px) {
            .nav-link:not(.nav-cta) { display: none; }
            .steps-list { grid-template-columns: 1fr 1fr; }
            .step { border-right: none; }
            .step:nth-child(odd) { border-right: 1px solid rgba(255,255,255,.07); }
        }

        @media (max-width: 400px) {
            .steps-list { grid-template-columns: 1fr; }
            .step { border-right: none; }
        }
    </style>
</head>
<body>

<!-- Nav -->
<nav>
    <a href="/" class="nav-logo">BookIn<span>Stack</span></a>
    <div class="nav-links">
        <a href="#features" class="nav-link">Features</a>
        <a href="#pricing" class="nav-link">Pricing</a>
        <a href="{{ route('login') }}" class="nav-link">Sign in</a>
        <a href="{{ route('register') }}" class="nav-link nav-cta">Get started</a>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-tag">For Nigerian Businesses</div>

        <h1>
            Your business,<br>
            fully booked &amp;<br>
            <em>getting paid</em>
        </h1>

        <p class="hero-sub">
            BookInStack gives your business a professional booking and payment
            system. No tech skills needed. Set up in minutes, start earning today.
        </p>

        <div class="hero-actions">
            <a href="{{ route('register') }}" class="btn-primary">Start for free</a>
            <a href="#how-it-works" class="btn-ghost">See how it works</a>
        </div>

        <div class="hero-points">
            <div class="hero-point">
                <div class="hero-point-dot"></div>
                <span>Customers book and pay directly from your website</span>
            </div>
            <div class="hero-point">
                <div class="hero-point-dot"></div>
                <span>Payments hit your bank account — 95% goes straight to you</span>
            </div>
            <div class="hero-point">
                <div class="hero-point-dot"></div>
                <span>QR check-in, staff accounts, and booking management — all included</span>
            </div>
        </div>
    </div>
</section>

<!-- Stats bar -->
{{-- <div class="stats-bar">
    <div class="stat-item">
        <div class="num">3</div>
        <div class="lbl">Lines to integrate</div>
    </div>
    <div class="stat-item">
        <div class="num">~60s</div>
        <div class="lbl">To go live</div>
    </div>
    <div class="stat-item">
        <div class="num">₦0</div>
        <div class="lbl">Monthly fee</div>
    </div>
</div> --}}

<!-- Features -->
<section class="section" id="features">
    <div class="section-eyebrow">Features</div>
    <h2>Everything you need, nothing you don't</h2>
    <p class="section-sub">
        One SDK. One API key. Full booking and payment infrastructure that just works.
    </p>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-label">Payments</div>
            <h4>Paystack-powered checkout</h4>
            <p>Card, bank transfer, USSD — all channels supported. Settlements hit your account directly via subaccounts.</p>
        </div>
        <div class="feature-card">
            <div class="feature-label">Bookings</div>
            <h4>Booking management</h4>
            <p>Create, track, and manage bookings. Mark attendance, search by customer, filter by status.</p>
        </div>
        <div class="feature-card">
            <div class="feature-label">Scheduling</div>
            <h4>Booking windows</h4>
            <p>Restrict when bookings are accepted. Set open days and hours — bookings outside that window are automatically rejected.</p>
        </div>
        <div class="feature-card">
            <div class="feature-label">Integration</div>
            <h4>Simple SDK</h4>
            <p>One script tag, one public key. <code style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:var(--accent);">Booking.create()</code>, done. Or use the drop-in widget.</p>
        </div>
        <div class="feature-card">
            <div class="feature-label">Team</div>
            <h4>Staff accounts</h4>
            <p>Add team members who can view bookings and mark attendance — without touching your API keys or settings.</p>
        </div>
        <div class="feature-card">
            <div class="feature-label">Analytics</div>
            <h4>Revenue dashboard</h4>
            <p>See your earnings, paid bookings, and full payment history at a glance.</p>
        </div>
    </div>
</section>

<!-- How it works -->
<section class="steps" id="how-it-works">
    <div class="section-eyebrow">How it works</div>
    <h2>Up and running in minutes</h2>

    <div class="steps-list">
        <div class="step">
            <div class="step-num">01</div>
            <h4>Create your account</h4>
            <p>Sign up with your email and business name. Takes 30 seconds.</p>
        </div>
        <div class="step">
            <div class="step-num">02</div>
            <h4>Verify your identity</h4>
            <p>Submit your BVN and bank account. We verify via Paystack and create your subaccount instantly.</p>
        </div>
        <div class="step">
            <div class="step-num">03</div>
            <h4>Get your API key</h4>
            <p>Your live public key is issued immediately after verification.</p>
        </div>
        <div class="step">
            <div class="step-num">04</div>
            <h4>Start accepting payments</h4>
            <p>Add one script tag to your site. Every payment goes straight to your bank account.</p>
        </div>
    </div>
</section>

<!-- Pricing -->
<section class="section" id="pricing">
    <div class="section-eyebrow">Pricing</div>
    <h2>Pay only when you earn</h2>
    <p class="section-sub">No monthly fees. No setup costs. A small percentage per transaction — that's it.</p>

    <div class="pricing-wrap">
        <div class="pricing-card">
            <div class="pricing-header">
                <div class="pricing-badge">Free to start</div>
                <div class="pricing-headline">Simple, transparent pricing</div>
                <div class="pricing-desc">Everything included. No tiers, no surprises.</div>
            </div>

            <ul class="pricing-features">
                <li>Unlimited bookings</li>
                <li>Paystack-powered checkout</li>
                <li>Booking window controls</li>
                <li>Staff accounts</li>
                <li>Revenue dashboard</li>
                <li>Webhook-verified payments</li>
                <li>Drop-in widget included</li>
            </ul>

            <a href="{{ route('register') }}" class="btn-pricing">Get started free</a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="logo">BookIn<span>Stack</span></div>
    <p>© {{ date('Y') }} BookInStack. Built for Nigerian Businesses.</p>
</footer>

</body>
</html>
