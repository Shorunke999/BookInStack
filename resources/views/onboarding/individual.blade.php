@extends('layouts.auth')

@section('title', 'Identity Verification — BookInStack')

@section('content')

<div style="text-align:center;margin-bottom:32px;">
    <a href="/" style="text-decoration:none;">
        <span style="font-size:22px;font-weight:800;color:#0d0d14;letter-spacing:-.4px;">
            BookIn<span style="color:#4f46e5;">Stack</span>
        </span>
    </a>
</div>

@include('onboarding._progress', ['step' => 2])

<h2 style="font-size:22px;font-weight:700;color:#0d0d14;margin-bottom:6px;letter-spacing:-.4px;">
    Verify your identity
</h2>
<p style="font-size:14px;color:#64748b;margin-bottom:24px;line-height:1.6;">
    We use your BVN to confirm your identity and set up your payment account. Your BVN is never shared.
</p>

@if(session('error'))
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:20px;">
        ⚠️ {{ session('error') }}
    </div>
@endif

<form method="POST" action="{{ route('onboarding.individual.submit') }}">
    @csrf

    {{-- Section: Personal info --}}
    <p style="font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;margin-bottom:12px;">
        Personal Information
    </p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">First Name</label>
            <input type="text" name="first_name" value="{{ old('first_name') }}"
                class="form-control" placeholder="Emeka" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
            @error('first_name')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Last Name</label>
            <input type="text" name="last_name" value="{{ old('last_name') }}"
                class="form-control" placeholder="Okafor" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
            @error('last_name')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Date of Birth</label>
            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                class="form-control" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
            @error('date_of_birth')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Gender</label>
            <select name="gender" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;color:#0d0d14;">
                <option value="">Select</option>
                <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
            </select>
            @error('gender')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
    </div>

    <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Phone Number</label>
        <input type="tel" name="phone_number" value="{{ old('phone_number') }}"
            class="form-control" placeholder="08012345678" required
            style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
        @error('phone_number')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
    </div>

    {{-- BVN --}}
    <div style="margin-bottom:20px;padding:16px;background:#f5f3ff;border:1.5px solid #c7d2fe;border-radius:10px;">
        <label style="display:block;font-size:13px;font-weight:700;color:#3730a3;margin-bottom:5px;">
            🔒 BVN (Bank Verification Number)
        </label>
        <input type="text" name="bvn" value="{{ old('bvn') }}"
            class="form-control" placeholder="12345678901" maxlength="11" required
            style="width:100%;padding:10px 12px;border:1.5px solid #c7d2fe;border-radius:9px;font-size:14px;outline:none;background:#fff;letter-spacing:.05em;" />
        <p style="font-size:12px;color:#6366f1;margin-top:6px;">
            Your 11-digit BVN. Dial *565*0# on any network to retrieve it.
        </p>
        @error('bvn')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
    </div>

    {{-- Section: Address --}}
    <p style="font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;margin-bottom:12px;">
        Address
    </p>

    <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Street Address</label>
        <input type="text" name="address_line1" value="{{ old('address_line1') }}"
            class="form-control" placeholder="12 Broad Street" required
            style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
        @error('address_line1')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">City</label>
            <input type="text" name="city" value="{{ old('city') }}"
                placeholder="Lagos" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
            @error('city')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">State</label>
            <select name="state" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;color:#0d0d14;">
                <option value="">Select state</option>
                @foreach(['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'] as $st)
                    <option value="{{ $st }}" {{ old('state') === $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
            @error('state')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
    </div>

    {{-- Section: Settlement Bank --}}
    <p style="font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;margin-bottom:8px;">
        Payout Bank Account
    </p>
    <p style="font-size:13px;color:#64748b;margin-bottom:12px;line-height:1.5;">
        When a customer pays for a booking, we'll automatically transfer your earnings here.
    </p>

    <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Bank</label>
         @if ($provider == 'paystack')
              <select name="bank_code" id="bank-select" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;color:#0d0d14;">
                <option value="">Select bank</option>
                {{-- Populated from JS or blade --}}
                    @foreach($banks ?? [] as $bank)
                        <option value="{{ $bank['code']}}"
                            {{ old('code') === $bank['code'] ? 'selected' : '' }}>
                            {{ $bank['name'] }}
                        </option>
                    @endforeach

                </select>

                @error('code')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        @else
            <select name="settlement_bank_nip_code" id="bank-select" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;color:#0d0d14;">
                <option value="">Select bank</option>
                {{-- Populated from JS or blade --}}
                    @foreach($banks ?? [] as $bank)
                        <option value="{{ $bank['attributes']['nipCode'] }}"
                            {{ old('settlement_bank_nip_code') === $bank['attributes']['nipCode'] ? 'selected' : '' }}>
                            {{ $bank['attributes']['name'] }}
                        </option>
                    @endforeach

            </select>
            @error('settlement_bank_nip_code')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        @endif
    </div>

    <div style="margin-bottom:24px;">
        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Account Number</label>
        <input type="text" name="settlement_account_number" value="{{ old('settlement_account_number') }}"
            placeholder="0123456789" maxlength="10" required
            style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;letter-spacing:.05em;" />
        <div id="account-name-preview" style="font-size:12px;color:#10b981;margin-top:5px;font-weight:600;min-height:18px;"></div>
        @error('settlement_account_number')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
    </div>

    <button type="submit" id="submit-btn" style="
        width:100%;padding:13px;background:#4f46e5;color:#fff;
        border:none;border-radius:10px;font-size:15px;font-weight:600;
        cursor:pointer;transition:background .15s;
    ">
        Submit for Verification →
    </button>

    <p style="text-align:center;font-size:12px;color:#94a3b8;margin-top:16px;line-height:1.6;">
        🔐 Your data is encrypted and processed securely via Anchor MFB.
    </p>

</form>

{{-- @push('scripts')
<script>
// BVN digits only
document.querySelector('[name="bvn"]').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 11);
});

document.querySelector('[name="settlement_account_number"]').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 10);
    if (this.value.length === 10) resolveAccount();
    else document.getElementById('account-name-preview').textContent = '';
});

async function resolveAccount() {
    const acct = document.querySelector('[name="settlement_account_number"]').value;
    const bank = document.getElementById('bank-select').value;
    const preview = document.getElementById('account-name-preview');

    if (!bank) { preview.textContent = ''; return; }
    preview.textContent = 'Verifying...';
    preview.style.color = '#94a3b8';

    try {
        const res = await fetch(`/onboarding/resolve-account?bank=${bank}&account=${acct}`);
        const json = await res.json();
        if (json.account_name) {
            preview.textContent = '✓ ' + json.account_name;
            preview.style.color = '#10b981';
        } else {
            preview.textContent = 'Account not found';
            preview.style.color = '#ef4444';
        }
    } catch {
        preview.textContent = 'Could not verify account';
        preview.style.color = '#ef4444';
    }
}

document.getElementById('bank-select').addEventListener('change', () => {
    const acct = document.querySelector('[name="settlement_account_number"]').value;
    if (acct.length === 10) resolveAccount();
});
</script>
@endpush --}}

@endsection
