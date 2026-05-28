@extends('layouts.app')
@section('title', 'New Booking')
@section('page-title', 'New Booking')

@section('content')

@php $mode = $activeService->booking_mode; @endphp

<div style="max-width:640px;">
    <form
        method="POST"
        action="{{ route('bookings.store') }}"
        id="booking-form"
        style="max-width:640px;"
    >
        @csrf
    @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:8px;padding:12px 16px;font-size:14px;margin-bottom:20px;">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    {{-- ── Customer Details ──────────────────────────────────────────────── --}}
    <div class="card" style="margin-bottom:16px;">
        <h3 style="font-size:14px;margin-bottom:16px;">Customer Details</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
            <div class="form-group" style="margin-bottom:0;">
                <label>Full Name</label>
                <input type="text" name="customer_name" class="form-control" placeholder="Emeka Obi" required />
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label>Email</label>
                <input type="email" name="customer_email"  class="form-control" placeholder="customer@email.com" required />
            </div>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label>Phone <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
            <input type="tel" name="customer_phone" class="form-control" placeholder="08012345678" style="max-width:240px;" />
        </div>
    </div>

    {{-- ── Booking Details ────────────────────────────────────────────────── --}}
    <div class="card" style="margin-bottom:16px;">
        <h3 style="font-size:14px;margin-bottom:16px;">Booking Details</h3>

        @if($categories->isNotEmpty())
        <div class="form-group">
            <label>Category <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
            <select name="category_id" id="f-category" class="form-control" onchange="onCategoryChange(this)">
                <option value="">— Custom / no category —</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}"
                        data-price="{{ $cat->price }}"
                        data-name="{{ $cat->name }}"
                        data-mode="{{ $cat->booking_mode }}">
                        {{ $cat->name }} — ₦{{ number_format($cat->price, 0) }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" id="f-description" class="form-control" placeholder="What is this booking for?" required />
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label>Amount (₦)</label>
            <input type="number"  name="amount" id="f-amount" class="form-control" placeholder="e.g. 50000" min="1" step="0.01" style="max-width:220px;" required />
            <span style="font-size:11px;color:var(--muted);">Enter the agreed amount in Naira.</span>
        </div>
    </div>

    {{-- ── Mode-specific fields ───────────────────────────────────────────── --}}
    @if($mode === 'reservation')
    <div class="card" style="margin-bottom:16px;">
        <h3 style="font-size:14px;margin-bottom:16px;">
            {{ $developer->reservation_unit === 'day' ? 'Dates' : 'Check-in / Check-out' }}
        </h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="form-group" style="margin-bottom:0;">
                <label>{{ $developer->reservation_unit === 'day' ? 'Start Date' : 'Check-in' }}</label>
                <input type="date" name="check_in" id="f-checkin" class="form-control" required />
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label>{{ $developer->reservation_unit === 'day' ? 'End Date' : 'Check-out' }}</label>
                <input type="date" name="check_out" id="f-checkout" class="form-control" required />
            </div>
        </div>
    </div>
    @endif

    @if($mode === 'appointment')
    <div class="card" style="margin-bottom:16px;">
        <h3 style="font-size:14px;margin-bottom:16px;">Appointment</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="form-group" style="margin-bottom:0;">
                <label>Preferred Date <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                <input type="date" name="preferred_date" id="f-pref-date" class="form-control" />
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label>Preferred Time <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                <input type="time" name="preferred_time" id="f-pref-time" class="form-control" />
            </div>
        </div>
    </div>
    @endif

    @if($mode === 'ticket')
    <div class="card" style="margin-bottom:16px;">
        <h3 style="font-size:14px;margin-bottom:16px;">Tickets</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="form-group" style="margin-bottom:0;">
                <label>Adults</label>
                <input type="number" name="adults" id="f-adults" class="form-control" value="1" min="1" />
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label>Children</label>
                <input type="number" name="children" id="f-children" class="form-control" value="0" min="0" />
            </div>
        </div>
    </div>
    @endif

    {{-- ── Payment Method ─────────────────────────────────────────────────── --}}
    <div class="card" style="margin-bottom:20px;">
        <h3 style="font-size:14px;margin-bottom:14px;">How will the customer pay?</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">

            <label style="display:flex;align-items:flex-start;gap:12px;padding:14px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;transition:border-color .15s;" id="pm-label-transfer">
                <input type="radio" name="payment_method" value="online" checked
                    style="margin-top:2px;accent-color:#4f46e5;width:16px;height:16px;flex-shrink:0;"
                    onchange="highlightPayMethod(this)" />
                <div>
                    <div style="font-size:14px;font-weight:700;color:var(--ink);margin-bottom:2px;">🏦 Bank Transfer</div>
                    <div style="font-size:12px;color:var(--muted);line-height:1.5;">
                        We generate a unique account number the customer pays to. Auto-confirmed when payment lands.
                    </div>
                </div>
            </label>

            <label style="display:flex;align-items:flex-start;gap:12px;padding:14px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;transition:border-color .15s;" id="pm-label-card">
                <input type="radio" name="payment_method" value="cash"
                    style="margin-top:2px;accent-color:#4f46e5;width:16px;height:16px;flex-shrink:0;"
                    onchange="highlightPayMethod(this)" />
                <div>
                    <div style="font-size:14px;font-weight:700;color:var(--ink);margin-bottom:2px;">💳 Cash</div>
                    <div style="font-size:12px;color:var(--muted);line-height:1.5;">
                        Customer Pays in full cash of the agreed price
                    </div>
                </div>
            </label>

        </div>
    </div>

    <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary btn-sm">
            Generate Payment Details
        </button>
        <a href="{{ route('dashboard.bookings') }}" class="btn btn-outline btn-sm">Cancel</a>
    </div>
</form>
</div>


@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────────────────────
let vaData     = null;
let cardData   = null;
let timerInt   = null;
let bookingRef = null;

// ── Payment method highlight ──────────────────────────────────────────────────
function highlightPayMethod(input) {
    ['transfer','card'].forEach(v => {
        const el = document.getElementById('pm-label-' + v);
        if (el) {
            el.style.borderColor = 'var(--border)';
            el.style.background  = '#fff';
        }
    });
    const sel = document.getElementById('pm-label-' + input.value);
    if (sel) {
        sel.style.borderColor = '#4f46e5';
        sel.style.background  = '#f5f3ff';
    }
}

// Highlight default on load
document.addEventListener('DOMContentLoaded', () => {
    const checked = document.querySelector('input[name="payment_method"]:checked');
    if (checked) highlightPayMethod(checked);
});

// ── Category change ───────────────────────────────────────────────────────────
function onCategoryChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (opt.dataset.price) {
        document.getElementById('f-amount').value      = parseInt(opt.dataset.price);
        document.getElementById('f-description').value = opt.dataset.name;
    }
}

</script>
@endpush

@endsection
