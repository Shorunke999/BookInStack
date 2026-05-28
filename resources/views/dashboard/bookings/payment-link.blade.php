{{-- resources/views/dashboard/bookings/payment-link.blade.php --}}

@extends('layouts.app')

@section('page-title', 'Payment Link')

@section('content')

<div style="max-width: 520px;">

    {{-- Header --}}
    <div style="margin-bottom: 20px;">
        <div class="mono" style="font-size:10px; text-transform:uppercase; letter-spacing:.1em; color:var(--muted); margin-bottom:8px;">Booking</div>
        <h1 style="font-size:20px; font-weight:700; color:var(--ink); letter-spacing:-.03em; margin-bottom:4px;">Payment Link</h1>
        <p style="font-size:13.5px; color:var(--muted); line-height:1.55;">Share this link with your customer to collect payment.</p>
    </div>

    {{-- Main card --}}
    <div class="card" style="padding:0; overflow:hidden;">

        {{-- Amount banner --}}
        <div style="background:var(--ink); padding:28px 28px 24px; position:relative; overflow:hidden;">

            {{-- Subtle glow --}}
            <div style="position:absolute; width:280px; height:280px; background:radial-gradient(circle, rgba(37,99,235,.18) 0%, transparent 70%); top:-80px; right:-60px; pointer-events:none;"></div>

            <div class="mono" style="font-size:10px; text-transform:uppercase; letter-spacing:.1em; color:rgba(255,255,255,.25); margin-bottom:10px;">Amount Due</div>

            <div style="font-size:36px; font-weight:700; color:#fff; letter-spacing:-.05em; line-height:1; margin-bottom:14px;">
                ₦{{ number_format($booking->amount, 2) }}
            </div>

            <div style="display:flex; flex-direction:column; gap:3px;">
                <div style="font-size:14px; font-weight:600; color:rgba(255,255,255,.85); letter-spacing:-.01em;">
                    {{ $booking->customer_name }}
                </div>
                <div style="font-size:13px; color:rgba(255,255,255,.35); line-height:1.5;">
                    {{ $booking->description }}
                </div>
            </div>

        </div>

        {{-- Link + actions --}}
        <div style="padding:24px 28px; display:flex; flex-direction:column; gap:20px;">

            {{-- Copy row --}}
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:var(--ink); letter-spacing:-.01em; margin-bottom:8px;">
                    Payment Link
                </label>
                <div style="display:flex; gap:8px; align-items:stretch;">
                    <input
                        id="paymentLink"
                        type="text"
                        readonly
                        value="{{ route('payment.link', $booking->payment_link_token) }}"
                        style="
                            flex:1;
                            min-width:0;
                            padding:9px 12px;
                            background:var(--surface);
                            border:1px solid var(--border);
                            border-radius:7px;
                            font-family:'IBM Plex Mono', monospace;
                            font-size:12px;
                            color:var(--ink-mid);
                            outline:none;
                            cursor:text;
                            white-space:nowrap;
                            overflow:hidden;
                            text-overflow:ellipsis;
                        "
                    />
                    <button
                        onclick="copyPaymentLink(this)"
                        style="
                            flex-shrink:0;
                            padding:9px 16px;
                            background:var(--ink);
                            color:#fff;
                            border:none;
                            border-radius:7px;
                            font-family:'Sora', sans-serif;
                            font-size:13px;
                            font-weight:600;
                            cursor:pointer;
                            letter-spacing:-.01em;
                            transition:background .15s;
                            white-space:nowrap;
                        "
                        onmouseover="this.style.background='#1a1f2e'"
                        onmouseout="this.style.background='var(--ink)'"
                    >
                        Copy
                    </button>
                </div>
                <div id="copy-confirm" style="display:none; font-size:12px; color:var(--green); margin-top:6px; font-weight:500;">
                    Link copied to clipboard
                </div>
            </div>

            {{-- Divider --}}
            <div style="border-top:1px solid var(--border);"></div>

            {{-- Action buttons --}}
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a
                    href="{{ route('payment.link', $booking->payment_link_token) }}"
                    target="_blank"
                    class="btn btn-primary"
                >
                    Open Payment Page
                </a>
                <a
                    href="{{ $whatsappUrl }}"
                    target="_blank"
                    class="btn btn-outline"
                    style="display:inline-flex; align-items:center; gap:7px;"
                >
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" style="opacity:.7;flex-shrink:0;">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.554 4.112 1.523 5.84L.057 23.272a.75.75 0 0 0 .919.899l5.516-1.445A11.943 11.943 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.956 9.956 0 0 1-5.14-1.424l-.368-.218-3.813 1 1.024-3.741-.238-.382A9.955 9.955 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                    </svg>
                    Share via WhatsApp
                </a>
            </div>

        </div>

    </div>

    {{-- Back link --}}
    <div style="margin-top:16px;">
        <a href="{{ route('dashboard.bookings') }}" style="font-size:13px; color:var(--muted); text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:color .15s;" onmouseover="this.style.color='var(--ink)'" onmouseout="this.style.color='var(--muted)'">
            ← Back to Bookings
        </a>
    </div>

</div>

<script>
function copyPaymentLink(btn) {
    const input = document.getElementById('paymentLink');
    navigator.clipboard.writeText(input.value).then(() => {
        const confirm = document.getElementById('copy-confirm');
        const original = btn.textContent;
        btn.textContent = 'Copied!';
        btn.style.background = 'var(--green)';
        confirm.style.display = 'block';
        setTimeout(() => {
            btn.textContent = original;
            btn.style.background = 'var(--ink)';
            confirm.style.display = 'none';
        }, 2000);
    });
}
</script>

@endsection
