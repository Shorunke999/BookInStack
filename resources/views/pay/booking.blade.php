{{-- resources/views/pay/booking.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
Complete Payment — {{ $booking->developer->business_name ?? $booking->developer->name }}
</title>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />

<style>

*, *::before, *::after {
    box-sizing:border-box;
    margin:0;
    padding:0;
}

:root{
    --accent:#4f46e5;
    --ink:#0d0d14;
    --muted:#64748b;
    --border:#e2e8f0;
    --soft:#f8fafc;
    --green:#10b981;
}

body{
    font-family:'DM Sans',sans-serif;
    background:var(--soft);
    color:var(--ink);
    min-height:100vh;
    display:flex;
    flex-direction:column;
    align-items:center;
    padding:40px 20px;
}

.logo-bar{
    margin-bottom:28px;
    text-align:center;
}

.business-name{
    font-size:20px;
    font-weight:700;
}

.powered{
    font-size:11px;
    color:var(--muted);
    margin-top:4px;
}

.powered strong{
    color:var(--accent);
}

.card{
    background:#fff;
    border:1px solid var(--border);
    border-radius:14px;
    padding:28px;
    width:100%;
    max-width:440px;
    box-shadow:0 4px 24px rgba(0,0,0,.06);
}

.amount-display{
    text-align:center;
    padding:20px 0 18px;
    border-bottom:1px solid var(--border);
    margin-bottom:20px;
}

.amount-label{
    font-size:11px;
    color:var(--muted);
    letter-spacing:.1em;
    text-transform:uppercase;
    margin-bottom:8px;
}

.amount-value{
    font-size:40px;
    font-weight:800;
    color:var(--ink);
    letter-spacing:-.04em;
    line-height:1;
}

.amount-desc{
    font-size:13px;
    color:var(--muted);
    margin-top:8px;
}

.detail-row{
    display:flex;
    justify-content:space-between;
    padding:10px 0;
    border-bottom:1px solid var(--border);
    font-size:13px;
}

.detail-row:last-of-type{
    border-bottom:none;
}

.detail-label{
    color:var(--muted);
}

.detail-value{
    font-weight:600;
    text-align:right;
}

.reference{
    font-family:'DM Mono', monospace;
    font-size:11px;
}

.pay-btn{
    width:100%;
    padding:15px;
    margin-top:24px;
    background:var(--accent);
    color:#fff;
    border:none;
    border-radius:9px;
    font-size:15px;
    font-weight:700;
    font-family:inherit;
    cursor:pointer;
    transition:all .2s;
}

.pay-btn:hover:not(:disabled){
    background:#4338ca;
    transform:translateY(-1px);
    box-shadow:0 6px 20px rgba(79,70,229,.3);
}

.pay-btn:disabled{
    opacity:.6;
    cursor:not-allowed;
    transform:none;
}

.secure-note{
    text-align:center;
    font-size:12px;
    color:var(--muted);
    margin-top:12px;
}

.error-msg{
    background:#fef2f2;
    border:1px solid #fecaca;
    color:#991b1b;
    border-radius:8px;
    padding:10px 14px;
    font-size:13px;
    margin-top:14px;
    display:none;
}

.state-card{
    text-align:center;
    padding:40px 28px;
    width:100%;
    max-width:440px;
    background:#fff;
    border:1px solid var(--border);
    border-radius:14px;
    box-shadow:0 4px 24px rgba(0,0,0,.06);
}

.state-icon{
    font-size:52px;
    margin-bottom:16px;
}

.state-title{
    font-size:22px;
    font-weight:700;
    margin-bottom:8px;
}
 .alert {
            padding: 11px 14px;
            border-radius: 7px;
            font-size: 13.5px;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }


.state-sub{
    font-size:14px;
    color:var(--muted);
    line-height:1.6;
}

</style>
</head>

<body>

<div class="logo-bar">
    <div class="business-name">
        {{ $booking->developer->business_name ?? $booking->developer->name }}
    </div>

    <div class="powered">
        Powered by <strong>BookInStack</strong>
    </div>
</div>

@if($booking->status === 'paid')

<div class="state-card">

    <div class="state-icon">✅</div>

    <div class="state-title" style="color:var(--green);">
        Payment Confirmed
    </div>

    <div class="state-sub">
        Your payment has been received successfully.
        <br><br>
        Booking Reference:
        <strong>{{ $booking->reference }}</strong>
    </div>

</div>

@else

<div class="card">
    {{-- Flash messages --}}
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
    <div class="amount-display">

        <div class="amount-label">
            Amount Due
        </div>

        <div class="amount-value">
            ₦{{ number_format($booking->amount, 2) }}
        </div>

        <div class="amount-desc">
            {{ $booking->description }}
        </div>

    </div>

    <div>

        <div class="detail-row">
            <span class="detail-label">Customer</span>
            <span class="detail-value">
                {{ $booking->customer_name }}
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Email</span>
            <span class="detail-value reference">
                {{ $booking->customer_email }}
            </span>
        </div>

        @if($booking->service)
        <div class="detail-row">
            <span class="detail-label">Service</span>
            <span class="detail-value">
                {{ $booking->service->name }}
            </span>
        </div>
        @endif

        @if($booking->category)
        <div class="detail-row">
            <span class="detail-label">Category</span>
            <span class="detail-value">
                {{ $booking->category->name }}
            </span>
        </div>
        @endif

        <div class="detail-row">
            <span class="detail-label">Reference</span>
            <span class="detail-value reference">
                {{ $booking->reference }}
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Status</span>
            <span class="detail-value">
                {{ ucfirst($booking->status) }}
            </span>
        </div>

    </div>

    <div id="error-msg" class="error-msg"></div>

    <form method="POST" action="{{ route('payment.link.continue', $booking->payment_link_token) }}">
        @csrf
        <button
            class="pay-btn"
            type="submit"
        >
            Continue Payment
        </button>
    </form>

    <div class="secure-note">
        🔒 Secured by Paystack · Your card details are never stored
    </div>

</div>

@endif
</body>
</html>
