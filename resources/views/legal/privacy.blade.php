<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Privacy Policy — BookInStack</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'DM Sans',sans-serif;background:#f8fafc;color:#0d0d14;line-height:1.7;}
    .wrap{max-width:760px;margin:0 auto;padding:60px 24px;}
    .logo{font-size:20px;font-weight:800;color:#0d0d14;text-decoration:none;display:inline-block;margin-bottom:48px;}
    .logo span{color:#4f46e5;}
    h1{font-size:32px;font-weight:800;margin-bottom:8px;letter-spacing:-.5px;}
    .meta{font-size:14px;color:#64748b;margin-bottom:40px;}
    h2{font-size:18px;font-weight:700;margin:36px 0 10px;color:#0d0d14;}
    p{font-size:15px;color:#374151;margin-bottom:14px;}
    ul{padding-left:20px;margin-bottom:14px;}
    ul li{font-size:15px;color:#374151;margin-bottom:6px;}
    .highlight{background:#eef2ff;border-left:3px solid #4f46e5;padding:14px 18px;border-radius:0 8px 8px 0;margin:20px 0;font-size:14px;color:#3730a3;}
    footer{margin-top:60px;padding-top:24px;border-top:1px solid #e2e8f0;font-size:13px;color:#94a3b8;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;}
    footer a{color:#4f46e5;text-decoration:none;}
    @media(max-width:600px){.wrap{padding:32px 16px;}h1{font-size:24px;}}
  </style>
</head>
<body>
<div class="wrap">
    <a href="{{ url('/') }}" class="logo">BookIn<span>Stack</span></a>

    <h1>Privacy Policy</h1>
    <p class="meta">Last updated: {{ now()->format('d F Y') }} · Effective immediately</p>

    <div class="highlight">
        BookInStack is a software platform operated by <strong>{{ config('app.company_name', '[Your Company] Limited') }}</strong>. We are not a bank or financial institution. Payments are processed by Paystack, a CBN-licensed payment processor.
    </div>

    <h2>1. Who We Are</h2>
    <p>BookInStack ("we", "our", "the platform") is a booking and payment software platform that enables businesses to accept online bookings and payments. We are registered in Nigeria and operate under Nigerian law.</p>
    <p>When you use BookInStack — whether as a business owner (Developer) or as a customer making a booking — you trust us with your information. This policy explains what we collect, why we collect it, and how we protect it.</p>

    <h2>2. Information We Collect</h2>

    <p><strong>From businesses signing up (Developers):</strong></p>
    <ul>
        <li>Name, email address, business name</li>
        <li>Bank account number and bank name (for settlement setup)</li>
        <li>BVN — used solely for identity verification via Paystack. <strong>We do not store your raw BVN after verification is complete.</strong> Only the verification status is retained.</li>
        <li>Paystack subaccount code (generated after verification)</li>
    </ul>

    <p><strong>From customers making bookings:</strong></p>
    <ul>
        <li>Name, email address, phone number</li>
        <li>Booking details (dates, ticket type, service selected)</li>
        <li>Payment reference (from Paystack) — we do not store card numbers or bank PINs</li>
    </ul>

    <p><strong>Automatically collected:</strong></p>
    <ul>
        <li>IP address, browser type, device type</li>
        <li>Pages visited and actions taken on the platform</li>
        <li>Cookies for session management and security</li>
    </ul>

    <h2>3. How We Use Your Information</h2>
    <ul>
        <li>To create and manage your account</li>
        <li>To verify your identity and set up your payment subaccount</li>
        <li>To process bookings and send confirmation emails</li>
        <li>To send transaction receipts and QR tickets</li>
        <li>To provide customer support</li>
        <li>To detect and prevent fraud</li>
        <li>To comply with legal obligations</li>
    </ul>
    <p>We do <strong>not</strong> sell your personal data to third parties. We do not use your data for advertising.</p>

    <h2>4. BVN Data — Special Notice</h2>
    <div class="highlight">
        Your Bank Verification Number (BVN) is classified as sensitive personal data under the Nigeria Data Protection Regulation (NDPR). We collect your BVN only to verify your identity via Paystack's verification service. Once verification is complete, your raw BVN is permanently deleted from our systems. We retain only a boolean flag confirming verification was successful.
    </div>

    <h2>5. Payment Data</h2>
    <p>All payment processing is handled by <strong>Paystack</strong> (licensed by the Central Bank of Nigeria). BookInStack does not store, process, or transmit card details or bank account credentials. Your payment data is subject to Paystack's own Privacy Policy at <a href="https://paystack.com/privacy" target="_blank">paystack.com/privacy</a>.</p>

    <h2>6. Data Sharing</h2>
    <p>We share your data only with:</p>
    <ul>
        <li><strong>Paystack</strong> — for payment processing and identity verification</li>
        <li><strong>Resend</strong> — for sending transactional emails (confirmation, receipts)</li>
        <li><strong>Laravel Cloud / Supabase</strong> — for hosting and database infrastructure</li>
        <li><strong>Law enforcement</strong> — when required by Nigerian law or court order</li>
    </ul>
    <p>All third-party services we use are bound by data processing agreements and comply with applicable data protection laws.</p>

    <h2>7. Data Retention</h2>
    <ul>
        <li>BVN: Deleted immediately after successful verification</li>
        <li>Booking records: Retained for 7 years (Nigerian financial record-keeping requirement)</li>
        <li>Account data: Retained while your account is active, deleted within 30 days of account closure on request</li>
        <li>Payment references: Retained for 7 years for reconciliation purposes</li>
    </ul>

    <h2>8. Your Rights (NDPR)</h2>
    <p>Under the Nigeria Data Protection Regulation, you have the right to:</p>
    <ul>
        <li>Access the personal data we hold about you</li>
        <li>Request correction of inaccurate data</li>
        <li>Request deletion of your data (subject to legal retention requirements)</li>
        <li>Withdraw consent where processing is based on consent</li>
        <li>Lodge a complaint with the National Information Technology Development Agency (NITDA)</li>
    </ul>
    <p>To exercise any of these rights, email us at <strong>privacy@bookinstack.com</strong>.</p>

    <h2>9. Security</h2>
    <p>We implement industry-standard security measures including:</p>
    <ul>
        <li>HTTPS encryption for all data in transit</li>
        <li>Encrypted database storage</li>
        <li>Access controls — only authorised personnel can access production data</li>
        <li>Regular security reviews</li>
    </ul>
    <p>No system is 100% secure. If you discover a security vulnerability, please report it responsibly to security@bookinstack.com.</p>

    <h2>10. Cookies</h2>
    <p>We use essential cookies only — for session management, CSRF protection, and keeping you logged in. We do not use advertising or tracking cookies.</p>

    <h2>11. Children</h2>
    <p>BookInStack is not directed at children under 18. We do not knowingly collect data from minors. If you believe a minor has created an account, contact us and we will delete it promptly.</p>

    <h2>12. Changes to This Policy</h2>
    <p>We may update this policy from time to time. Material changes will be communicated by email to registered users at least 14 days before they take effect. Continued use of the platform after that date constitutes acceptance of the updated policy.</p>

    <h2>13. Contact Us</h2>
    <p>
        <strong>{{ config('app.company_name', '[Your Company] Limited') }}</strong><br/>
        Email: privacy@bookinstack.com<br/>
        Nigeria
    </p>

    <footer>
        <span>© {{ now()->year }} {{ config('app.company_name', '[Your Company] Limited') }}. All rights reserved.</span>
        <div style="display:flex;gap:16px;">
            <a href="{{ route('terms') }}">Terms of Service</a>
            <a href="{{ url('/') }}">Home</a>
        </div>
    </footer>
</div>
</body>
</html>