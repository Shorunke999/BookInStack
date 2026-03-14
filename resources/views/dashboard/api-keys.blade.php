@extends('layouts.app')

@section('title', 'API Keys')
@section('page-title', 'API Keys')

@section('content')

@if(! $developer->nin_verified)
    {{-- ── NIN Verification Form ───────────────────────────────────────────── --}}
    <div style="max-width:560px;">
        <div class="card">
            <h3 style="font-size:16px; margin:0 0 6px;">Verify Your Identity</h3>
            <p style="color:var(--muted); font-size:14px; margin:0 0 24px;">
                Your NIN is required for KYC compliance. After verification, a Paystack subaccount will be
                created automatically and your live API key will be issued instantly.
            </p>

            @if($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('nin.verify') }}">
                @csrf

                <div class="form-group">
                    <label for="nin">National Identification Number (NIN)</label>
                    <input
                        type="text"
                        id="nin"
                        name="nin"
                        class="form-control"
                        value="{{ old('nin') }}"
                        placeholder="12345678901"
                        maxlength="11"
                        pattern="\d{11}"
                        required
                    />
                    <div style="font-size:12px; color:var(--muted); margin-top:4px;">Must be exactly 11 digits.</div>
                    @error('nin')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="bank_code">Bank</label>
                    <select
                        id="bank_code"
                        name="bank_code"
                        class="form-control"
                        required
                    >
                        <option value="">Select your bank</option>
                        @foreach($banks as $bank)
                            <option value="{{ $bank['code'] }}" {{ old('bank_code') === $bank['code'] ? 'selected' : '' }}>
                                {{ $bank['name'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('bank_code')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="account_number">Account Number</label>
                    <input
                        type="text"
                        id="account_number"
                        name="account_number"
                        class="form-control"
                        value="{{ old('account_number') }}"
                        placeholder="0123456789"
                        maxlength="10"
                        pattern="\d{10}"
                        required
                    />
                    {{-- Live account name lookup --}}
                    <div id="account-name-display" style="font-size:13px; color:var(--green); min-height:20px; margin-top:6px;"></div>
                    @error('account_number')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:8px;">
                    Verify &amp; Activate Account
                </button>
            </form>
        </div>
    </div>

@else
    {{-- ── Active API Key ──────────────────────────────────────────────────── --}}
    <div style="max-width:600px; display:flex; flex-direction:column; gap:20px;">

        {{-- Key card --}}
        <div class="card">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                <div style="width:10px; height:10px; background:var(--green); border-radius:50%;"></div>
                <h3 style="font-size:16px; margin:0;">Account Active</h3>
            </div>
            <p style="color:var(--muted); font-size:14px; margin:0 0 16px;">
                Your public key is live and ready to use. Include it in <code>Booking.init()</code> on your website.
            </p>

            <div class="stat-label" style="margin-bottom:8px;">Public API Key</div>
            <div class="key-box">
                <div class="key-value" id="api-key-value">{{ $developer->public_key }}</div>
                <button class="btn btn-sm btn-outline" onclick="copyKey()">Copy</button>
            </div>
            <div style="font-size:12px; color:var(--muted); margin-top:8px;">
                This key is safe to use in client-side code. Never share your secret key.
            </div>
        </div>

        {{-- Subaccount info --}}
        <div class="card">
            <h3 style="font-size:15px; margin:0 0 12px;">Paystack Subaccount</h3>
            <table>
                <tr>
                    <td style="color:var(--muted); font-size:13px; border:none; padding:6px 0;">Subaccount Code</td>
                    <td class="mono" style="border:none; padding:6px 0;">{{ $developer->paystack_subaccount_code }}</td>
                </tr>
                <tr>
                    <td style="color:var(--muted); font-size:13px; border:none; padding:6px 0;">Settlement Split</td>
                    <td style="border:none; padding:6px 0;">
                        <span class="badge badge-green">95% to you</span>
                        &nbsp;
                        <span class="badge badge-gray">5% platform</span>
                    </td>
                </tr>
                <tr>
                    <td style="color:var(--muted); font-size:13px; border:none; padding:6px 0;">Status</td>
                    <td style="border:none; padding:6px 0;">@include('components.status-badge', ['status' => $developer->status])</td>
                </tr>
            </table>
        </div>

        {{-- Danger zone --}}
        <div class="card" style="border-color:#fee2e2;">
            <h3 style="font-size:15px; margin:0 0 8px; color:var(--red);">Danger Zone</h3>
            <p style="font-size:14px; color:var(--muted); margin:0 0 16px;">
                Regenerating your key will <strong>immediately invalidate</strong> the current key.
                Any live integrations using the old key will stop working.
            </p>
            <form method="POST" action="{{ route('api-keys.regenerate') }}"
                  onsubmit="return confirm('Are you sure? Your current key will stop working immediately.')">
                @csrf
                @method('POST')
                <button type="submit" class="btn btn-danger btn-sm">⚠ Regenerate Key</button>
            </form>
        </div>

    </div>
@endif

@endsection

@push('scripts')
<script>
    {{-- Live bank account name lookup --}}
    const accountInput = document.getElementById('account_number');
    const bankSelect   = document.getElementById('bank_code');
    const nameDisplay  = document.getElementById('account-name-display');

    let lookupTimer;

    function triggerLookup() {
        clearTimeout(lookupTimer);
        const accNo    = accountInput?.value || '';
        const bankCode = bankSelect?.value   || '';
        if (!nameDisplay) return;

        nameDisplay.style.color = 'var(--muted)';
        nameDisplay.textContent = '';

        if (accNo.length !== 10 || !bankCode) return;

        nameDisplay.textContent = 'Looking up account…';

        lookupTimer = setTimeout(async () => {
            try {
                const res = await fetch('{{ route("api.verify-account") }}', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ account_number: accNo, bank_code: bankCode }),
                });

                const data = await res.json();

                if (res.ok && data.account?.account_name) {
                    nameDisplay.style.color = 'var(--green)';
                    nameDisplay.textContent = '✓ ' + data.account.account_name;
                } else {
                    nameDisplay.style.color = 'var(--red)';
                    nameDisplay.textContent = 'Account not found';
                }
            } catch {
                nameDisplay.style.color = 'var(--red)';
                nameDisplay.textContent = 'Could not verify account';
            }
        }, 700);
    }

    accountInput?.addEventListener('input', triggerLookup);
    bankSelect?.addEventListener('change', triggerLookup);

    {{-- Copy key to clipboard --}}
    function copyKey() {
        const key = document.getElementById('api-key-value')?.textContent?.trim();
        if (!key) return;
        navigator.clipboard.writeText(key).then(() => {
            const btn = event.target;
            btn.textContent = 'Copied!';
            setTimeout(() => btn.textContent = 'Copy', 2000);
        });
    }
</script>
@endpush
