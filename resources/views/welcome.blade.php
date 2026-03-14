<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BookStackIn — Bookings & Payments for Developers</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --ink:    #0d0d14;
            --muted:  #64748b;
            --border: #e2e8f0;
            --accent: #4f46e5;
            --violet: #7c3aed;
            --green:  #10b981;
            --soft:   #f8fafc;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            color: var(--ink);
            background: #fff;
            overflow-x: hidden;
        }

        /* ── Nav ────────────────────────────────────────────── */
        nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5vw;
            z-index: 100;
            background: rgba(255,255,255,.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,.06);
        }

        .nav-logo {
            font-size: 20px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -.4px;
            text-decoration: none;
        }

        .nav-logo span { color: var(--accent); }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link {
            font-size: 14px;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            transition: all .15s;
        }

        .nav-link:hover { color: var(--ink); background: var(--soft); }

        .nav-cta {
            background: var(--accent);
            color: #fff !important;
            font-weight: 600;
        }

        .nav-cta:hover { background: #4338ca !important; }

        /* ── Hero ───────────────────────────────────────────── */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 5vw 60px;
            position: relative;
            overflow: hidden;
            background: var(--ink);
        }

        /* Animated gradient orbs */
        .hero::before, .hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .35;
            animation: drift 8s ease-in-out infinite alternate;
        }

        .hero::before {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #4f46e5, transparent);
            top: -100px; right: -100px;
        }

        .hero::after {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #7c3aed, transparent);
            bottom: -80px; left: -80px;
            animation-delay: -4s;
        }

        @keyframes drift {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(30px, 20px) scale(1.1); }
        }

        .hero-inner {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 700px;
        }

        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(99,102,241,.15);
            border: 1px solid rgba(99,102,241,.3);
            color: #a5b4fc;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 20px;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 28px;
        }

        .hero h1 {
            font-size: clamp(36px, 6vw, 64px);
            font-weight: 800;
            color: #fff;
            line-height: 1.1;
            letter-spacing: -.03em;
            margin-bottom: 20px;
        }

        .hero h1 .grad {
            background: linear-gradient(135deg, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-sub {
            font-size: clamp(15px, 2vw, 18px);
            color: #94a3b8;
            line-height: 1.6;
            max-width: 520px;
            margin: 0 auto 36px;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-hero-primary {
            background: var(--accent);
            color: #fff;
            padding: 13px 28px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all .2s;
            box-shadow: 0 4px 20px rgba(79,70,229,.4);
        }

        .btn-hero-primary:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 6px 28px rgba(79,70,229,.5);
        }

        .btn-hero-ghost {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            color: #fff;
            padding: 13px 28px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            transition: all .2s;
        }

        .btn-hero-ghost:hover { background: rgba(255,255,255,.1); }

        /* ── Code preview ───────────────────────────────────── */
        .hero-code {
            margin-top: 56px;
            background: #1e1e2e;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 14px;
            padding: 22px 24px;
            text-align: left;
            max-width: 540px;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
        }

        .code-dots {
            display: flex;
            gap: 6px;
            margin-bottom: 16px;
        }

        .code-dots span {
            width: 10px; height: 10px;
            border-radius: 50%;
        }

        .code-dots .r { background: #ff5f56; }
        .code-dots .y { background: #ffbd2e; }
        .code-dots .g { background: #27c93f; }

        .hero-code pre {
            font-family: 'DM Mono', monospace;
            font-size: 13px;
            line-height: 1.75;
            color: #cdd6f4;
        }

        .kw  { color: #cba6f7; }
        .str { color: #a6e3a1; }
        .fn  { color: #89dceb; }
        .cm  { color: #585b70; }
        .nm  { color: #fab387; }

        /* ── Stats bar ───────────────────────────────────────── */
        .stats-bar {
            background: var(--soft);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            padding: 28px 5vw;
            display: flex;
            justify-content: center;
            gap: clamp(32px, 6vw, 80px);
            flex-wrap: wrap;
        }

        .stat-item { text-align: center; }

        .stat-item .num {
            font-size: 28px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -.04em;
            line-height: 1;
        }

        .stat-item .lbl {
            font-size: 13px;
            color: var(--muted);
            margin-top: 4px;
        }

        /* ── Features ────────────────────────────────────────── */
        .section {
            padding: clamp(60px, 8vw, 100px) 5vw;
        }

        .section-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--accent);
            margin-bottom: 12px;
        }

        .section h2 {
            font-size: clamp(26px, 4vw, 40px);
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -.03em;
            max-width: 560px;
            line-height: 1.15;
            margin-bottom: 16px;
        }

        .section-sub {
            font-size: 16px;
            color: var(--muted);
            max-width: 480px;
            line-height: 1.6;
            margin-bottom: 48px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .feature-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            transition: all .2s;
        }

        .feature-card:hover {
            border-color: #c7d2fe;
            box-shadow: 0 4px 24px rgba(79,70,229,.08);
            transform: translateY(-2px);
        }

        .feature-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 14px;
        }

        .feature-card h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 6px;
        }

        .feature-card p {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.6;
        }

        /* ── How it works ─────────────────────────────────────── */
        .steps {
            background: var(--ink);
            padding: clamp(60px, 8vw, 100px) 5vw;
        }

        .steps h2 {
            font-size: clamp(26px, 4vw, 40px);
            font-weight: 800;
            color: #fff;
            letter-spacing: -.03em;
            text-align: center;
            margin-bottom: 48px;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
            max-width: 900px;
            margin: 0 auto;
        }

        .step {
            text-align: center;
            padding: 28px 20px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 14px;
        }

        .step-num {
            width: 40px; height: 40px;
            background: var(--accent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 16px;
            color: #fff;
            margin: 0 auto 16px;
        }

        .step h4 { font-size: 15px; font-weight: 700; color: #fff; margin-bottom: 8px; }
        .step p  { font-size: 13px; color: #64748b; line-height: 1.6; }

        /* ── Pricing ─────────────────────────────────────────── */
        .pricing-card {
            background: #fff;
            border: 2px solid var(--accent);
            border-radius: 16px;
            padding: 36px;
            max-width: 420px;
            margin: 0 auto;
            text-align: center;
            box-shadow: 0 8px 40px rgba(79,70,229,.12);
        }

        .pricing-badge {
            display: inline-block;
            background: var(--accent);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .pricing-price {
            font-size: 52px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -.05em;
            line-height: 1;
        }

        .pricing-price sup { font-size: 24px; font-weight: 600; vertical-align: top; margin-top: 8px; }
        .pricing-price .per { font-size: 16px; font-weight: 400; color: var(--muted); }

        .pricing-desc {
            font-size: 14px;
            color: var(--muted);
            margin: 12px 0 28px;
        }

        .pricing-features {
            list-style: none;
            text-align: left;
            margin-bottom: 28px;
        }

        .pricing-features li {
            font-size: 14px;
            color: var(--ink);
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pricing-features li:last-child { border-bottom: none; }

        .pricing-features li::before {
            content: '✓';
            color: var(--green);
            font-weight: 700;
            font-size: 13px;
        }

        .btn-pricing {
            display: block;
            background: var(--accent);
            color: #fff;
            padding: 13px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all .2s;
        }

        .btn-pricing:hover { background: #4338ca; }

        /* ── CTA ─────────────────────────────────────────────── */
        .cta {
            background: linear-gradient(135deg, var(--accent), var(--violet));
            padding: clamp(60px, 8vw, 100px) 5vw;
            text-align: center;
        }

        .cta h2 {
            font-size: clamp(26px, 4vw, 40px);
            font-weight: 800;
            color: #fff;
            letter-spacing: -.03em;
            margin-bottom: 12px;
        }

        .cta p {
            font-size: 16px;
            color: rgba(255,255,255,.75);
            margin-bottom: 32px;
        }

        /* ── Footer ──────────────────────────────────────────── */
        footer {
            background: var(--ink);
            padding: 28px 5vw;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        footer .logo {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
        }

        footer .logo span { color: #818cf8; }

        footer p {
            font-size: 13px;
            color: #475569;
        }

        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 640px) {
            .nav-link:not(.nav-cta) { display: none; }
            .stats-bar { gap: 24px; }
        }
    </style>
</head>
<body>

<!-- Nav -->
<nav>
    <a href="/" class="nav-logo">BookStack<span>In</span></a>
    <div class="nav-links">
        <a href="#features" class="nav-link">Features</a>
        <a href="#pricing" class="nav-link">Pricing</a>
        <a href="{{ route('login') }}" class="nav-link">Sign in</a>
        <a href="{{ route('register') }}" class="nav-link nav-cta">Get Started →</a>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-pill">⚡ For Nigerian Developers</div>

        <h1>
            Accept bookings &<br />
            payments with<br />
            <span class="grad">three lines of code</span>
        </h1>

        <p class="hero-sub">
            BookStackIn handles your entire booking and payment infrastructure.
            Verify once, integrate anywhere, get paid instantly.
        </p>

        <div class="hero-actions">
            <a href="{{ route('register') }}" class="btn-hero-primary">Start for free →</a>
            <a href="#how-it-works" class="btn-hero-ghost">See how it works</a>
        </div>

        <div class="hero-code">
            <div class="code-dots">
                <span class="r"></span><span class="y"></span><span class="g"></span>
            </div>
            <pre><span class="cm">// Your entire booking flow</span>
<span class="fn">Booking</span>.<span class="fn">init</span>({ <span class="fn">publicKey</span>: <span class="str">'pk_live_xxx'</span>, <span class="fn">returnUrl</span>: <span class="str">'/success'</span> });

<span class="kw">const</span> booking = <span class="kw">await</span> <span class="fn">Booking</span>.<span class="fn">create</span>({
  amount:         <span class="nm">500000</span>,   <span class="cm">// ₦5,000</span>
  customer_email: <span class="str">'user@example.com'</span>,
  description:    <span class="str">'Consultation'</span>,
});

<span class="kw">await</span> <span class="fn">Booking</span>.<span class="fn">pay</span>(booking.reference);
<span class="cm">// → Paystack checkout → 95% to you, instantly</span></pre>
        </div>
    </div>
</section>

<!-- Stats bar -->
<div class="stats-bar">
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
</div>

<!-- Features -->
<section class="section" id="features">
    <div class="section-label">Features</div>
    <h2>Everything you need, nothing you don't</h2>
    <p class="section-sub">
        One SDK. One API key. Full booking and payment infrastructure that just works.
    </p>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon" style="background:#eef2ff;">💳</div>
            <h4>Paystack-powered payments</h4>
            <p>Card, bank transfer, USSD — all channels supported. Settlements hit your account directly via subaccounts.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background:#dcfce7;">📋</div>
            <h4>Booking management</h4>
            <p>Create, track, and manage bookings. Mark attendance, search by customer, filter by status.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background:#fef9c3;">⏰</div>
            <h4>Booking windows</h4>
            <p>Restrict when bookings are accepted. Set open days and hours — bookings outside that window are automatically rejected.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background:#fee2e2;">🔑</div>
            <h4>Simple SDK</h4>
            <p>One script tag, one public key. <code>Booking.create()</code>, <code>Booking.pay()</code>, done. Or use the drop-in widget.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background:#f3e8ff;">👥</div>
            <h4>Staff accounts</h4>
            <p>Add team members who can view bookings and mark attendance — without touching your API keys or settings.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background:#ecfdf5;">📊</div>
            <h4>Revenue dashboard</h4>
            <p>See your earnings, paid bookings, and payment history.</p>
        </div>
    </div>
</section>

<!-- How it works -->
<section class="steps" id="how-it-works">
    <h2>Up and running in minutes</h2>
    <div class="steps-grid">
        <div class="step">
            <div class="step-num">1</div>
            <h4>Create your account</h4>
            <p>Sign up with your email and business name. Takes 30 seconds.</p>
        </div>
        <div class="step">
            <div class="step-num">2</div>
            <h4>Verify your identity</h4>
            <p>Submit your BVN and bank account. We verify via Paystack and create your subaccount instantly.</p>
        </div>
        <div class="step">
            <div class="step-num">3</div>
            <h4>Get your API key</h4>
            <p>Your live public key is issued immediately after verification.</p>
        </div>
        <div class="step">
            <div class="step-num">4</div>
            <h4>Start accepting payments</h4>
            <p>Add one script tag to your site. Every payment goes straight to your bank account.</p>
        </div>
    </div>
</section>

<!-- Pricing -->
<section class="section" id="pricing">
    <div style="text-align:center; margin-bottom:40px;">
        <div class="section-label" style="display:inline-block;">Pricing</div>
        <h2 style="max-width:100%; text-align:center;">Pay only when you earn</h2>
        <p class="section-sub" style="max-width:400px; margin:12px auto 0;">
            No monthly fees. No setup costs. We take 5% only when a payment succeeds.
        </p>
    </div>

    <div class="pricing-card">
        <div class="pricing-badge">Simple, transparent</div>
        <div class="pricing-price">
            1<sup>%</sup>
            <span class="per">per transaction</span>
        </div>
        <p class="pricing-desc">You keep ₦99 of every ₦100. We take ₦1. That's it.</p>

        <ul class="pricing-features">
            <li>Unlimited bookings</li>
            <li>Paystack-powered checkout</li>
            <li>Automatic 99/1 split via subaccounts</li>
            <li>Booking window controls</li>
            <li>Staff accounts</li>
            <li>Revenue dashboard</li>
            <li>Webhook-verified payments</li>
            <li>Drop-in widget included</li>
        </ul>

        <a href="{{ route('register') }}" class="btn-pricing">Get started free →</a>
    </div>
</section>

<!-- CTA -->
{{-- <section class="cta">
    <h2>Ready to get paid?</h2>
    <p>Create your account. Verify once. Start accepting bookings today.</p>
    <a href="{{ route('register') }}" class="btn-hero-primary" style="display:inline-block;">
        Create free account →
    </a>
</section> --}}

<!-- Footer -->
<footer>
    <div class="logo">BookStack<span>In</span></div>
    <p>© {{ date('Y') }} BookStackIn. Built for Nigerian developers.</p>
</footer>

</body>
</html>