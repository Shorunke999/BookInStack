@extends('layouts.app')
@section('title', 'New Payment Link')
@section('page-title', 'New Payment Link')

@section('content')

<div style="max-width:600px;">

    @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:8px;padding:12px 16px;font-size:14px;margin-bottom:20px;">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('payment-links.store') }}">
        @csrf

        {{-- Customer details --}}
        <div class="card" style="margin-bottom:16px;">
            <h3 style="margin-bottom:16px;font-size:14px;">Customer Details</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Full Name</label>
                    <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}" placeholder="e.g. Emeka Obi" required />
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Email</label>
                    <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email') }}" placeholder="customer@email.com" required />
                </div>
            </div>
            <div class="form-group" style="margin-top:14px;margin-bottom:0;">
                <label>Phone <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone') }}" placeholder="08012345678" style="max-width:240px;" />
            </div>
        </div>

        {{-- Booking details --}}
        <div class="card" style="margin-bottom:16px;">
            <h3 style="margin-bottom:16px;font-size:14px;">Booking Details</h3>

            @if($categories->isNotEmpty())
                <div class="form-group">
                    <label>Category <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                    <select name="category_id" class="form-control" onchange="onCategoryChange(this)">
                        <option value="">— No category / custom —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" data-price="{{ $cat->price }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }} — {{ $cat->formattedPrice() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" id="f-description" class="form-control"
                       value="{{ old('description') }}" placeholder="What is this payment for?" required />
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label>Agreed Amount (₦ Naira)</label>
                <div style="display:flex;align-items:center;gap:8px;">
                    <input type="number" name="amount" id="f-amount" class="form-control"
                           value="{{ old('amount') }}" placeholder="e.g. 150000" min="1" step="0.01"
                           style="max-width:220px;" required />
                    <span style="font-size:12px;color:var(--muted);">Enter in Naira</span>
                </div>
                <span style="font-size:11px;color:var(--muted);">This is the negotiated price — enter what you and the customer agreed on.</span>
            </div>
        </div>

        {{-- Options --}}
        <div class="card" style="margin-bottom:16px;">
            <h3 style="margin-bottom:16px;font-size:14px;">Options</h3>

            <div class="form-group">
                <label>Private Note <span style="font-weight:400;color:var(--muted);">(only you see this)</span></label>
                <input type="text" name="note" class="form-control" value="{{ old('note') }}"
                       placeholder="e.g. Negotiated down from ₦200,000 — couple's package" />
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label>Link Expiry <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                <div style="display:flex;align-items:center;gap:8px;">
                    <input type="number" name="expires_hours" class="form-control"
                           value="{{ old('expires_hours', 48) }}" min="1" max="720"
                           style="max-width:100px;" />
                    <span style="font-size:13px;color:var(--muted);">hours from now</span>
                </div>
                <span style="font-size:11px;color:var(--muted);">Leave as 48 hours (2 days) if unsure. Max 720 hours (30 days).</span>
            </div>
        </div>

        <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary btn-sm">Generate Payment Link</button>
            <a href="{{ route('payment-links.index') }}" class="btn btn-outline btn-sm">Cancel</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function onCategoryChange(sel) {
    const opt   = sel.options[sel.selectedIndex];
    const price = opt.dataset.price;
    const name  = opt.text.split(' — ')[0];

    if (price) {
        document.getElementById('f-amount').value      = (parseInt(price) ).toFixed(2);
        document.getElementById('f-description').value = name;
    }
}
</script>
@endpush

@endsection