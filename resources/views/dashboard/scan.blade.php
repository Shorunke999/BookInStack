@extends('layouts.app')
@section('title', 'QR Scanner')
@section('page-title', 'Scan Ticket')

@section('content')

{{-- ── Desktop blocked state ──────────────────────────────────────────────── --}}
<div id="desktop-block" style="display:none; text-align:center; padding:60px 20px;">
    <div style="font-size:56px; margin-bottom:16px;">💻</div>
    <h2 style="font-size:20px; font-weight:700; margin-bottom:8px;">Phone Required</h2>
    <p style="font-size:14px; color:var(--muted); line-height:1.7; max-width:340px; margin:0 auto;">
        The QR scanner uses your device camera. Please open this page on a smartphone or tablet.
    </p>
    <div style="margin-top:24px; padding:14px 20px; background:var(--soft); border-radius:8px; border:1px solid var(--border); display:inline-block;">
        <div style="font-size:12px; color:var(--muted); margin-bottom:6px;">Or mark attendance manually:</div>
        <a href="{{ route('dashboard.bookings') }}" class="btn btn-primary btn-sm">Go to Bookings →</a>
    </div>
</div>

{{-- ── Mobile scanner ───────────────────────────────────────────────────────── --}}
<div id="scanner-wrap" style="display:none; max-width:440px; margin:0 auto;">

    {{-- Status bar --}}
    <div id="scan-status" style="
        text-align:center; padding:12px 16px; border-radius:8px;
        font-size:14px; font-weight:600; margin-bottom:20px;
        background:var(--soft); border:1px solid var(--border); color:var(--muted);
    ">
        📷 Point camera at QR code
    </div>

    {{-- Camera viewfinder --}}
    <div style="position:relative; border-radius:14px; overflow:hidden; background:#000; width:100%; height:300px;">
        <div id="qr-reader" style="width:100%; height:100%;"></div>

        {{-- Corner guides --}}
        <div style="position:absolute; inset:16px; pointer-events:none;">
            @foreach(['top-left','top-right','bottom-left','bottom-right'] as $corner)
                @php
                    $styles = match($corner) {
                        'top-left'     => 'top:0;left:0;border-top:3px solid #fff;border-left:3px solid #fff;',
                        'top-right'    => 'top:0;right:0;border-top:3px solid #fff;border-right:3px solid #fff;',
                        'bottom-left'  => 'bottom:0;left:0;border-bottom:3px solid #fff;border-left:3px solid #fff;',
                        'bottom-right' => 'bottom:0;right:0;border-bottom:3px solid #fff;border-right:3px solid #fff;',
                    };
                @endphp
                <div style="position:absolute; width:24px; height:24px; border-radius:2px; {{ $styles }}"></div>
            @endforeach
        </div>
    </div>

    {{-- Result card (hidden until scan) --}}
    <div id="result-card" style="display:none; margin-top:20px;" class="card">
        <div id="result-content"></div>
        <div style="display:flex; gap:10px; margin-top:16px;">
            <button id="btn-confirm" class="btn btn-primary btn-sm" style="flex:1;">
                ✓ Mark as Attended
            </button>
            <button id="btn-reset" class="btn btn-outline btn-sm">
                Scan Again
            </button>
        </div>
    </div>

    {{-- Manual lookup --}}
    <div style="margin-top:20px;" class="card">
        <div style="font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:10px;">
            Manual Lookup
        </div>
        <div style="display:flex; gap:8px;">
            <input type="text" id="manual-ref" class="form-control"
                   placeholder="e.g. BKG-CAK9OF6LQYDQ"
                   style="flex:1; font-size:13px; text-transform:uppercase;"
                   oninput="this.value=this.value.toUpperCase()" />
            <button onclick="lookupManual()" class="btn btn-outline btn-sm">Go</button>
        </div>
    </div>

</div>

{{-- html5-qrcode CDN --}}
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
  const IS_MOBILE = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent)
                    || window.innerWidth < 768;

  // ── Show correct UI based on device ────────────────────────────────────────
  document.getElementById(IS_MOBILE ? 'scanner-wrap' : 'desktop-block').style.display = 'block';

  if (!IS_MOBILE) {
    // Nothing more to do on desktop — stop here
  } else {

  let scanner      = null;
  let lastRef      = null;
  let scanLocked   = false;

  const statusEl   = document.getElementById('scan-status');
  const resultCard = document.getElementById('result-card');
  const resultBody = document.getElementById('result-content');
  const confirmBtn = document.getElementById('btn-confirm');

  // ── Init scanner ───────────────────────────────────────────────────────────
  function startScanner() {
    scanner = new Html5Qrcode('qr-reader');
    scanner.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 220, height: 220 } },
      onScanSuccess,
      () => {} // ignore per-frame errors
    ).catch(err => {
      setStatus('⚠️ Camera access denied. Use manual lookup below.', '#ef4444', '#fef2f2');
    });
  }

  function onScanSuccess(decodedText) {
    if (scanLocked) return;
    // Accept BKG- references or raw text
    const ref = decodedText.trim().toUpperCase();
    if (!ref) return;
    scanLocked = true;

    // Pause scanner
    if (scanner) scanner.pause();

    // Vibrate feedback
    if (navigator.vibrate) navigator.vibrate(100);

    lookupBooking(ref);
  }

  // ── Lookup booking via API ─────────────────────────────────────────────────
  async function lookupBooking(ref) {
    setStatus('🔍 Looking up booking…', '#1e40af', '#eff6ff');

    try {
      const res  = await fetch(`/scan/lookup/${encodeURIComponent(ref)}`, {
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
      });
      const data = await res.json();

      if (!res.ok) throw new Error(data.message || 'Booking not found.');

      lastRef = ref;
      showResult(data.booking);
    } catch (e) {
      setStatus(`❌ ${e.message}`, '#ef4444', '#fef2f2');
      setTimeout(() => {
        setStatus('📷 Point camera at QR code', null, null);
        if (scanner) scanner.resume();
        scanLocked = false;
      }, 2500);
    }
  }

  // ── Show booking result ────────────────────────────────────────────────────
  function showResult(b) {
    const statusColors = { pending:'#d97706', paid:'#15803d', failed:'#dc2626' };
    const alreadyIn    = b.attended;

    resultBody.innerHTML = `
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div style="font-weight:700;font-size:15px;">${b.customer_name || '—'}</div>
        <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px;
                     background:${b.status==='paid'?'#f0fdf4':'#fffbeb'};
                     color:${statusColors[b.status]||'#374151'};">
          ${b.status.toUpperCase()}
        </span>
      </div>
      <div style="font-size:13px;color:var(--muted);margin-bottom:4px;">${b.description || b.category || '—'}</div>
      <div style="font-family:monospace;font-size:12px;color:var(--muted);margin-bottom:12px;">${b.reference}</div>
      ${b.adults > 1 || b.children > 0 ? `
        <div style="font-size:13px;margin-bottom:8px;">
          🎟 ${b.adults} adult${b.adults!==1?'s':''}
          ${b.children > 0 ? `· ${b.children} child${b.children!==1?'ren':''}` : ''}
        </div>` : ''}
      ${alreadyIn ? `
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;border-radius:8px;padding:10px 14px;font-size:13px;font-weight:600;text-align:center;">
          ✓ Already checked in at ${b.attended_at || '—'}
        </div>` : ''}
    `;

    if (b.status !== 'paid') {
      confirmBtn.disabled = true;
      confirmBtn.textContent = 'Not paid';
      confirmBtn.style.opacity = '.5';
    } else if (alreadyIn) {
      confirmBtn.disabled = true;
      confirmBtn.textContent = '✓ Already In';
      confirmBtn.style.opacity = '.5';
    } else {
      confirmBtn.disabled = false;
      confirmBtn.textContent = '✓ Mark as Attended';
      confirmBtn.style.opacity = '1';
    }

    resultCard.style.display = 'block';
    setStatus(alreadyIn ? '⚠️ Already checked in' : '✅ Booking found — confirm below',
              alreadyIn ? '#d97706' : '#15803d',
              alreadyIn ? '#fffbeb' : '#f0fdf4');
  }

  // ── Confirm attendance ─────────────────────────────────────────────────────
  async function confirmAttend() {
    console.log('in the confirmAttend mehod');
    if (!lastRef) return;
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Processing…';

    try {
      const res  = await fetch(`/bookings/${encodeURIComponent(lastRef)}/attend`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ attended: true, isApi: true }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed.');

      if (navigator.vibrate) navigator.vibrate([100, 50, 100]);
      setStatus('🎉 Checked in!', '#15803d', '#f0fdf4');
      confirmBtn.textContent = '✓ Done!';

      setTimeout(resetScanner, 2000);
    } catch (e) {
      console.log('error from the confirm method', data);
      setStatus(`❌ ${e.message}`, '#ef4444', '#fef2f2');
      confirmBtn.disabled = false;
      confirmBtn.textContent = '✓ Mark as Attended';
    }
  }

  // ── Manual lookup ──────────────────────────────────────────────────────────
  function lookupManual() {
    const ref = document.getElementById('manual-ref').value.trim();
    if (!ref) return;
    scanLocked = true;
    if (scanner) scanner.pause();
    lookupBooking(ref);
  }

  // ── Reset ──────────────────────────────────────────────────────────────────
  function resetScanner() {
    lastRef    = null;
    scanLocked = false;
    resultCard.style.display = 'none';
    resultBody.innerHTML     = '';
    setStatus('📷 Point camera at QR code', null, null);
    if (scanner) scanner.resume();
  }

  function setStatus(text, color, bg) {
    statusEl.textContent = text;
    statusEl.style.color      = color || 'var(--muted)';
    statusEl.style.background = bg    || 'var(--soft)';
    statusEl.style.borderColor = color ? color + '33' : 'var(--border)';
  }

  document.getElementById('btn-confirm').addEventListener('click', confirmAttend);
  document.getElementById('btn-reset').addEventListener('click', resetScanner);
  // Start
  startScanner();

  } // end IS_MOBILE
</script>
@push('styles')
<style>
    #qr-reader { border: none !important; }
    #qr-reader video { width: 100% !important; height: 100% !important; object-fit: cover !important; }
    #qr-reader__scan_region { margin: 0 !important; padding: 0 !important; }
    #qr-reader__dashboard { display: none !important; }
</style>
@endpush
@endsection