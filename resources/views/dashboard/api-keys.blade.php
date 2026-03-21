@extends('layouts.app')
@section('title', 'API Keys')
@section('page-title', 'API Keys')

@section('content')

@if(!$developer->nin_verified)

    {{-- ── nin Verification ─────────────────────────────────────────────────── --}}
    <div style="max-width:520px;">
        <div class="card">
            <h3 style="font-size:16px;margin:0 0 6px;">Verify Your Business</h3>
            <p style="color:var(--muted);font-size:14px;margin:0 0 24px;line-height:1.6;">
                Submit your nin and bank account to activate your account.
                We'll create your Paystack subaccount and issue your live key instantly.
            </p>

            <form method="POST" action="{{ route('nin.verify') }}">
                @csrf
                <div class="form-group">
                    <label>nin <span style="font-size:12px;color:var(--muted);font-weight:400;">(Bank Verification Number)</span></label>
                    <input type="text" name="nin" class="form-control"
                           value="{{ old('nin') }}" placeholder="12345678901"
                           maxlength="11" pattern="\d{11}" required />
                    <div style="font-size:12px;color:var(--muted);margin-top:4px;">11 digits — found on your bank app or USSD *565*0#</div>
                </div>

                <div class="form-group">
                    <label>Bank</label>
                    <select name="bank_code" class="form-control" required id="bank-select" onchange="triggerLookup()">
                        <option value="">Select your bank</option>
                        @foreach($banks ?? [] as $bank)
                            <option value="{{ $bank['code'] }}" {{ old('bank_code')===$bank['code']?'selected':'' }}>
                                {{ $bank['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Account Number</label>
                    <input type="text" name="account_number" id="account-input" class="form-control"
                           value="{{ old('account_number') }}" placeholder="0123456789"
                           maxlength="10" pattern="\d{10}" required
                           oninput="triggerLookup()" />
                    <div id="account-name" style="font-size:13px;min-height:20px;margin-top:6px;font-weight:600;"></div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">
                    Verify &amp; Activate Account
                </button>
            </form>
        </div>
    </div>

@else

    {{-- ── Active account ───────────────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:16px;max-width:580px;">

        {{-- Status banner --}}
        <div style="display:flex;align-items:center;gap:10px;padding:14px 18px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;">
            <div style="width:10px;height:10px;background:#10b981;border-radius:50%;flex-shrink:0;"></div>
            <div>
                <div style="font-weight:700;font-size:14px;color:#166534;">Account Active</div>
                <div style="font-size:12px;color:#166534;opacity:.8;">Your API key is live and ready to use on your website.</div>
            </div>
        </div>

        {{-- Public key card --}}
        <div class="card">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">
                Public API Key
            </div>
            <div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-bottom:10px;">
                <div id="api-key-value" style="font-family:'DM Mono',monospace;font-size:12px;color:var(--ink);word-break:break-all;line-height:1.6;">
                    {{ $developer->public_key }}
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="btn btn-outline btn-sm" onclick="copyKey()" id="copy-btn">Copy Key</button>
                <button class="btn btn-outline btn-sm" onclick="toggleKey()" id="toggle-btn" style="color:var(--muted);">Show</button>
            </div>
            <div style="font-size:12px;color:var(--muted);margin-top:10px;line-height:1.5;">
                Safe to use in client-side code. Never share your <strong>secret key</strong>.
            </div>
        </div>

        {{-- Subaccount info --}}
        <div class="card">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">
                Paystack Subaccount
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:8px 0;border-bottom:1px solid var(--border);flex-wrap:wrap;">
                    <span style="font-size:13px;color:var(--muted);flex-shrink:0;">Subaccount Code</span>
                    <span style="font-family:monospace;font-size:12px;word-break:break-all;text-align:right;">{{ $developer->paystack_subaccount_code ?? '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid var(--border);flex-wrap:wrap;">
                    <span style="font-size:13px;color:var(--muted);">Settlement Split</span>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px;background:#f0fdf4;color:#15803d;">{{100 - $developer->platform_fee_percent}}% to you</span>
                        <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px;background:#f3f4f6;color:#6b7280;">{{ $developer->platform_fee_percent }}% platform</span>
                    </div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:8px 0;flex-wrap:wrap;">
                    <span style="font-size:13px;color:var(--muted);">Account Status</span>
                    @php $sc=['active'=>['#15803d','#f0fdf4'],'pending'=>['#d97706','#fffbeb'],'suspended'=>['#ef4444','#fef2f2']]; [$c,$bg]=$sc[$developer->status]??['#6b7280','#f3f4f6']; @endphp
                    <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px;background:{{ $bg }};color:{{ $c }};">{{ ucfirst($developer->status) }}</span>
                </div>
            </div>
        </div>

        {{-- Quick embed --}}
        <div class="card">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">
                Quick Embed
            </div>
            <p style="font-size:13px;color:var(--muted);margin-bottom:10px;line-height:1.5;">Add this to your website — paste before the closing <code>&lt;/body&gt;</code> tag:</p>
            <div style="background:#0d0d14;border-radius:8px;padding:14px;overflow-x:auto;position:relative;">
                <pre id="embed-snippet" style="margin:0;color:#a5b4fc;font-size:11px;line-height:1.7;white-space:pre;font-family:monospace;">&lt;div id="booking-widget"&gt;&lt;/div&gt;
&lt;script src="{{ config('app.url') }}/sdk/booking.js"&gt;&lt;/script&gt;
&lt;script&gt;
(async () => {
  await Booking.init({
    publicKey: '{{ $developer->public_key }}',
    baseUrl:   '{{ config('app.url') }}/api',
    returnUrl: window.location.href + '?booking=success',
  });
  Booking.widget('#booking-widget', { title: 'Book Now' });
})();
&lt;/script&gt;</pre>
                <button onclick="copySnippet()" id="snippet-btn"
                        style="position:absolute;top:10px;right:10px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);color:#fff;padding:4px 10px;border-radius:5px;font-size:11px;cursor:pointer;font-family:inherit;">
                    Copy
                </button>
            </div>
            <div style="margin-top:10px;">
                <a href="{{ route('dashboard.integration') }}" style="font-size:13px;color:var(--accent);">
                    View full integration guide →
                </a>
            </div>
        </div>

        {{-- Danger zone --}}
        <div class="card" style="border-color:#fecaca;">
            <div style="font-size:11px;font-weight:700;color:#ef4444;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">
                Danger Zone
            </div>
            <p style="font-size:13px;color:var(--muted);margin-bottom:14px;line-height:1.5;">
                Regenerating your key <strong>immediately invalidates</strong> the current one.
                Any live integrations will stop working until you update them.
            </p>
            <form method="POST" action="{{ route('api-keys.regenerate') }}"
                  onsubmit="return confirm('Regenerate key? Your current key will stop working immediately.')">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm" style="color:#ef4444;border-color:#fecaca;">
                    ⚠ Regenerate Key
                </button>
            </form>
        </div>
    </div>

@endif

@push('scripts')
<script>
  // ── Key visibility toggle ──────────────────────────────────────────────────
  const KEY    = '{{ $developer->public_key ?? '' }}';
  const masked = KEY.slice(0, 12) + '••••••••••••••••••••••••';
  const keyEl  = document.getElementById('api-key-value');
  let   shown  = false;

  if (keyEl) keyEl.textContent = masked;

  function toggleKey() {
    shown = !shown;
    if (keyEl) keyEl.textContent = shown ? KEY : masked;
    const btn = document.getElementById('toggle-btn');
    if (btn) btn.textContent = shown ? 'Hide' : 'Show';
  }

  function copyKey() {
    navigator.clipboard.writeText(KEY).then(() => {
      const btn = document.getElementById('copy-btn');
      if (btn) { btn.textContent = 'Copied!'; setTimeout(()=>btn.textContent='Copy Key',2000); }
    });
  }

  function copySnippet() {
    const pre = document.getElementById('embed-snippet');
    const text = pre?.textContent || '';
    navigator.clipboard.writeText(text.trim()).then(() => {
      const btn = document.getElementById('snippet-btn');
      if (btn) { btn.textContent = 'Copied!'; setTimeout(()=>btn.textContent='Copy',2000); }
    });
  }

  // ── Bank account lookup ────────────────────────────────────────────────────
  let lookupTimer;
  function triggerLookup() {
    clearTimeout(lookupTimer);
    const acc  = document.getElementById('account-input')?.value  || '';
    const bank = document.getElementById('bank-select')?.value    || '';
    const el   = document.getElementById('account-name');
    if (!el) return;
    el.style.color   = 'var(--muted)';
    el.textContent   = '';
    if (acc.length !== 10 || !bank) return;
    el.textContent   = 'Looking up…';
    lookupTimer = setTimeout(async () => {
      try {
        const res  = await fetch('{{ route("api.verify-account") }}', {
          method:  'POST',
          headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}' },
          body:    JSON.stringify({ account_number: acc, bank_code: bank }),
        });
        const data = await res.json();
        if (res.ok && data.account?.account_name) {
          el.style.color = '#10b981';
          el.textContent = '✓ ' + data.account.account_name;
        } else {
          el.style.color = '#ef4444';
          el.textContent = 'Account not found';
        }
      } catch {
        el.style.color = '#ef4444';
        el.textContent = 'Could not verify';
      }
    }, 700);
  }
</script>
@endpush

@endsection