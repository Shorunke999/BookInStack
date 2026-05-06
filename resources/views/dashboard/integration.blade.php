@push('styles')
<style>
@media (max-width: 768px) {
    .card { padding: 16px !important; }
    pre   { font-size: 11px !important; }
    code  { font-size: 11px !important; word-break: break-all; }
    [style*="display:grid;grid-template-columns:1fr 1fr"] {
        display: flex !important; flex-direction: column !important;
    }
}
</style>
@endpush

@extends('layouts.app')
@section('title', 'Integration Guide')
@section('page-title', 'Integration Guide')

@section('content')

@php
    $pk      = $developer->public_key ?? 'YOUR_PUBLIC_KEY';
    $baseUrl = config('app.url') . '/api';
    $mode    = $developer->booking_mode;
    $unit    = $developer->reservation_unit ?? 'night';
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:#f8fafc;border:1px solid var(--border);border-radius:8px;margin-bottom:28px;flex-wrap:wrap;gap:10px;">
    <div>
        <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;">Your Public Key</div>
        <code style="font-size:13px;color:var(--accent);">{{ $pk }}</code>
    </div>
    <button onclick="navigator.clipboard.writeText('{{ $pk }}').then(()=>this.textContent='Copied!').catch(()=>{})" class="btn btn-outline btn-sm">Copy Key</button>
</div>

<div style="display:flex;gap:2px;margin-bottom:28px;border-bottom:1px solid var(--border);overflow-x:auto;">
    @foreach([['quickstart','🚀','Quickstart'],['negotiate','💬','Negotiate'],['payment-links','🔗','Payment Links'],['modes','🗓','Modes'],['widget-config','🎨','Widget']] as [$id,$icon,$label])
        <button onclick="switchTab('{{ $id }}')" id="itab-{{ $id }}" style="padding:10px 16px;font-size:13px;font-weight:600;cursor:pointer;border:none;background:none;margin-bottom:-1px;transition:all .15s;white-space:nowrap;border-bottom:2px solid {{ $id==='quickstart'?'var(--accent)':'transparent' }};color:{{ $id==='quickstart'?'var(--accent)':'var(--muted)' }};">{{ $icon }} {{ $label }}</button>
    @endforeach
</div>

{{-- QUICKSTART --}}
<div id="ipanel-quickstart">
    <div class="card" style="margin-bottom:16px;"><h3 style="margin-bottom:8px;">1. Load the SDK</h3>
        <pre style="background:#0d0d14;color:#a5b4fc;padding:14px 16px;border-radius:8px;font-size:13px;overflow-x:auto;overflow-x:auto;white-space:pre;word-break:normal;">&lt;script src="{{ config('app.url') }}/sdk/booking.js"&gt;&lt;/script&gt;</pre>
    </div>
    <div class="card" style="margin-bottom:16px;"><h3 style="margin-bottom:8px;">2. Add a container</h3>
        <pre style="background:#0d0d14;color:#a5b4fc;padding:14px 16px;border-radius:8px;font-size:13px;overflow-x:auto;white-space:pre;word-break:normal;">&lt;div id="booking-widget"&gt;&lt;/div&gt;</pre>
    </div>
    <div class="card" style="margin-bottom:16px;"><h3 style="margin-bottom:8px;">3. Initialize</h3>
        <pre style="background:#0d0d14;color:#a5b4fc;padding:14px 16px;border-radius:8px;font-size:13px;line-height:1.7;overflow-x:auto;white-space:pre;word-break:normal;">(async () => {
  await Booking.init({
    publicKey: '{{ $pk }}',
    baseUrl:   '{{ $baseUrl }}',
    returnUrl: window.location.href + '?booking=success',
    onSuccess: (tx) => console.log('Paid:', tx.reference),
  });
  Booking.widget('#booking-widget', { title: 'Book Now' });
})();</pre>
    </div>
    <div class="card"><h3 style="margin-bottom:8px;">4. Handle return</h3>
        <pre style="background:#0d0d14;color:#a5b4fc;padding:14px 16px;border-radius:8px;font-size:13px;line-height:1.7;overflow-x:auto;white-space:pre;word-break:normal;">const params = new URLSearchParams(window.location.search);
if (params.get('booking') === 'success') {
  const ref = params.get('reference') || params.get('trxref');
  // show success UI
  history.replaceState({}, '', window.location.pathname);
}</pre>
    </div>
</div>

{{-- NEGOTIATE --}}
<div id="ipanel-negotiate" style="display:none;">
    <div style="padding:14px 18px;background:{{ $developer->enable_negotiate?'#f0fdf4':'#fef2f2' }};border:1px solid {{ $developer->enable_negotiate?'#bbf7d0':'#fecaca' }};border-radius:8px;margin-bottom:20px;font-size:13px;color:{{ $developer->enable_negotiate?'#166534':'#991b1b' }};">
        Negotiation is <strong>{{ $developer->enable_negotiate ? '✓ Enabled' : '✗ Disabled' }}</strong>.
        @if(!$developer->enable_negotiate)<a href="{{ route('dashboard.booking-settings') }}#negotiate" style="color:var(--accent);margin-left:6px;">Enable in Settings →</a>@endif
    </div>
    <div class="card" style="margin-bottom:16px;"><h3 style="margin-bottom:10px;">Flow</h3>
        @foreach(['💬 Customer selects category in widget','📱 Clicks "Chat on WhatsApp" — pre-filled message sent','🤝 You negotiate price in WhatsApp chat','🔗 Generate payment link from Dashboard → Payment Links','💳 Customer pays → booking created automatically','✅ Webhook fires → confirmation email + QR ticket sent'] as $step)
            <div style="display:flex;gap:10px;font-size:13px;margin-bottom:8px;"><span>{{ explode(' ',$step,2)[0] }}</span><span style="color:var(--muted);">{{ explode(' ',$step,2)[1] }}</span></div>
        @endforeach
    </div>
    <div class="card" style="margin-bottom:16px;"><h3 style="margin-bottom:10px;">Fixed vs Custom price</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div style="padding:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:13px;"><strong style="color:#166534;">Fixed Price ON</strong><div style="color:#166534;margin-top:4px;font-size:12px;">Shows price + Pay button. Normal checkout.</div></div>
            <div style="padding:12px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:13px;"><strong style="color:#92400e;">Fixed Price OFF</strong><div style="color:#92400e;margin-top:4px;font-size:12px;">Hides pay button, shows WhatsApp negotiate card.</div></div>
        </div>
    </div>
    <div class="card"><h3 style="margin-bottom:8px;">Pre-filled WhatsApp message</h3>
        <pre style="background:#0d0d14;color:#a5b4fc;padding:14px 16px;border-radius:8px;font-size:12px;line-height:1.7;overflow-x:auto;white-space:pre;word-break:normal;">Hello,

I am interested in making a booking.
📋 *{{ ucfirst($mode) }} Details*
Service: [Selected Category]
Listed Price: ₦XX,XXX

My Name: [Customer Name]
My Email: [Customer Email]

Can we discuss availability and pricing?</pre>
    </div>
</div>

{{-- PAYMENT LINKS --}}
<div id="ipanel-payment-links" style="display:none;">
    <div class="card" style="margin-bottom:16px;"><h3 style="margin-bottom:8px;">What are payment links?</h3>
        <p style="font-size:13px;color:var(--muted);line-height:1.7;">Generate a unique link, share anywhere (WhatsApp, SMS, email), customer pays on a clean hosted page. Booking created automatically on payment. Ideal for negotiated prices.</p>
    </div>
    <div class="card" style="margin-bottom:16px;"><h3 style="margin-bottom:10px;">Creating a link</h3>
        @foreach(['Dashboard → Payment Links → New Payment Link','Enter customer name, email, phone','Select a category (auto-fills price + description)','Enter negotiated amount in Naira','Set expiry (default 48 hrs)','Share via WhatsApp or email button'] as $i=>$step)
            <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:10px;font-size:13px;"><span style="width:22px;height:22px;background:var(--accent);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">{{ $i+1 }}</span><span style="color:var(--muted);line-height:1.5;">{{ $step }}</span></div>
        @endforeach
    </div>
    <div class="card"><h3 style="margin-bottom:8px;">Statuses</h3>
        @foreach([['pending','#d97706','#fffbeb','Awaiting payment'],['paid','#15803d','#f0fdf4','Payment received'],['expired','#6b7280','#f3f4f6','Past expiry'],['cancelled','#ef4444','#fef2f2','Manually cancelled']] as [$s,$c,$bg,$d])
            <div style="display:flex;align-items:center;gap:10px;font-size:13px;padding:8px 0;border-bottom:1px solid var(--border);"><span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px;background:{{ $bg }};color:{{ $c }};">{{ ucfirst($s) }}</span><span style="color:var(--muted);">{{ $d }}</span></div>
        @endforeach
    </div>
</div>

{{-- MODES --}}
<div id="ipanel-modes" style="display:none;">
    <div style="padding:12px 16px;background:var(--accent-light);border:1px solid var(--accent);border-radius:8px;margin-bottom:20px;font-size:13px;">Active mode: <strong style="color:var(--accent);">{{ ucfirst($mode) }}</strong>@if($mode==='reservation') · Unit: <strong style="color:var(--accent);">{{ ucfirst($unit) }}</strong>@endif</div>
    @foreach([
        ['appointment','🗓','Appointment','Date + time · Clinics, salons, consultants','preferred_date: YYYY-MM-DD'."\n".'preferred_time: H:i'."\n".'category_id: integer'],
        ['ticket','🎟','Ticket','Quantities · Events, concerts','category_id: integer'."\n".'adults: integer (min 1)'."\n".'children: integer (min 0)'],
        ['reservation','🏨','Reservation','Date range · Hotels, halls','category_id: integer'."\n".'check_in: YYYY-MM-DD'."\n".'check_out: YYYY-MM-DD'],
    ] as [$m,$em,$label,$desc,$fields])
        <div class="card" style="margin-bottom:12px;{{ $mode===$m?'border-color:var(--accent);':'' }}">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;"><span style="font-size:20px;">{{ $em }}</span><div><div style="font-weight:700;font-size:14px;">{{ $label }}</div><div style="font-size:12px;color:var(--muted);">{{ $desc }}</div></div>@if($mode===$m)<span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px;background:var(--accent);color:#fff;margin-left:auto;">Active</span>@endif</div>
            <pre style="background:#0d0d14;color:#a5b4fc;padding:10px 14px;border-radius:6px;font-size:12px;overflow-x:auto;white-space:pre;word-break:normal;">{{ $fields }}</pre>
        </div>
    @endforeach
    <div class="card"><h3 style="margin-bottom:6px;">Day vs Night unit</h3><p style="font-size:13px;color:var(--muted);line-height:1.6;">Set in Settings → Mode. <strong>Day</strong> = event centres (Start/End date, ☀️ 2 days). <strong>Night</strong> = hotels (Check-in/Check-out, 🌙 3 nights). SDK reads automatically.</p></div>
</div>

{{-- WIDGET CONFIG --}}
<div id="ipanel-widget-config" style="display:none;">
    <div class="card" style="margin-bottom:16px;"><h3 style="margin-bottom:10px;">Appearance settings</h3>
        @foreach(['Background' => 'None (white), solid color, or image URL','Accent color' => 'Button + highlight — default #4f46e5','Corner radius' => '0px (sharp) to 28px (pill)','Branding' => 'Toggle "Powered by BookInStack"'] as $k=>$v)
            <div style="display:flex;gap:12px;font-size:13px;padding:9px 0;border-bottom:1px solid var(--border);"><span style="min-width:130px;font-weight:600;">{{ $k }}</span><span style="color:var(--muted);">{{ $v }}</span></div>
        @endforeach
    </div>
    <div class="card" style="margin-bottom:16px;"><h3 style="margin-bottom:8px;">Booking window</h3><p style="font-size:13px;color:var(--muted);line-height:1.6;">Restrict days + hours in Settings → Hours. SDK checks on load — shows "Bookings closed" if outside window. Also enforced server-side on booking creation and payment link payment.</p></div>
    <div class="card"><h3 style="margin-bottom:8px;">SDK state</h3>
        <pre style="background:#0d0d14;color:#a5b4fc;padding:14px 16px;border-radius:8px;font-size:12px;line-height:1.8;overflow-x:auto;white-space:pre;word-break:normal;">await Booking.init({ publicKey, baseUrl });

Booking.mode            // 'appointment' | 'ticket' | 'reservation'
Booking.reservationUnit // 'night' | 'day'
Booking.enableNegotiate // true | false
Booking.whatsappNumber  // developer WhatsApp number
Booking.widgetConfig    // { bg_type, accent_color, border_radius, ... }
Booking.catalog         // array of active categories</pre>
    </div>
</div>

{{-- WEBHOOKS --}}
{{-- <div id="ipanel-webhook" style="display:none;">
    <div class="card" style="margin-bottom:16px;"><h3 style="margin-bottom:8px;">Webhook URL</h3>
        <p style="font-size:13px;color:var(--muted);margin-bottom:10px;">Register this in Paystack dashboard → Settings → Webhooks:</p>
        <div style="display:flex;gap:8px;align-items:center;"><code style="flex:1;font-size:13px;background:#f8fafc;padding:10px 14px;border-radius:6px;border:1px solid var(--border);word-break:break-all;">{{ config('app.url') }}/webhooks/paystack</code><button onclick="navigator.clipboard.writeText('{{ config('app.url') }}/webhooks/paystack').then(()=>this.textContent='Copied!').catch(()=>{})" class="btn btn-outline btn-sm">Copy</button></div>
    </div>
    <div class="card" style="margin-bottom:16px;"><h3 style="margin-bottom:8px;">Events handled</h3>
        @foreach(['charge.success' => 'Marks booking as paid · Sends confirmation email with QR code · Attaches PDF ticket (ticket mode) · Updates payment link status if metadata contains payment_link_token'] as $e=>$a)
            <div style="font-size:13px;padding:10px 0;"><code style="color:var(--accent);">{{ $e }}</code><div style="color:var(--muted);margin-top:4px;line-height:1.5;">{{ $a }}</div></div>
        @endforeach
    </div>
    <div class="card"><h3 style="margin-bottom:8px;">Required packages</h3>
        <pre style="background:#0d0d14;color:#a5b4fc;padding:12px 16px;border-radius:8px;font-size:13px;overflow-x:auto;white-space:pre;word-break:normal;">composer require simplesoftwareio/simple-qrcode
composer require barryvdh/laravel-dompdf</pre>
        <p style="font-size:12px;color:var(--muted);margin-top:8px;">Both fail gracefully — emails send normally if not installed, QR and PDF are simply omitted.</p>
    </div>
</div> --}}

@push('scripts')
<script>
const ITABS = ['quickstart','negotiate','payment-links','modes','widget-config'];
function switchTab(id) {
    ITABS.forEach(t => {
        document.getElementById('ipanel-' + t).style.display = t === id ? 'block' : 'none';
        const b = document.getElementById('itab-' + t);
        b.style.borderBottomColor = t === id ? 'var(--accent)' : 'transparent';
        b.style.color = t === id ? 'var(--accent)' : 'var(--muted)';
    });
}
const hash = location.hash.replace('#','');
if (ITABS.includes(hash)) switchTab(hash);
</script>
@endpush

@endsection
