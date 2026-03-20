<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
      background: #ffffff;
      width: 400px;
    }

    /* ── Ticket wrapper ─────────────────────────────── */
    .ticket {
      width: 400px;
      min-height: 680px;
      background: #ffffff;
      position: relative;
      overflow: hidden;
    }

    /* ── Header band ────────────────────────────────── */
    .ticket-header {
      background: #0d0d14;
      padding: 28px 28px 22px;
      text-align: center;
    }

    .business-name {
      font-size: 22px;
      font-weight: 700;
      color: #ffffff;
      letter-spacing: -0.3px;
      margin-bottom: 4px;
    }

    .ticket-label {
      font-size: 11px;
      font-weight: 600;
      color: #818cf8;
      letter-spacing: 0.2em;
      text-transform: uppercase;
    }

    /* ── Event name banner ──────────────────────────── */
    .event-banner {
      background: #4f46e5;
      padding: 18px 28px;
      text-align: center;
    }

    .event-name {
      font-size: 20px;
      font-weight: 700;
      color: #ffffff;
      letter-spacing: -0.2px;
    }

    .event-desc {
      font-size: 12px;
      color: rgba(255,255,255,0.7);
      margin-top: 4px;
    }

    /* ── Body ───────────────────────────────────────── */
    .ticket-body {
      padding: 24px 28px;
      background: #ffffff;
    }

    /* ── QR code section ────────────────────────────── */
    .qr-section {
      text-align: center;
      padding: 20px 0 16px;
      border-bottom: 1px dashed #e2e8f0;
      margin-bottom: 20px;
    }

    .qr-section img {
      width: 160px;
      height: 160px;
    }

    .qr-placeholder {
      width: 160px;
      height: 160px;
      background: #f1f5f9;
      border: 2px dashed #cbd5e1;
      display: inline-block;
      line-height: 160px;
      font-size: 12px;
      color: #94a3b8;
    }

    .scan-label {
      font-size: 11px;
      color: #94a3b8;
      margin-top: 8px;
      letter-spacing: 0.05em;
    }

    /* ── Details rows ───────────────────────────────── */
    .detail-row {
      display: table;
      width: 100%;
      margin-bottom: 12px;
      border-bottom: 1px solid #f1f5f9;
      padding-bottom: 10px;
    }

    .detail-row:last-child {
      border-bottom: none;
    }

    .detail-label {
      display: table-cell;
      font-size: 11px;
      font-weight: 600;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      width: 40%;
      vertical-align: middle;
    }

    .detail-value {
      display: table-cell;
      font-size: 14px;
      font-weight: 600;
      color: #0d0d14;
      vertical-align: middle;
    }

    /* ── Reference badge ────────────────────────────── */
    .ref-badge {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 12px 16px;
      text-align: center;
      margin-bottom: 20px;
    }

    .ref-label {
      font-size: 10px;
      font-weight: 700;
      color: #94a3b8;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      margin-bottom: 4px;
    }

    .ref-code {
      font-size: 16px;
      font-weight: 700;
      color: #0d0d14;
      letter-spacing: 0.08em;
      font-family: 'DejaVu Sans Mono', monospace;
    }

    /* ── Tear line ──────────────────────────────────── */
    .tear-line {
      border: none;
      border-top: 2px dashed #e2e8f0;
      margin: 20px 0;
    }

    /* ── Footer ─────────────────────────────────────── */
    .ticket-footer {
      background: #f8fafc;
      padding: 14px 28px;
      text-align: center;
      border-top: 1px solid #e2e8f0;
    }

    .footer-note {
      font-size: 11px;
      color: #94a3b8;
      line-height: 1.6;
    }

    .powered {
      font-size: 10px;
      color: #cbd5e1;
      margin-top: 6px;
      letter-spacing: 0.05em;
    }
  </style>
</head>
<body>
<div class="ticket">

  {{-- Header --}}
  <div class="ticket-header">
    <div class="business-name">{{ $developer->business_name ?? $developer->name }}</div>
    <div class="ticket-label">🎟 Event Ticket</div>
  </div>

  {{-- Event / ticket name banner --}}
  <div class="event-banner">
    <div class="event-name">{{ $booking->category?->name ?? $booking->description }}</div>
    @if($booking->category?->description)
      <div class="event-desc">{{ $booking->category->description }}</div>
    @endif
  </div>

  <div class="ticket-body">

    {{-- QR code --}}
    <div class="qr-section">
      @if($qrBase64)
        <img src="{{ $qrBase64 }}" alt="QR Code" />
      @else
        <div class="qr-placeholder">QR CODE</div>
      @endif
      <div class="scan-label">Scan at entry · Ref: {{ $booking->reference }}</div>
    </div>

    {{-- Reference badge --}}
    <div class="ref-badge">
      <div class="ref-label">Booking Reference</div>
      <div class="ref-code">{{ $booking->reference }}</div>
    </div>

    {{-- Details --}}
    <div class="detail-row">
      <div class="detail-label">Name</div>
      <div class="detail-value">{{ $booking->customer_name ?? '—' }}</div>
    </div>

    <div class="detail-row">
      <div class="detail-label">Email</div>
      <div class="detail-value" style="font-size:12px;">{{ $booking->customer_email }}</div>
    </div>

    <div class="detail-row">
      <div class="detail-label">Tickets</div>
      <div class="detail-value">
        {{ $booking->adults ?? 1 }} Adult{{ ($booking->adults ?? 1) > 1 ? 's' : '' }}
        @if(($booking->children ?? 0) > 0)
          · {{ $booking->children }} Child{{ $booking->children > 1 ? 'ren' : '' }}
        @endif
      </div>
    </div>

    <div class="detail-row">
      <div class="detail-label">Amount Paid</div>
      <div class="detail-value" style="color:#4f46e5;">
        ₦{{ number_format(($booking->amount * max(1, ($booking->adults ?? 1) + ($booking->children ?? 0))) , 2) }}
      </div>
    </div>

    @if($booking->paid_at)
    <div class="detail-row">
      <div class="detail-label">Paid On</div>
      <div class="detail-value">{{ $booking->paid_at->format('d M Y, g:i A') }}</div>
    </div>
    @endif

    <hr class="tear-line" />

    <div class="detail-row" style="margin-bottom:0;border:none;padding-bottom:0;">
      <div class="detail-label">Issued By</div>
      <div class="detail-value" style="font-size:13px;">{{ $developer->business_name ?? $developer->name }}</div>
    </div>

  </div>

  {{-- Footer --}}
  <div class="ticket-footer">
    <div class="footer-note">
      🎫 Present this ticket (printed or on your phone) at entry.<br/>
      This ticket is non-transferable. Valid ID may be required.
    </div>
    <div class="powered">Powered by BookInStack</div>
  </div>

</div>
</body>
</html>