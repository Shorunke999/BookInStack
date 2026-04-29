@extends('layouts.app')
@section('title', 'Integration')
@section('page-title', 'Integration Guide')

@section('content')

<div style="max-width:800px; display:flex; flex-direction:column; gap:20px;">
    <a href="/sdk/test.html" target="_blank" class="nav-item">
    @include('components.icon', ['name' => 'code'])
    SDK Console ↗
</a>
    {{-- ── 1. Include ──────────────────────────────────────────────────── --}}
    <div class="card">
        <h3>1. Include the SDK</h3>
        <p style="font-size:14px; color:var(--muted); margin-top:6px; margin-bottom:0;">
            One script tag on any page where you want to accept bookings.
        </p>
        <div class="code-block">
            <pre><span class="kw">&lt;script</span> <span class="fn">src</span>=<span class="str">"https://cdn.BookStackIn.dev/booking.js"</span><span class="kw">&gt;&lt;/script&gt;</span></pre>
        </div>
    </div>

    {{-- ── 2. Init ──────────────────────────────────────────────────────── --}}
    <div class="card">
        <h3>2. Initialize</h3>
        <p style="font-size:14px; color:var(--muted); margin-top:6px; margin-bottom:0;">
            Call once on page load. <code>returnUrl</code> is where Paystack redirects after payment —
            it appends <code>?reference=PAY-xxx</code> to whatever URL you set.
            @if($developer->public_key)
                Your live key is pre-filled.
            @else
                <a href="{{ route('dashboard.api-keys') }}" style="color:var(--accent);">Verify your BVN</a> to get your key.
            @endif
        </p>
        <div class="code-block">
            <pre><span class="fn">Booking</span>.<span class="fn">init</span>({
  <span class="fn">publicKey</span>: <span class="str">"{{ $developer->public_key ?? 'pk_live_your_key_here' }}"</span>,
  <span class="fn">returnUrl</span>: <span class="str">"https://yoursite.com/booking/success"</span>,
});</pre>
        </div>
    </div>

    {{-- ── 3. Create + Pay ──────────────────────────────────────────────── --}}
    <div class="card">
        <h3>3. Create a booking then pay</h3>
        <p style="font-size:14px; color:var(--muted); margin-top:6px; margin-bottom:0;">
            <strong>Amounts are in kobo</strong> — ₦1 = 100 kobo.
            <code>Booking.pay()</code> hits your backend to initialize the Paystack transaction,
            then redirects the browser to Paystack's hosted checkout. No popup, no extra JS dependency.
            You can override <code>returnUrl</code> per-payment if needed.
        </p>
        <div class="code-block">
            <pre><span class="cm">// 1 — create the booking</span>
<span class="kw">const</span> booking = <span class="kw">await</span> <span class="fn">Booking</span>.<span class="fn">create</span>({
  amount:         <span class="fn">500000</span>,              <span class="cm">// ₦5,000 in kobo</span>
  description:    <span class="str">"Consultation"</span>,
  customer_email: <span class="str">"user@example.com"</span>,
  customer_name:  <span class="str">"Jane Doe"</span>,          <span class="cm">// optional</span>
  customer_phone: <span class="str">"08012345678"</span>,        <span class="cm">// optional</span>
  metadata:       { service_id: <span class="str">"SVC-001"</span> }, <span class="cm">// optional — stored on booking</span>
});

<span class="cm">// 2 — redirect to Paystack checkout</span>
<span class="kw">await</span> <span class="fn">Booking</span>.<span class="fn">pay</span>(booking.reference, {
  returnUrl: <span class="str">"https://yoursite.com/success"</span>,  <span class="cm">// overrides global returnUrl if needed</span>
});</pre>
        </div>

        {{-- Create payload --}}
        <div style="margin-top:16px; font-size:12px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px;">
            POST /api/bookings — Response
        </div>
        <div class="code-block">
            <pre>{
  <span class="fn">"booking"</span>: {
    <span class="fn">"id"</span>:               <span class="fn">1</span>,
    <span class="fn">"reference"</span>:        <span class="str">"BKG-A1B2C3D4E5F6"</span>,
    <span class="fn">"amount"</span>:           <span class="fn">500000</span>,
    <span class="fn">"description"</span>:      <span class="str">"Consultation"</span>,
    <span class="fn">"customer_email"</span>:   <span class="str">"user@example.com"</span>,
    <span class="fn">"customer_name"</span>:    <span class="str">"Jane Doe"</span>,
    <span class="fn">"customer_phone"</span>:   <span class="str">"08012345678"</span>,
    <span class="fn">"status"</span>:           <span class="str">"pending"</span>,
    <span class="fn">"attended"</span>:         <span class="kw">false</span>,
    <span class="fn">"paid_at"</span>:          <span class="kw">null</span>,
    <span class="fn">"created_at"</span>:       <span class="str">"2025-03-10T12:00:00.000000Z"</span>
  }
}</pre>
        </div>

        {{-- Pay payload --}}
        <div style="margin-top:16px; font-size:12px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px;">
            POST /api/payments/initialize — Response
        </div>
        <div class="code-block">
            <pre>{
  <span class="fn">"authorization_url"</span>: <span class="str">"https://checkout.paystack.com/0peioxfhpn"</span>,
  <span class="fn">"access_code"</span>:       <span class="str">"0peioxfhpn"</span>,
  <span class="fn">"reference"</span>:         <span class="str">"PAY-BKG-A1B2C3D4E5F6-1710072000"</span>
}
<span class="cm">// SDK redirects browser to authorization_url immediately after this</span></pre>
        </div>
    </div>

    {{-- ── 4. Return page ───────────────────────────────────────────────── --}}
    <div class="card">
        <h3>4. Handle the return page</h3>
        <p style="font-size:14px; color:var(--muted); margin-top:6px; margin-bottom:0;">
            Paystack redirects back to your <code>returnUrl</code> with <code>?reference=PAY-xxx</code>
            appended. Your webhook has already updated the booking status — just read the reference
            and show confirmation. No API call needed on this page.
        </p>
        <div class="code-block">
            <pre><span class="cm">&lt;!-- success.html --&gt;</span>
<span class="kw">&lt;script</span> <span class="fn">src</span>=<span class="str">"booking.js"</span><span class="kw">&gt;&lt;/script&gt;</span>
<span class="kw">&lt;script&gt;</span>
  <span class="fn">Booking</span>.<span class="fn">init</span>({ <span class="fn">publicKey</span>: <span class="str">"pk_live_xxx"</span> });

  <span class="kw">const</span> params    = <span class="kw">new</span> <span class="fn">URLSearchParams</span>(window.location.search);
  <span class="kw">const</span> reference = params.<span class="fn">get</span>(<span class="str">'reference'</span>) || params.<span class="fn">get</span>(<span class="str">'trxref'</span>);

  <span class="kw">if</span> (reference) {
    document.<span class="fn">getElementById</span>(<span class="str">'ref'</span>).textContent = reference;
    <span class="cm">// Optionally fetch the booking to show details</span>
    <span class="fn">Booking</span>.<span class="fn">get</span>(reference).<span class="fn">then</span>(b =&gt; console.<span class="fn">log</span>(b));
  }
<span class="kw">&lt;/script&gt;</span>
<span class="kw">&lt;p&gt;</span>Payment confirmed! Ref: <span class="kw">&lt;strong</span> <span class="fn">id</span>=<span class="str">"ref"</span><span class="kw">&gt;&lt;/strong&gt;&lt;/p&gt;</span></pre>
        </div>
    </div>

    {{-- ── 5. Widget ────────────────────────────────────────────────────── --}}
    <div class="card">
        <h3>5. Drop-in widget</h3>
        <p style="font-size:14px; color:var(--muted); margin-top:6px; margin-bottom:0;">
            A fully self-contained booking form. Set <code>amount</code> to fix the price,
            or omit it to let the customer enter their own.
        </p>
        <div class="code-block">
            <pre><span class="kw">&lt;div</span> <span class="fn">id</span>=<span class="str">"booking-form"</span><span class="kw">&gt;&lt;/div&gt;</span>

<span class="fn">Booking</span>.<span class="fn">widget</span>(<span class="str">"#booking-form"</span>, {
  title:       <span class="str">"Book a Session"</span>,
  amount:      <span class="fn">500000</span>,            <span class="cm">// omit for open amount</span>
  description: <span class="str">"Consultation"</span>,    <span class="cm">// omit to let user fill in</span>
  returnUrl:   <span class="str">"https://yoursite.com/success"</span>,
});</pre>
        </div>
    </div>

    {{-- ── 6. Attendance ────────────────────────────────────────────────── --}}
    <div class="card">
        <h3>6. Mark attendance</h3>
        <p style="font-size:14px; color:var(--muted); margin-top:6px; margin-bottom:0;">
            Only works on bookings with <code>status: "paid"</code>.
        </p>
        <div class="code-block">
            <pre><span class="kw">await</span> <span class="fn">Booking</span>.<span class="fn">attend</span>(<span class="str">"BKG-A1B2C3D4E5F6"</span>, {
  attended: <span class="kw">true</span>,
  note:     <span class="str">"Arrived on time"</span>,  <span class="cm">// optional</span>
});</pre>
        </div>

        <div style="margin-top:16px; font-size:12px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px;">
            POST /api/bookings/{reference}/attend — Response
        </div>
        <div class="code-block">
            <pre>{
  <span class="fn">"message"</span>:  <span class="str">"Marked as attended"</span>,
  <span class="fn">"booking"</span>: {
    <span class="fn">"reference"</span>:       <span class="str">"BKG-A1B2C3D4E5F6"</span>,
    <span class="fn">"status"</span>:          <span class="str">"paid"</span>,
    <span class="fn">"attended"</span>:        <span class="kw">true</span>,
    <span class="fn">"attended_at"</span>:     <span class="str">"2025-03-10T14:30:00.000000Z"</span>,
    <span class="fn">"attendance_note"</span>: <span class="str">"Arrived on time"</span>
  }
}</pre>
        </div>
    </div>

    {{-- ── 7. List ──────────────────────────────────────────────────────── --}}
    <div class="card">
        <h3>7. List bookings</h3>
        <div class="code-block">
            <pre><span class="kw">const</span> result = <span class="kw">await</span> <span class="fn">Booking</span>.<span class="fn">list</span>({ page: <span class="fn">1</span> });</pre>
        </div>

        <div style="margin-top:12px; font-size:12px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px;">
            GET /api/bookings — Response
        </div>
        <div class="code-block">
            <pre>{
  <span class="fn">"current_page"</span>: <span class="fn">1</span>,
  <span class="fn">"per_page"</span>:     <span class="fn">20</span>,
  <span class="fn">"total"</span>:        <span class="fn">4</span>,
  <span class="fn">"data"</span>: [
    {
      <span class="fn">"reference"</span>:      <span class="str">"BKG-A1B2C3D4E5F6"</span>,
      <span class="fn">"amount"</span>:         <span class="fn">500000</span>,
      <span class="fn">"description"</span>:    <span class="str">"Consultation"</span>,
      <span class="fn">"customer_email"</span>: <span class="str">"user@example.com"</span>,
      <span class="fn">"status"</span>:         <span class="str">"paid"</span>,
      <span class="fn">"attended"</span>:       <span class="kw">true</span>,
      <span class="fn">"paid_at"</span>:        <span class="str">"2025-03-10T12:05:00.000000Z"</span>,
      <span class="fn">"created_at"</span>:     <span class="str">"2025-03-10T12:00:00.000000Z"</span>
    }
  ]
}</pre>
        </div>
    </div>

    {{-- ── 8. Events ────────────────────────────────────────────────────── --}}
    <div class="card">
        <h3>8. Window events</h3>
        <p style="font-size:14px; color:var(--muted); margin-top:6px; margin-bottom:0;">
            Every SDK action fires a <code>BookStackIn:*</code> event on <code>window</code>.
        </p>
        <div class="code-block">
            <pre>window.<span class="fn">addEventListener</span>(<span class="str">'BookStackIn:ready'</span>, (e) =&gt; {});
window.<span class="fn">addEventListener</span>(<span class="str">'BookStackIn:booking:created'</span>, (e) =&gt; {
  console.<span class="fn">log</span>(e.detail); <span class="cm">// full booking object</span>
});
window.<span class="fn">addEventListener</span>(<span class="str">'BookStackIn:payment:redirecting'</span>, (e) =&gt; {
  console.<span class="fn">log</span>(e.detail.reference, e.detail.returnUrl);
});
window.<span class="fn">addEventListener</span>(<span class="str">'BookStackIn:booking:attendance'</span>, (e) =&gt; {
  console.<span class="fn">log</span>(e.detail); <span class="cm">// updated booking</span>
});</pre>
        </div>
    </div>

    {{-- ── 9. Webhook ───────────────────────────────────────────────────── --}}
    <div class="card">
        <h3>9. Paystack webhook payload</h3>
        <p style="font-size:14px; color:var(--muted); margin-top:6px; margin-bottom:0;">
            BookStackIn handles this automatically — your booking is marked <code>paid</code>
            and the 95%/5% split is recorded when this arrives. Shown here for reference.
        </p>
        <div class="code-block">
            <pre>{
  <span class="fn">"event"</span>: <span class="str">"charge.success"</span>,
  <span class="fn">"data"</span>: {
    <span class="fn">"reference"</span>: <span class="str">"PAY-BKG-A1B2C3D4E5F6-1710072000"</span>,
    <span class="fn">"amount"</span>:    <span class="fn">500000</span>,
    <span class="fn">"currency"</span>: <span class="str">"NGN"</span>,
    <span class="fn">"status"</span>:   <span class="str">"success"</span>,
    <span class="fn">"channel"</span>:  <span class="str">"card"</span>,
    <span class="fn">"customer"</span>: { <span class="fn">"email"</span>: <span class="str">"user@example.com"</span> },
    <span class="fn">"metadata"</span>: {
      <span class="fn">"booking_reference"</span>: <span class="str">"BKG-A1B2C3D4E5F6"</span>,
      <span class="fn">"developer_id"</span>:      <span class="fn">1</span>
    }
  }
}
<span class="cm">// → booking status set to "paid"</span>
<span class="cm">// → payment recorded: developer_amount = ₦4,750 (95%), platform_fee = ₦250 (5%)</span></pre>
        </div>
    </div>

    {{-- ── Key card ─────────────────────────────────────────────────────── --}}
    @if($developer->public_key)
    <div class="card" style="background:var(--accent-light); border-color:#c7d2fe;">
        <h3 style="color:var(--accent);">Your Public Key</h3>
        <p style="font-size:14px; color:var(--muted); margin:6px 0 12px;">
            Use this in all <code>Booking.init()</code> calls.
        </p>
        <div class="key-box" style="background:#fff;">
            <div class="key-value">{{ $developer->public_key }}</div>
            <button class="btn btn-sm btn-outline" onclick="
                navigator.clipboard.writeText('{{ $developer->public_key }}');
                this.textContent = 'Copied!';
                setTimeout(() => this.textContent = 'Copy', 2000);
            ">Copy</button>
        </div>
    </div>
    @endif
    
</div>

@endsection