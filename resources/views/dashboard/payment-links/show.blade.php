@extends('layouts.app')
@section('title', 'Payment Link')
@section('page-title', 'Payment Link')

@section('content')

@if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;border-radius:8px;padding:12px 16px;font-size:14px;margin-bottom:20px;">✓ {{ session('success') }}</div>
@endif

<div style="max-width:640px;">

    {{-- Status banner --}}
    @php
        $banners = [
            'pending'   => ['bg'=>'#fffbeb','border'=>'#fde68a','color'=>'#92400e','icon'=>'⏳','text'=>'Awaiting payment'],
            'paid'      => ['bg'=>'#f0fdf4','border'=>'#bbf7d0','color'=>'#166534','icon'=>'✅','text'=>'Payment received'],
            'expired'   => ['bg'=>'#f3f4f6','border'=>'#e5e7eb','color'=>'#6b7280','icon'=>'⏰','text'=>'Link expired'],
            'cancelled' => ['bg'=>'#fef2f2','border'=>'#fecaca','color'=>'#991b1b','icon'=>'✕','text'=>'Link cancelled'],
        ];
        $b = $banners[$link->status] ?? $banners['pending'];
    @endphp
    <div style="background:{{ $b['bg'] }};border:1px solid {{ $b['border'] }};color:{{ $b['color'] }};border-radius:8px;padding:14px 18px;font-size:14px;font-weight:600;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
        {{ $b['icon'] }} {{ $b['text'] }}
    </div>

    {{-- Link details --}}
    <div class="card" style="margin-bottom:16px;">
        <h3 style="font-size:14px;margin-bottom:16px;">Link Details</h3>

        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach([
                ['Customer', $link->customer_name . ' · ' . $link->customer_email],
                ['Description', $link->category?->name ?? $link->description],
                ['Amount', $link->formattedAmount()],
                ['Expires', $link->expires_at ? $link->expires_at->format('d M Y, g:i A') : 'No expiry'],
                ['Created', $link->created_at->format('d M Y, g:i A')],
            ] as [$label, $value])
                <div style="display:flex;gap:16px;font-size:13px;border-bottom:1px solid var(--border);padding-bottom:10px;">
                    <span style="color:var(--muted);min-width:100px;flex-shrink:0;">{{ $label }}</span>
                    <span style="font-weight:500;">{{ $value }}</span>
                </div>
            @endforeach

            @if($link->note)
                <div style="display:flex;gap:16px;font-size:13px;">
                    <span style="color:var(--muted);min-width:100px;flex-shrink:0;">Private note</span>
                    <span style="color:var(--muted);font-style:italic;">{{ $link->note }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Share section --}}
    @if($link->status === 'pending')
    <div class="card" style="margin-bottom:16px;">
        <h3 style="font-size:14px;margin-bottom:14px;">Share with Customer</h3>

        {{-- Payment URL --}}
        <div class="form-group" style="margin-bottom:14px;">
            <label style="font-size:12px;">Payment Link</label>
            <div style="display:flex;gap:8px;">
                <input type="text" id="link-url" value="{{ $link->publicUrl() }}"
                       class="form-control" readonly style="font-family:monospace;font-size:12px;flex:1;" />
                <button type="button" onclick="copyLink()" class="btn btn-outline btn-sm" id="copy-btn">
                    Copy
                </button>
            </div>
        </div>

        {{-- Share buttons --}}
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="{{ $whatsappUrl }}" target="_blank"
               style="display:inline-flex;align-items:center;gap:8px;background:#25d366;color:#fff;padding:10px 20px;border-radius:6px;font-size:13px;font-weight:700;text-decoration:none;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                Send via WhatsApp
            </a>

            <a href="mailto:{{ $link->customer_email }}?subject=Payment%20Link%20%E2%80%94%20{{ rawurlencode($link->description) }}&body={{ rawurlencode($link->whatsappText()) }}"
               style="display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--border);color:var(--ink);padding:10px 20px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;">
                📧 Send via Email
            </a>
        </div>
    </div>

    {{-- Cancel --}}
    <form method="POST" action="{{ route('payment-links.cancel', $link->token) }}"
          onsubmit="return confirm('Cancel this payment link?')" style="margin:0;">
        @csrf
        <button type="submit" class="btn btn-outline btn-sm" style="color:#ef4444;border-color:#fecaca;">
            Cancel Link
        </button>
    </form>
    @endif

    <div style="margin-top:16px;">
        <a href="{{ route('payment-links.index') }}" style="font-size:13px;color:var(--muted);">← Back to Payment Links</a>
    </div>
</div>

@push('scripts')
<script>
function copyLink() {
    const input = document.getElementById('link-url');
    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = document.getElementById('copy-btn');
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = 'Copy', 2000);
    });
}
</script>
@endpush

@endsection