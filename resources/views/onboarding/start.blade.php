@extends('layouts.auth')

@section('title', 'Get Started — BookInStack')

@section('content')

<div style="text-align:center;margin-bottom:32px;">
    <a href="/" style="text-decoration:none;">
        <span style="font-size:22px;font-weight:800;color:#0d0d14;letter-spacing:-.4px;">
            BookIn<span style="color:#4f46e5;">Stack</span>
        </span>
    </a>
</div>

{{-- Progress --}}
@include('onboarding._progress', ['step' => 1])

<h2 style="font-size:22px;font-weight:700;color:#0d0d14;margin-bottom:6px;letter-spacing:-.4px;">
    Who are you setting up for?
</h2>
<p style="font-size:14px;color:#64748b;margin-bottom:28px;line-height:1.6;">
    Tell us a bit about yourself so we can verify your identity and set up your payment account.
</p>

@if(session('error'))
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:20px;">
        ⚠️ {{ session('error') }}
    </div>
@endif

<form method="POST" action="{{ route('onboarding.type') }}">
    @csrf

    <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:28px;">

        {{-- Individual --}}
        <label style="display:flex;align-items:flex-start;gap:14px;padding:18px;border:1.5px solid var(--border);border-radius:12px;cursor:pointer;transition:border-color .15s;" id="label-individual">
            <input type="radio" name="customer_type" value="individual"
                style="margin-top:3px;accent-color:#4f46e5;width:17px;height:17px;flex-shrink:0;"
                onchange="highlightCard(this)"
                {{ old('customer_type') === 'individual' ? 'checked' : '' }}
            />
            <div>
                <div style="font-size:15px;font-weight:700;color:#0d0d14;margin-bottom:3px;">
                    👤 I'm an individual
                </div>
                <div style="font-size:13px;color:#64748b;line-height:1.5;">
                    Freelancers, consultants, sole traders. Verified using your BVN.
                </div>
            </div>
        </label>

        {{-- Business --}}
        {{-- <label style="display:flex;align-items:flex-start;gap:14px;padding:18px;border:1.5px solid var(--border);border-radius:12px;cursor:pointer;transition:border-color .15s;" id="label-business">
            <input type="radio" name="customer_type" value="business"
                style="margin-top:3px;accent-color:#4f46e5;width:17px;height:17px;flex-shrink:0;"
                onchange="highlightCard(this)"
                {{ old('customer_type') === 'business' ? 'checked' : '' }}
            />
            <div>
                <div style="font-size:15px;font-weight:700;color:#0d0d14;margin-bottom:3px;">
                    🏢 I represent a business
                </div>
                <div style="font-size:13px;color:#64748b;line-height:1.5;">
                    SMEs, hotels, event centres, concert organisers. Verified using CAC documents.
                </div>
            </div>
        </label> --}}

    </div>

    @error('customer_type')
        <div style="color:#ef4444;font-size:12px;margin-top:-16px;margin-bottom:16px;">{{ $message }}</div>
    @enderror

    <button type="submit" style="
        width:100%;padding:13px;background:#4f46e5;color:#fff;
        border:none;border-radius:10px;font-size:15px;font-weight:600;
        cursor:pointer;transition:background .15s;
    ">
        Continue →
    </button>
</form>

@push('scripts')
<script>
function highlightCard(input) {
    document.querySelectorAll('[id^="label-"]').forEach(el => {
        el.style.borderColor = 'var(--border)';
        el.style.background  = '#fff';
    });
    const label = document.getElementById('label-' + input.value);
    if (label) {
        label.style.borderColor = '#4f46e5';
        label.style.background  = '#f5f3ff';
    }
}

// Restore on page load if old() value present
document.querySelectorAll('input[name="customer_type"]').forEach(i => {
    if (i.checked) highlightCard(i);
});
</script>
@endpush

@endsection
