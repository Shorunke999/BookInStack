<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Booking Confirmed</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      background: #f8fafc;
      color: #0d0d14;
      padding: 32px 16px;
      -webkit-font-smoothing: antialiased;
    }

    .email-wrap {
      max-width: 540px;
      margin: 0 auto;
    }

    /* ── Header ─────────────────────────────────────────── */
    .email-header {
      background: #0d0d14;
      border-radius: 14px 14px 0 0;
      padding: 28px 32px;
      text-align: center;
    }

    .email-logo {
      font-size: 22px;
      font-weight: 800;
      color: #fff;
      letter-spacing: -.3px;
    }

    .email-logo span { color: #818cf8; }

    .email-mode-pill {
      display: inline-block;
      margin-top: 10px;
      background: rgba(255,255,255,.1);
      color: #a5b4fc;
      font-size: 12px;
      font-weight: 600;
      padding: 4px 12px;
      border-radius: 20px;
      letter-spacing: .06em;
      text-transform: uppercase;
    }

    /* ── Body card ──────────────────────────────────────── */
    .email-body {
      background: #fff;
      padding: 32px;
      border-left: 1px solid #e2e8f0;
      border-right: 1px solid #e2e8f0;
    }

    .confirm-icon {
      text-align: center;
      font-size: 52px;
      margin-bottom: 16px;
      line-height: 1;
    }

    .confirm-title {
      text-align: center;
      font-size: 22px;
      font-weight: 800;
      color: #0d0d14;
      letter-spacing: -.03em;
      margin-bottom: 6px;
    }

    .confirm-sub {
      text-align: center;
      font-size: 14px;
      color: #64748b;
      margin-bottom: 28px;
      line-height: 1.5;
    }

    /* ── Detail rows ─────────────────────────────────────── */
    .details {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      overflow: hidden;
      margin-bottom: 24px;
    }

    .detail-row {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      padding: 12px 16px;
      border-bottom: 1px solid #e2e8f0;
      gap: 16px;
    }

    .detail-row:last-child { border-bottom: none; }

    .detail-label {
      font-size: 12px;
      font-weight: 600;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: .05em;
      white-space: nowrap;
      padding-top: 1px;
    }

    .detail-value {
      font-size: 14px;
      font-weight: 500;
      color: #0d0d14;
      text-align: right;
      word-break: break-word;
    }

    .detail-value.mono {
      font-family: 'Courier New', monospace;
      font-size: 13px;
      color: #4f46e5;
    }

    .detail-value.amount {
      font-size: 18px;
      font-weight: 800;
      color: #0d0d14;
      letter-spacing: -.03em;
    }

    /* ── Ticket barcode block ─────────────────────────────── */
    .ticket-block {
      border: 2px dashed #e2e8f0;
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      margin-bottom: 24px;
      background: #fff;
    }

    .ticket-ref {
      font-family: 'Courier New', monospace;
      font-size: 20px;
      font-weight: 700;
      color: #0d0d14;
      letter-spacing: .1em;
      margin-bottom: 8px;
    }

    .ticket-barcode {
      /* Fake barcode visual using alternating bars */
      height: 48px;
      background: repeating-linear-gradient(
        90deg,
        #0d0d14 0px, #0d0d14 3px,
        #fff 3px, #fff 6px,
        #0d0d14 6px, #0d0d14 8px,
        #fff 8px, #fff 12px,
        #0d0d14 12px, #0d0d14 14px,
        #fff 14px, #fff 17px,
        #0d0d14 17px, #0d0d14 19px,
        #fff 19px, #fff 24px
      );
      border-radius: 4px;
      margin: 12px auto 10px;
      max-width: 260px;
    }

    .ticket-qty {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #eef2ff;
      color: #4f46e5;
      font-size: 13px;
      font-weight: 700;
      padding: 5px 14px;
      border-radius: 20px;
      margin-top: 8px;
    }

    /* ── Date badge (appointment / reservation) ───────────── */
    .date-badge {
      background: #eef2ff;
      border: 1px solid #c7d2fe;
      border-radius: 10px;
      padding: 16px 20px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .date-icon { font-size: 28px; line-height: 1; flex-shrink: 0; }

    .date-main {
      font-size: 16px;
      font-weight: 700;
      color: #0d0d14;
      letter-spacing: -.02em;
    }

    .date-sub {
      font-size: 13px;
      color: #64748b;
      margin-top: 2px;
    }

    /* ── Business info ────────────────────────────────────── */
    .business-block {
      background: #f8fafc;
      border-radius: 10px;
      padding: 16px 20px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .business-avatar {
      width: 40px;
      height: 40px;
      background: #4f46e5;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      font-weight: 800;
      color: #fff;
      flex-shrink: 0;
    }

    .business-name {
      font-size: 14px;
      font-weight: 700;
      color: #0d0d14;
    }

    .business-sub {
      font-size: 12px;
      color: #94a3b8;
      margin-top: 2px;
    }

    /* ── Note box ─────────────────────────────────────────── */
    .note-box {
      background: #fffbeb;
      border: 1px solid #fde68a;
      border-radius: 8px;
      padding: 12px 14px;
      font-size: 13px;
      color: #92400e;
      line-height: 1.5;
      margin-bottom: 24px;
    }

    /* ── Footer ───────────────────────────────────────────── */
    .email-footer {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-top: none;
      border-radius: 0 0 14px 14px;
      padding: 20px 32px;
      text-align: center;
    }

    .email-footer p {
      font-size: 12px;
      color: #94a3b8;
      line-height: 1.6;
    }

    .email-footer a {
      color: #4f46e5;
      text-decoration: none;
    }

    .divider {
      border: none;
      border-top: 1px solid #e2e8f0;
      margin: 20px 0;
    }
  </style>
</head>
<body>
<div class="email-wrap">

  {{-- ── Header ─────────────────────────────────────────── --}}
  <div class="email-header">
    <div class="email-logo">BookIn<span>Stack</span></div>
    <div class="email-mode-pill">
      @if($modeConfig['mode'] === 'ticket') 🎟 Ticket
      @elseif($modeConfig['mode'] === 'reservation') 🏨 Reservation
      @else 🗓 Appointment
      @endif
    </div>
  </div>

  {{-- ── Body ───────────────────────────────────────────── --}}
  <div class="email-body">

    {{-- Icon + heading --}}
    {{-- <div class="confirm-icon">
      @if($modeConfig['mode'] === 'ticket') 🎟
      @elseif($modeConfig['mode'] === 'reservation') 🏨
      @else ✅
      @endif
    </div> --}}

    <div class="confirm-title">{{ $modeConfig['success_message'] }}</div>
    <div class="confirm-sub">
      Hi {{ $booking->customer_name ?: $booking->customer_email }},
      your {{ strtolower($modeConfig['label']) }} has been confirmed and payment received.
    </div>

    {{-- ── TICKET: QR code block ──────────────────────────── --}}
    @if($modeConfig['mode'] === 'ticket')
      <div class="ticket-block">
        {{-- Business name --}}
        <div style="font-size:11px; color:#94a3b8; text-transform:uppercase; letter-spacing:.12em; font-weight:600; margin-bottom:6px;">
          {{ $developer->business_name ?? $developer->name }}
        </div>

        {{-- Ticket / event name --}}
        <div style="font-size:17px; font-weight:800; color:#0d0d14; margin-bottom:2px; letter-spacing:-.2px;">
          {{ $booking->category?->name ?? $booking->description }}
        </div>
        @if($booking->category?->description)
          <div style="font-size:12px; color:#64748b; margin-bottom:8px;">
            {{ $booking->category->description }}
          </div>
        @endif

        <div class="ticket-ref">{{ $booking->reference }}</div>

        {{-- QR Code --}}
        @if($qrCodeSvg)
          <div style="margin:14px auto; width:160px; height:160px; padding:8px; background:#fff; border:2px solid #e2e8f0; border-radius:10px;">
            {!! $qrCodeSvg !!}
          </div>
          <div style="font-size:11px; color:#94a3b8; margin-bottom:8px; letter-spacing:.04em;">
            Scan at entry · Ref: {{ $booking->reference }}
          </div>
        @else
          <div class="ticket-barcode"></div>
        @endif

        <div class="ticket-qty">
          🎟 {{ $booking->adults ?? $booking->quantity ?? 1 }} × ticket{{ ($booking->adults ?? 1) > 1 ? 's' : '' }}
          @if(($booking->children ?? 0) > 0)
            · {{ $booking->children }} child{{ $booking->children > 1 ? 'ren' : '' }}
          @endif
        </div>
      </div>
    @endif

    {{-- ── APPOINTMENT: date badge ───────────────────────── --}}
    @if($modeConfig['mode'] === 'appointment' && ($booking->preferred_date || $booking->preferred_time))
      <div class="date-badge">
        <div class="date-icon">📅</div>
        <div>
          <div class="date-main">
            @if($booking->preferred_date)
              {{ \Carbon\Carbon::parse($booking->preferred_date)->format('l, d F Y') }}
            @endif
          </div>
          @if($booking->preferred_time)
            <div class="date-sub">
              at {{ \Carbon\Carbon::parse($booking->preferred_time)->format('g:i A') }}
            </div>
          @endif
        </div>
      </div>
    @endif

    {{-- QR for appointment & reservation (compact) --}}
    @if($modeConfig['mode'] !== 'ticket' && $qrCodeSvg)
      <div style="text-align:center; margin:16px 0;">
        <div style="display:inline-block; padding:8px; background:#fff; border:1px solid #e2e8f0; border-radius:8px; width:120px; height:120px;">
          {!! $qrCodeSvg !!}
        </div>
        <div style="font-size:11px; color:#94a3b8; margin-top:6px; font-family:monospace;">{{ $booking->reference }}</div>
      </div>
    @endif

    {{-- ── RESERVATION: date range badge ───────────────────── --}}
    @if($modeConfig['mode'] === 'reservation' && $booking->check_in)
      @php
        $nights = \Carbon\Carbon::parse($booking->check_in)
                    ->diffInDays(\Carbon\Carbon::parse($booking->check_out));
      @endphp
      <div class="date-badge">
        <div class="date-icon">🌙</div>
        <div>
          <div class="date-main">
            {{ \Carbon\Carbon::parse($booking->check_in)->format('d M Y') }}
            →
            {{ \Carbon\Carbon::parse($booking->check_out)->format('d M Y') }}
          </div>
          <div class="date-sub">
            {{ $nights }} night{{ $nights !== 1 ? 's' : '' }} · {{ $booking->description }}
          </div>
        </div>
      </div>
    @endif

    {{-- ── Business info ─────────────────────────────────── --}}
    <div class="business-block">
      <div class="business-avatar">{{ strtoupper(substr($developer->business_name ?: $developer->name, 0, 1)) }}</div>
      <div>
        <div class="business-name">{{ $developer->business_name ?: $developer->name }}</div>
        <div class="business-sub">{{ $developer->email }}</div>
      </div>
    </div>

    {{-- ── Booking details ────────────────────────────────── --}}
    <div class="details">

      <div class="detail-row">
        <span class="detail-label">Reference</span>
        <span class="detail-value mono">{{ $booking->reference }}</span>
      </div>

      <div class="detail-row">
        <span class="detail-label">{{ $modeConfig['desc_label'] }}</span>
        <span class="detail-value">{{ $booking->description }}</span>
      </div>

      @if($modeConfig['mode'] === 'ticket')
        <div class="detail-row">
          <span class="detail-label">Tickets</span>
          <span class="detail-value">{{ $booking->quantity }} × ₦{{ number_format($booking->amount , 2) }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Total Paid</span>
          <span class="detail-value amount">₦{{ number_format(($booking->amount * $booking->quantity) , 2) }}</span>
        </div>

      @elseif($modeConfig['mode'] === 'reservation')
        <div class="detail-row">
          <span class="detail-label">Check-in</span>
          <span class="detail-value">{{ \Carbon\Carbon::parse($booking->check_in)->format('d M Y') }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Check-out</span>
          <span class="detail-value">{{ \Carbon\Carbon::parse($booking->check_out)->format('d M Y') }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Duration</span>
          <span class="detail-value">{{ $nights }} night{{ $nights !== 1 ? 's' : '' }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Total Paid</span>
          <span class="detail-value amount">₦{{ number_format(($booking->amount * $nights) , 2) }}</span>
        </div>

      @else
        @if($booking->preferred_date)
          <div class="detail-row">
            <span class="detail-label">Date</span>
            <span class="detail-value">{{ \Carbon\Carbon::parse($booking->preferred_date)->format('d M Y') }}</span>
          </div>
        @endif
        @if($booking->preferred_time)
          <div class="detail-row">
            <span class="detail-label">Time</span>
            <span class="detail-value">{{ \Carbon\Carbon::parse($booking->preferred_time)->format('g:i A') }}</span>
          </div>
        @endif
        <div class="detail-row">
          <span class="detail-label">Amount Paid</span>
          <span class="detail-value amount">₦{{ number_format($booking->amount , 2) }}</span>
        </div>
      @endif

      <div class="detail-row">
        <span class="detail-label">Customer</span>
        <span class="detail-value">
          {{ $booking->customer_name ?: '—' }}<br />
          <span style="font-weight:400; color:#64748b; font-size:13px;">{{ $booking->customer_email }}</span>
        </span>
      </div>

      <div class="detail-row">
        <span class="detail-label">Paid on</span>
        <span class="detail-value">{{ $booking->paid_at?->format('d M Y, g:i A') ?? 'Confirmed' }}</span>
      </div>

    </div>

    {{-- Ticket instructions --}}
    @if($modeConfig['mode'] === 'ticket')
      <div class="note-box">
        🎫 <strong>Your ticket is attached to this email as a PDF.</strong>
        Download and save it — present it at entry (printed or on your phone).
        Your reference <strong>{{ $booking->reference }}</strong> will be scanned at the gate.
      </div>
    @elseif($modeConfig['mode'] === 'reservation')
      <div class="note-box">
        🏨 <strong>Please bring this confirmation to check in.</strong>
        Early check-in is subject to availability. Contact the property for special requests.
      </div>
    @else
      <div class="note-box">
        📋 <strong>Please arrive a few minutes early.</strong>
        If you need to reschedule, contact us as soon as possible with your reference number.
      </div>
    @endif

  </div>

  {{-- ── Footer ─────────────────────────────────────────── --}}
  <div class="email-footer">
    <p>
      This is an automated confirmation from
      <strong>{{ $developer->business_name ?: $developer->name }}</strong>
      powered by <a href="https://bookinstack.dev">BookInStack</a>.
    </p>
    <p style="margin-top:8px;">
      Keep your reference number: <strong style="color:#4f46e5;">{{ $booking->reference }}</strong>
    </p>
  </div>

</div>
</body>
</html>
