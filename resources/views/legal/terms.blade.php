<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Terms of Service — BookInStack</title>
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
    .warning{background:#fef2f2;border-left:3px solid #ef4444;padding:14px 18px;border-radius:0 8px 8px 0;margin:20px 0;font-size:14px;color:#991b1b;}
    footer{margin-top:60px;padding-top:24px;border-top:1px solid #e2e8f0;font-size:13px;color:#94a3b8;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;}
    footer a{color:#4f46e5;text-decoration:none;}
    @media(max-width:600px){.wrap{padding:32px 16px;}h1{font-size:24px;}}
  </style>
</head>
<body>
<div class="wrap">
    <a href="{{ url('/') }}" class="logo">BookIn<span>Stack</span></a>

    <h1>Terms of Service</h1>
    <p class="meta">Last updated: {{ now()->format('d F Y') }} · By creating an account, you agree to these terms.</p>

    <div class="highlight">
        <strong>Plain English summary:</strong> BookInStack is a software tool. We are not a bank. We don't hold your money — Paystack does. You are responsible for your bookings and your customers. We take a small fee only when a payment succeeds.
    </div>

    <h2>1. About BookInStack</h2>
    <p>BookInStack is a booking and payment software platform operated by <strong>{{ config('app.company_name', '[Your Company] Limited') }}</strong>, a company registered in Nigeria. We provide software tools that allow businesses ("Developers") to accept bookings and payments online.</p>

    <div class="warning">
        BookInStack is a technology platform, not a financial institution, bank, money transfer operator, or payment processor. We do not hold, transmit, or manage funds. All payment processing is performed by Paystack, which is licensed by the Central Bank of Nigeria (CBN).
    </div>

    <h2>2. Eligibility</h2>
    <p>To create a BookInStack account you must:</p>
    <ul>
        <li>Be at least 18 years old</li>
        <li>Be a Nigerian resident or operate a business registered in Nigeria</li>
        <li>Have a valid Nigerian bank account</li>
        <li>Have a valid BVN (Bank Verification Number)</li>
        <li>Not be prohibited from using financial services under Nigerian law</li>
        <li>Accept these Terms and our Privacy Policy</li>
    </ul>

    <h2>3. Account Registration and Verification</h2>
    <p>To activate your account and receive a live API key, you must complete identity verification by providing your BVN and bank account details. This process is handled through Paystack's verification service.</p>
    <p>You agree that:</p>
    <ul>
        <li>All information provided during registration is accurate and truthful</li>
        <li>You will keep your account credentials secure and not share them</li>
        <li>You are responsible for all activity that occurs under your account</li>
        <li>You will notify us immediately of any unauthorised access</li>
    </ul>
    <p>We reserve the right to suspend or terminate accounts where we suspect fraud, false information, or violation of these terms.</p>

    <h2>4. The Platform Fee</h2>
    <p>BookInStack charges a platform fee on successful transactions processed through the platform. The default fee is <strong>5% per transaction</strong>, though this may be adjusted by agreement. This fee is deducted automatically at the point of payment settlement via Paystack's subaccount split mechanism.</p>
    <p>You receive your share (default 95%) directly into your registered bank account via Paystack's settlement system. BookInStack does not hold your funds at any point.</p>
    <p>There are no monthly fees, setup fees, or subscription charges. You only pay when you earn.</p>

    <h2>5. Your Responsibilities as a Developer</h2>
    <p>By using BookInStack to accept bookings and payments, you agree that:</p>
    <ul>
        <li>You are solely responsible for the goods, services, or events you offer</li>
        <li>You will honour all bookings made and paid through the platform</li>
        <li>You will issue refunds where required under Nigerian consumer protection law</li>
        <li>You will not use the platform for illegal, fraudulent, or deceptive purposes</li>
        <li>You will not sell prohibited goods or services (weapons, illegal substances, etc.)</li>
        <li>You will maintain your own compliance with all applicable Nigerian laws including NDPR, FCCPC regulations, and CBN guidelines relevant to your business</li>
        <li>You are responsible for collecting and remitting applicable taxes (VAT, withholding tax) on your sales</li>
        <li>You will not use the platform to process payments for businesses other than your own without our prior written consent</li>
    </ul>

    <h2>6. Prohibited Uses</h2>
    <p>You may not use BookInStack to sell or facilitate:</p>
    <ul>
        <li>Illegal goods or services of any kind</li>
        <li>Counterfeit tickets or fraudulent bookings</li>
        <li>Ponzi schemes, investment fraud, or money laundering</li>
        <li>Anything that violates Paystack's Acceptable Use Policy</li>
        <li>Services targeting minors in an inappropriate manner</li>
    </ul>
    <p>Violation of this section will result in immediate account termination and may be reported to relevant authorities.</p>

    <h2>7. Payments and Settlement</h2>
    <p>Payment processing is provided by Paystack. Settlement timelines, transaction limits, and payment holds are governed by Paystack's terms, not ours. We have no control over payment holds, reversals, or chargebacks initiated by Paystack or card networks.</p>
    <p>In the event of a disputed transaction or chargeback, you acknowledge that Paystack may reverse settled funds from your subaccount. BookInStack is not liable for such reversals.</p>

    <h2>8. API Keys and Integration</h2>
    <p>Upon verification, you receive a public API key for use in client-side integrations. You are responsible for keeping your secret key confidential. BookInStack is not liable for any losses arising from unauthorised use of your API keys.</p>
    <p>You may not reverse-engineer, decompile, or attempt to extract source code from the BookInStack SDK or API.</p>

    <h2>9. Availability and Uptime</h2>
    <p>We aim to maintain high availability but do not guarantee uninterrupted service. We may suspend the service for maintenance, security updates, or circumstances beyond our control. We will give reasonable notice of planned downtime where possible.</p>

    <h2>10. Intellectual Property</h2>
    <p>The BookInStack platform, SDK, dashboard, and all associated software remain the intellectual property of {{ config('app.company_name', '[Your Company] Limited') }}. You are granted a limited, non-exclusive licence to use the platform for its intended purpose. Nothing in these terms transfers ownership of our IP to you.</p>

    <h2>11. Limitation of Liability</h2>
    <p>To the maximum extent permitted by Nigerian law, BookInStack and its operators shall not be liable for:</p>
    <ul>
        <li>Loss of revenue or profits arising from platform downtime</li>
        <li>Payment disputes or chargebacks between you and your customers</li>
        <li>Losses arising from your use or misuse of the platform</li>
        <li>Actions taken by Paystack including payment holds or account suspension</li>
        <li>Any indirect, consequential, or punitive damages</li>
    </ul>
    <p>Our total liability to you for any claim shall not exceed the fees you paid to us in the 3 months preceding the claim.</p>

    <h2>12. Indemnity</h2>
    <p>You agree to indemnify and hold harmless BookInStack, its directors, employees, and agents from any claims, losses, or damages (including legal fees) arising from your use of the platform, your violation of these terms, or any dispute between you and your customers.</p>

    <h2>13. Termination</h2>
    <p>You may close your account at any time by contacting support. We may suspend or terminate your account immediately if you violate these terms, engage in fraudulent activity, or if required by law.</p>
    <p>Upon termination, your access to the platform ceases. Booking records are retained as required by law. Outstanding settlements due to you will be processed through Paystack's normal settlement cycle.</p>

    <h2>14. Governing Law</h2>
    <p>These Terms are governed by the laws of the Federal Republic of Nigeria. Any disputes arising from these terms shall be subject to the exclusive jurisdiction of Nigerian courts.</p>

    <h2>15. Changes to These Terms</h2>
    <p>We may update these terms from time to time. We will give you at least 14 days notice of material changes by email. Continued use of the platform after the effective date constitutes acceptance of the updated terms.</p>

    <h2>16. Contact</h2>
    <p>
        <strong>{{ config('app.company_name', '[Your Company] Limited') }}</strong><br/>
        Email: legal@bookinstack.com<br/>
        Nigeria
    </p>

    <footer>
        <span>© {{ now()->year }} {{ config('app.company_name', '[Your Company] Limited') }}. All rights reserved.</span>
        <div style="display:flex;gap:16px;">
            <a href="{{ route('privacy') }}">Privacy Policy</a>
            <a href="{{ url('/') }}">Home</a>
        </div>
    </footer>
</div>
</body>
</html>