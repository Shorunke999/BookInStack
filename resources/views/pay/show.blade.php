<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Complete Payment — {{ $link->developer->business_name ?? $link->developer->name }}</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    :root { --accent:#4f46e5; --ink:#0d0d14; --muted:#64748b; --border:#e2e8f0; --soft:#f8fafc; --green:#10b981; }
    body { font-family:'DM Sans',sans-serif; background:var(--soft); color:var(--ink); min-height:100vh; display:flex; flex-direction:column; align-items:center; padding:40px 20px; }
    .logo-bar { margin-bottom:28px; text-align:center; }
    .business-name { font-size:20px; font-weight:700; }
    .powered { font-size:11px; color:var(--muted); margin-top:4px; }
    .powered strong { color:var(--accent); }
    .card { background:#fff; border:1px solid var(--border); border-radius:14px; padding:28px; width:100%; max-width:440px; box-shadow:0 4px 24px rgba(0,0,0,.06); }
    .amount-display { text-align:center; padding:20px 0 18px; border-bottom:1px solid var(--border); margin-bottom:20px; }
    .amount-label { font-size:11px; color:var(--muted); letter-spacing:.1em; text-transform:uppercase; margin-bottom:8px; }
    .amount-value { font-size:40px; font-weight:800; color:var(--ink); letter-spacing:-.04em; line-height:1; }
    .amount-desc  { font-size:13px; color:var(--muted); margin-top:8px; }
    .detail-row { display:flex; justify-content:space-between; padding:9px 0; border-bottom:1px solid var(--border); font-size:13px; }
    .detail-row:last-of-type { border-bottom:none; }
    .detail-label { color:var(--muted); }
    .detail-value { font-weight:600; text-align:right; }
    .section-title { font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin:20px 0 12px; padding-top:16px; border-top:1px solid var(--border); }
    .form-group { margin-bottom:14px; }
    .form-group label { display:block; font-size:12px; font-weight:600; color:var(--muted); margin-bottom:5px; }
    .form-group input { width:100%; padding:9px 12px; border:1px solid var(--border); border-radius:8px; font-size:14px; font-family:inherit; color:var(--ink); background:#fff; }
    .form-group input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(79,70,229,.08); }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .qty-row { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border); font-size:14px; }
    .qty-row:last-of-type { border-bottom:none; }
    .qty-ctrl { display:flex; align-items:center; gap:14px; }
    .qty-btn { width:32px; height:32px; border-radius:8px; border:1px solid var(--border); background:var(--soft); font-size:18px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-weight:700; transition:all .15s; }
    .qty-btn:hover:not(:disabled) { border-color:var(--accent); color:var(--accent); }
    .qty-btn:disabled { opacity:.4; cursor:not-allowed; }
    .qty-num { font-size:16px; font-weight:700; min-width:20px; text-align:center; }
    .nights-badge { text-align:center; font-size:13px; font-weight:600; color:var(--accent); margin:10px 0 4px; padding:8px; background:rgba(79,70,229,.06); border-radius:8px; }
    .pay-btn { width:100%; padding:15px; margin-top:20px; background:var(--accent); color:#fff; border:none; border-radius:9px; font-size:15px; font-weight:700; font-family:inherit; cursor:pointer; transition:all .2s; }
    .pay-btn:hover:not(:disabled) { background:#4338ca; transform:translateY(-1px); box-shadow:0 6px 20px rgba(79,70,229,.3); }
    .pay-btn:disabled { opacity:.6; cursor:not-allowed; transform:none; }
    .secure-note { text-align:center; font-size:12px; color:var(--muted); margin-top:12px; display:flex; align-items:center; justify-content:center; gap:5px; }
    .error-msg { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; border-radius:8px; padding:10px 14px; font-size:13px; margin-top:12px; display:none; }
    .state-card { text-align:center; padding:40px 28px; width:100%; max-width:440px; background:#fff; border:1px solid var(--border); border-radius:14px; box-shadow:0 4px 24px rgba(0,0,0,.06); }
    .state-icon { font-size:52px; margin-bottom:16px; }
    .state-title { font-size:22px; font-weight:700; margin-bottom:8px; }
    .state-sub { font-size:14px; color:var(--muted); line-height:1.6; }
  </style>
</head>
<body>

@php
  $mode     = $link->category?->booking_mode ?? $link->developer->booking_mode ?? 'appointment';
  $paid     = request('paid') == '1';
  $today    = now()->toDateString();
  $tomorrow = now()->addDay()->toDateString();
  $unit     = $link->developer->reservation_unit ?? 'night';

  // ── Booking window check ───────────────────────────────────────────────────
  $windowClosed  = false;
  $windowMessage = null;

  if ($link->developer->enable_booking_window && $link->developer->booking_window) {
      $window    = $link->developer->booking_window;
      $now       = now();
      $dayName   = strtolower($now->format('l'));
      $time      = $now->format('H:i');
      $openDays  = $window['days']       ?? [];
      $openFrom  = $window['open_time']  ?? '00:00';
      $openUntil = $window['close_time'] ?? '23:59';

      if (!in_array($dayName, $openDays)) {
          $windowClosed  = true;
          $windowMessage = 'Bookings are not accepted on ' . ucfirst($dayName) . 's.';
      } elseif ($time < $openFrom || $time > $openUntil) {
          $windowClosed  = true;
          $windowMessage = "Bookings are accepted between {$openFrom} and {$openUntil}.";
      }
  }
@endphp

<div class="logo-bar">
  <div class="business-name">{{ $link->developer->business_name ?? $link->developer->name }}</div>
  <div class="powered">Powered by <strong>BookInStack</strong></div>
</div>

@if($link->status === 'paid' || $paid)
  <div class="state-card">
    <div class="state-icon">✅</div>
    <div class="state-title" style="color:var(--green);">Payment Confirmed!</div>
    <div class="state-sub">
      Your payment has been received.<br/>
      A confirmation will be sent to <strong>{{ $link->customer_email }}</strong>.
    </div>
  </div>

@elseif($link->status === 'expired')
  <div class="state-card">
    <div class="state-icon">⏰</div>
    <div class="state-title">Link Expired</div>
    <div class="state-sub">This payment link has expired. Please contact {{ $link->developer->business_name ?? $link->developer->name }} for a new one.</div>
  </div>

@elseif($link->status === 'cancelled')
  <div class="state-card">
    <div class="state-icon">❌</div>
    <div class="state-title">Link Cancelled</div>
    <div class="state-sub">This payment link has been cancelled.</div>
  </div>

@elseif($windowClosed)
  <div class="state-card">
    <div class="state-icon">🔒</div>
    <div class="state-title">Bookings Closed</div>
    <div class="state-sub">
      {{ $windowMessage }}<br/><br/>
      Please try again during business hours or contact<br/>
      <strong>{{ $link->developer->business_name ?? $link->developer->name }}</strong>.
    </div>
  </div>

@else
  <div class="card">

    <div class="amount-display">
      <div class="amount-label">Amount Due</div>
      <div class="amount-value" id="amount-display">{{ $link->formattedAmount() }}</div>
      <div class="amount-desc">{{ $link->description }}</div>
    </div>

    <div>
      <div class="detail-row">
        <span class="detail-label">Customer</span>
        <span class="detail-value">{{ $link->customer_name }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Email</span>
        <span class="detail-value" style="font-family:'DM Mono',monospace;font-size:11px;">{{ $link->customer_email }}</span>
      </div>
      @if($link->category)
      <div class="detail-row">
        <span class="detail-label">{{ match($mode) { 'ticket'=>'Ticket Type','reservation'=>'Room / Space',default=>'Service' } }}</span>
        <span class="detail-value">{{ $link->category->name }}</span>
      </div>
      @endif
      @if($link->expires_at)
      <div class="detail-row">
        <span class="detail-label">Pay before</span>
        <span class="detail-value">{{ $link->expires_at->format('d M Y, g:i A') }}</span>
      </div>
      @endif
    </div>

    {{-- RESERVATION --}}
    @if($mode === 'reservation')
      <div class="section-title">{{ $unit === 'day' ? 'Select Dates' : 'Check-in & Check-out' }}</div>
      <div class="form-row">
        <div class="form-group" style="margin-bottom:0;">
          <label>{{ $unit === 'day' ? 'Start Date' : 'Check-in' }}</label>
          <input type="date" id="f-checkin" min="{{ $today }}" value="{{ $today }}" />
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>{{ $unit === 'day' ? 'End Date' : 'Check-out' }}</label>
          <input type="date" id="f-checkout" min="{{ $tomorrow }}" value="{{ $tomorrow }}" />
        </div>
      </div>
      <div class="nights-badge" id="nights-badge">{{ $unit === 'day' ? '☀️' : '🌙' }} 1 {{ $unit }}</div>
    @endif

    {{-- APPOINTMENT --}}
    @if($mode === 'appointment')
      <div class="section-title">Preferred Schedule</div>
      <div class="form-row">
        <div class="form-group" style="margin-bottom:0;">
          <label>Preferred Date</label>
          <input type="date" id="f-date" min="{{ $today }}" value="{{ $today }}" />
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>Preferred Time</label>
          <input type="time" id="f-time" value="09:00" />
        </div>
      </div>
    @endif

    {{-- TICKET --}}
    @if($mode === 'ticket')
      <div class="section-title">Ticket Quantity</div>
      <div>
        <div class="qty-row">
          <span>Adults</span>
          <div class="qty-ctrl">
            <button class="qty-btn" id="adult-down" type="button" disabled>−</button>
            <span class="qty-num" id="adult-count">1</span>
            <button class="qty-btn" id="adult-up" type="button">+</button>
          </div>
        </div>
        @if($link->category?->enable_child_pricing)
        <div class="qty-row">
          <span>Children</span>
          <div class="qty-ctrl">
            <button class="qty-btn" id="child-down" type="button" disabled>−</button>
            <span class="qty-num" id="child-count">0</span>
            <button class="qty-btn" id="child-up" type="button">+</button>
          </div>
        </div>
        @endif
      </div>
    @endif

    <div id="error-msg" class="error-msg"></div>

    <button class="pay-btn" id="pay-btn" onclick="initPay()">Pay Now</button>

    <div class="secure-note">🔒 Secured by Paystack · Your card details are never stored</div>
  </div>
@endif

<script>
  const MODE        = '{{ $mode }}';
  const UNIT        = '{{ $unit }}';
  const BASE_PRICE  = {{ $link->category?->price ?? $link->amount }};
  const CHILD_PRICE = {{ $link->category?->child_price ?? $link->category?->price ?? $link->amount }};
  const MAX_ORDER   = {{ $link->category?->max_per_order ?? 99 }};

  let adults = 1, children = 0;

  function fmt(kobo) {
    return '₦' + (kobo / 100).toLocaleString('en-NG', { minimumFractionDigits: 2 });
  }

  // ── Reservation ───────────────────────────────────────────
  @if($mode === 'reservation')
  function updateNights() {
    const ci = document.getElementById('f-checkin');
    const co = document.getElementById('f-checkout');
    if (!ci || !co) return;
    if (co.value <= ci.value) {
      const d = new Date(ci.value); d.setDate(d.getDate() + 1);
      co.value = d.toISOString().split('T')[0]; co.min = co.value;
    }
    const n = Math.max(1, Math.round((new Date(co.value) - new Date(ci.value)) / 86400000));
    document.getElementById('nights-badge').textContent =
      (UNIT === 'day' ? '☀️' : '🌙') + ' ' + n + ' ' + UNIT + (n !== 1 ? 's' : '');
    document.getElementById('amount-display').textContent = fmt(BASE_PRICE * n);
  }
  document.getElementById('f-checkin')?.addEventListener('change', updateNights);
  document.getElementById('f-checkout')?.addEventListener('change', updateNights);
  updateNights();
  @endif

  // ── Ticket ────────────────────────────────────────────────
  @if($mode === 'ticket')
  function updateQty() {
    const total = (BASE_PRICE * adults) + (CHILD_PRICE * children);
    document.getElementById('adult-count').textContent = adults;
    const cc = document.getElementById('child-count');
    if (cc) cc.textContent = children;
    document.getElementById('adult-down').disabled = adults <= 1;
    document.getElementById('adult-up').disabled   = (adults + children) >= MAX_ORDER;
    const cd = document.getElementById('child-down');
    const cu = document.getElementById('child-up');
    if (cd) cd.disabled = children <= 0;
    if (cu) cu.disabled = (adults + children) >= MAX_ORDER;
    document.getElementById('amount-display').textContent = fmt(total);
  }
  document.getElementById('adult-up')?.addEventListener('click',   () => { adults++;   updateQty(); });
  document.getElementById('adult-down')?.addEventListener('click', () => { if (adults > 1)   { adults--;   updateQty(); } });
  document.getElementById('child-up')?.addEventListener('click',   () => { children++; updateQty(); });
  document.getElementById('child-down')?.addEventListener('click', () => { if (children > 0) { children--; updateQty(); } });
  updateQty();
  @endif

  // ── Submit ────────────────────────────────────────────────
  async function initPay() {
    const btn = document.getElementById('pay-btn');
    const err = document.getElementById('error-msg');
    btn.disabled = true; btn.textContent = 'Initializing…';
    err.style.display = 'none';

    const body = {};

    if (MODE === 'reservation') {
      const ci = document.getElementById('f-checkin')?.value;
      const co = document.getElementById('f-checkout')?.value;
      if (!ci || !co) {
        err.textContent = 'Please select your dates.';
        err.style.display = 'block';
        btn.disabled = false; btn.textContent = 'Pay Now'; return;
      }
      body.check_in = ci; body.check_out = co;
    }

    if (MODE === 'appointment') {
      body.preferred_date = document.getElementById('f-date')?.value;
      body.preferred_time = document.getElementById('f-time')?.value;
    }

    if (MODE === 'ticket') {
      body.adults = adults; body.children = children;
    }

    try {
      const res  = await fetch('/pay/{{ $link->token }}/initialize', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(body),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Payment failed.');
      window.location.href = data.authorization_url;
    } catch (e) {
      err.textContent = e.message;
      err.style.display = 'block';
      btn.disabled = false; btn.textContent = 'Pay Now';
    }
  }
</script>
</body>
</html>