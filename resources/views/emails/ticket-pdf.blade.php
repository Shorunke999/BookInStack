<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'DejaVu Sans', Arial, sans-serif;
      background: #ffffff;
      color: #0d0d14;
      font-size: 13px;
      line-height: 1.5;
      width: 100%;
    }

    .hdr {
      background: #0d0d14;
      padding: 22px 16px 16px;
      text-align: center;
    }
    .hdr-biz {
      font-size: 20px;
      font-weight: bold;
      color: #ffffff;
      margin-bottom: 4px;
    }
    .hdr-lbl {
      font-size: 10px;
      font-weight: bold;
      color: #818cf8;
      letter-spacing: 3px;
      text-transform: uppercase;
    }

    .banner {
      background: #4f46e5;
      padding: 14px 16px;
      text-align: center;
    }
    .banner-name {
      font-size: 17px;
      font-weight: bold;
      color: #ffffff;
      margin-bottom: 2px;
    }
    .banner-desc {
      font-size: 11px;
      color: rgba(255,255,255,0.75);
    }

    .body-pad { padding: 16px 16px 0 16px; }

    .qr-section {
      text-align: center;
      padding-bottom: 14px;
      border-bottom: 2px dashed #e2e8f0;
      margin-bottom: 14px;
    }
    .qr-section img {
      width: 150px;
      height: 150px;
      display: block;
      margin: 0 auto;
    }
    .qr-placeholder {
      width: 150px;
      height: 150px;
      background: #f1f5f9;
      border: 2px dashed #cbd5e1;
      margin: 0 auto;
    }
    .scan-label {
      font-size: 10px;
      color: #94a3b8;
      margin-top: 8px;
      letter-spacing: 1px;
      text-transform: uppercase;
    }

    .ref-badge {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      padding: 10px 14px;
      text-align: center;
      margin-bottom: 14px;
    }
    .ref-lbl {
      font-size: 9px;
      font-weight: bold;
      color: #94a3b8;
      letter-spacing: 2px;
      text-transform: uppercase;
      margin-bottom: 4px;
    }
    .ref-code {
      font-size: 14px;
      font-weight: bold;
      color: #0d0d14;
      letter-spacing: 2px;
      font-family: 'DejaVu Sans Mono', monospace;
    }

    .detail-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 14px;
    }
    .detail-table td {
      padding: 8px 0;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: top;
    }
    .detail-table tr:last-child td { border-bottom: none; }
    .dt-label {
      font-size: 9px;
      font-weight: bold;
      color: #94a3b8;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      width: 38%;
    }
    .dt-value {
      font-size: 12px;
      font-weight: bold;
      color: #0d0d14;
      text-align: right;
      width: 62%;
    }
    .dt-value.accent { color: #4f46e5; font-size: 13px; }
    .dt-value.small  { font-size: 10px; }

    .divider {
      border: none;
      border-top: 2px dashed #e2e8f0;
      margin: 12px 0;
    }

    .footer {
      background: #f8fafc;
      border-top: 1px solid #e2e8f0;
      padding: 12px 16px;
      text-align: center;
      margin-top: 14px;
    }
    .footer-note {
      font-size: 10px;
      color: #94a3b8;
      line-height: 1.6;
      margin-bottom: 3px;
    }
    .footer-powered {
      font-size: 9px;
      color: #cbd5e1;
      letter-spacing: 1px;
      text-transform: uppercase;
    }
  </style>
</head>
<body>

<div class="hdr">
  <div class="hdr-biz">{{ $developer->business_name ?? $developer->name }}</div>
  <div class="hdr-lbl">EVENT TICKET</div>
</div>

<div class="banner">
  <div class="banner-name">{{ $booking->category?->name ?? $booking->description }}</div>
  @if($booking->category?->description)
    <div class="banner-desc">{{ $booking->category->description }}</div>
  @endif
</div>

<div class="body-pad">

  <div class="qr-section">
    @if($qrBase64)
      <img src="{{ $qrBase64 }}" alt="QR" />
    @else
      <div class="qr-placeholder"></div>
    @endif
    <div class="scan-label">SCAN AT ENTRY &bull; REF: {{ $booking->reference }}</div>
  </div>

  <div class="ref-badge">
    <div class="ref-lbl">Booking Reference</div>
    <div class="ref-code">{{ $booking->reference }}</div>
  </div>

  <table class="detail-table">
    <tr>
      <td class="dt-label">Name</td>
      <td class="dt-value">{{ $booking->customer_name ?? '—' }}</td>
    </tr>
    <tr>
      <td class="dt-label">Email</td>
      <td class="dt-value small">{{ $booking->customer_email }}</td>
    </tr>
    <tr>
      <td class="dt-label">Tickets</td>
      <td class="dt-value">
        @php $adults = $booking->adults ?? 1; $children = $booking->children ?? 0; @endphp
        {{ $adults }} Adult{{ $adults > 1 ? 's' : '' }}@if($children > 0) &middot; {{ $children }} Child{{ $children > 1 ? 'ren' : '' }}@endif
      </td>
    </tr>
    <tr>
      <td class="dt-label">Amount Paid</td>
      <td class="dt-value accent">
        @php $total = ($booking->amount * max(1, $adults + $children)); @endphp
        NGN {{ number_format($total, 2) }}
      </td>
    </tr>
    @if($booking->paid_at)
    <tr>
      <td class="dt-label">Paid On</td>
      <td class="dt-value">{{ $booking->paid_at->format('d M Y, g:i A') }}</td>
    </tr>
    @endif
    <tr>
      <td class="dt-label">Issued By</td>
      <td class="dt-value">{{ $developer->business_name ?? $developer->name }}</td>
    </tr>
  </table>

  <hr class="divider" />

</div>

<div class="footer">
  <div class="footer-note">
    Present this ticket at entry (printed or on your phone).<br/>
    Non-transferable &bull; Valid ID may be required
  </div>
  <div class="footer-powered">Powered by BookInStack</div>
</div>

</body>
</html>