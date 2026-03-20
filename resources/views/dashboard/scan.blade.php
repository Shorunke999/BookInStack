@extends('layouts.app')
@section('title', $modeConfig['plural'])
@section('page-title', $modeConfig['plural'])

@section('content')

{{-- ── Mode + slot indicators ───────────────────────────────────────────────── --}}
<div style="display:flex; align-items:center; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
    <span style="
        display:inline-flex; align-items:center; gap:6px;
        background:var(--accent-light); color:var(--accent);
        font-size:12px; font-weight:700; padding:5px 12px; border-radius:20px;
    ">{{ match($modeConfig['mode']) { 'ticket'=>'🎟', 'reservation'=>'🏨', default=>'🗓' } }}
    {{ $modeConfig['label'] }} Mode</span>

    <span style="font-size:13px; color:var(--muted);">
        {{ number_format($bookings->total()) }} {{ strtolower($modeConfig['plural']) }}
    </span>

    @foreach($categories as $cat)
        @if($cat->total_slots !== null)
            @php $rem = $cat->slotsRemaining(); $pct = $cat->total_slots > 0 ? round(($cat->slotsBooked() / $cat->total_slots) * 100) : 0; @endphp
            <span style="
                font-size:12px; font-weight:600; padding:4px 11px; border-radius:20px;
                {{ $rem === 0 ? 'background:#fef2f2;color:#ef4444;' : ($pct >= 80 ? 'background:#fffbeb;color:#d97706;' : 'background:#f0fdf4;color:#15803d;') }}
            ">{{ $cat->name }}: {{ $rem === 0 ? 'Full' : $rem . ' slot' . ($rem === 1 ? '' : 's') . ' left' }}</span>
        @endif
    @endforeach
</div>

{{-- ── Filters ──────────────────────────────────────────────────────────────── --}}
<div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-bottom:16px;">

    <div style="display:flex; gap:6px; flex-wrap:wrap;">
        @foreach(['all'=>'All','pending'=>'Pending','paid'=>'Paid','failed'=>'Failed'] as $v=>$l)
            <a href="{{ route('dashboard.bookings', array_merge(request()->only('search','category'), ['status' => $v === 'all' ? null : $v])) }}"
               style="padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600; text-decoration:none; white-space:nowrap;
                      {{ request('status','all') === $v ? 'background:var(--accent);color:#fff;' : 'background:#fff;color:var(--muted);border:1px solid var(--border);' }}">
                {{ $l }}
            </a>
        @endforeach
    </div>

    @if($categories->isNotEmpty())
        <div style="display:flex; gap:6px; flex-wrap:wrap;">
            <a href="{{ route('dashboard.bookings', array_merge(request()->only('status','search'), ['category'=>null])) }}"
               style="padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600; text-decoration:none; white-space:nowrap;
                      {{ !request('category') ? 'background:var(--ink);color:#fff;' : 'background:#fff;color:var(--muted);border:1px solid var(--border);' }}">All</a>
            @foreach($categories as $cat)
                <a href="{{ route('dashboard.bookings', array_merge(request()->only('status','search'), ['category'=>$cat->id])) }}"
                   style="padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600; text-decoration:none; white-space:nowrap;
                          {{ request('category') == $cat->id ? 'background:var(--ink);color:#fff;' : 'background:#fff;color:var(--muted);border:1px solid var(--border);' }}">
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('dashboard.bookings') }}"
          style="display:flex; gap:6px; flex:1; min-width:180px; max-width:280px; margin-left:auto;">
        @foreach(request()->only('status','category') as $k=>$v)
            @if($v) <input type="hidden" name="{{ $k }}" value="{{ $v }}" /> @endif
        @endforeach
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Name, email, ref…" class="form-control" style="flex:1; font-size:13px;" />
        <button type="submit" class="btn btn-outline btn-sm">Go</button>
    </form>
</div>

{{-- ════════════════════════════════════════════════════════
     APPOINTMENT TABLE
════════════════════════════════════════════════════════ --}}
@if($modeConfig['mode'] === 'appointment')
<div class="card" style="padding:0; overflow:hidden;">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Service</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Fee</th>
                    <th>Status</th>
                    <th>Attended</th>
                    <th class="hide-mobile">Booked On</th>
                    @if(auth()->user()->isAdmin()) <th></th> @endif
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $b)
                <tr>
                    <td class="mono" style="font-size:11px;">
                        <button onclick="viewBooking('{{ $b->reference }}')"
                                style="background:none;border:none;cursor:pointer;color:var(--accent);font-family:'DM Mono',monospace;font-size:11px;padding:0;text-decoration:underline;">
                            {{ $b->reference }}
                        </button>
                    </td>
                    <td>
                        <div style="font-weight:600; font-size:13px;">{{ $b->customer_name ?: '—' }}</div>
                        <div style="font-size:11px; color:var(--muted);">{{ $b->customer_email }}</div>
                    </td>
                    <td style="font-size:13px;">
                        {{ $b->category?->name ?? $b->description }}
                        @if($b->category?->duration_minutes)
                            <div style="font-size:11px; color:var(--muted);">⏱ {{ $b->category->duration_minutes }} min</div>
                        @endif
                    </td>
                    <td style="font-size:13px; white-space:nowrap;">
                        {{ $b->preferred_date?->format('d M Y') ?? '—' }}
                    </td>
                    <td style="font-size:13px; white-space:nowrap;">
                        {{ $b->preferred_time ? \Carbon\Carbon::parse($b->preferred_time)->format('g:i A') : '—' }}
                    </td>
                    <td style="font-weight:700; font-size:13px; white-space:nowrap;">
                        ₦{{ number_format($b->amount / 100, 2) }}
                    </td>
                    <td>@include('components.status-badge', ['status' => $b->status])</td>
                    <td>
                        @if($b->status === 'paid')
                            @if($b->attended)
                                <span style="font-size:11px; font-weight:700; background:#f0fdf4; color:#15803d; padding:3px 8px; border-radius:10px;">✓ Attended</span>
                            @else
                                <form method="POST" action="{{ route('bookings.attend', $b->reference) }}" style="margin:0;">
                                    @csrf
                                    <input type="hidden" name="attended" value="1" />
                                    <button type="submit" class="btn btn-outline btn-sm" style="font-size:11px; padding:3px 9px;">Mark</button>
                                </form>
                            @endif
                        @else
                            <span style="color:var(--border);">—</span>
                        @endif
                    </td>
                    <td class="hide-mobile" style="font-size:12px; white-space:nowrap;">
                        {{ $b->created_at->format('d M Y') }}
                        <div style="color:var(--muted);">{{ $b->created_at->format('H:i') }}</div>
                    </td>
                    @if(auth()->user()->isAdmin())
                        <td>
                            @if($b->attended)
                                <form method="POST" action="{{ route('bookings.attend', $b->reference) }}" style="margin:0;">
                                    @csrf <input type="hidden" name="attended" value="0" />
                                    <button type="submit" class="btn btn-outline btn-sm" style="font-size:11px; padding:3px 9px; color:var(--muted);">Undo</button>
                                </form>
                            @endif
                        </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center; padding:48px; color:var(--muted); font-size:14px;">
                    No appointments yet.
                    @if(request()->hasAny(['search','status','category']))
                        <a href="{{ route('dashboard.bookings') }}" style="color:var(--accent);">Clear filters</a>
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($bookings->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border);">
            {{ $bookings->withQueryString()->links('components.pagination') }}
        </div>
    @endif
</div>
@endif


{{-- ════════════════════════════════════════════════════
     TICKET TABLE
════════════════════════════════════════════════════ --}}
@if($modeConfig['mode'] === 'ticket')
<div class="card" style="padding:0; overflow:hidden;">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Ticket Type</th>
                    <th style="text-align:center;">Adults</th>
                    <th style="text-align:center;" class="hide-mobile">Children</th>
                    <th style="text-align:right;">Total</th>
                    <th>Status</th>
                    <th>Checked In</th>
                    <th class="hide-mobile">Booked On</th>
                    @if(auth()->user()->isAdmin()) <th></th> @endif
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $b)
                @php
                    $adultPrice = $b->amount;
                    $childPrice = ($b->category?->enable_child_pricing && $b->category->child_price)
                        ? $b->category->child_price : $b->amount;
                    $total = ($adultPrice * $b->adults) + ($childPrice * $b->children);
                @endphp
                <tr>
                    <td class="mono" style="font-size:11px;">
                        <button onclick="viewBooking('{{ $b->reference }}')"
                                style="background:none;border:none;cursor:pointer;color:var(--accent);font-family:'DM Mono',monospace;font-size:11px;padding:0;text-decoration:underline;">
                            {{ $b->reference }}
                        </button>
                    </td>
                    <td>
                        <div style="font-weight:600; font-size:13px;">{{ $b->customer_name ?: '—' }}</div>
                        <div style="font-size:11px; color:var(--muted);">{{ $b->customer_email }}</div>
                    </td>
                    <td style="font-size:13px;">
                        {{ $b->category?->name ?? $b->description }}
                    </td>
                    <td style="text-align:center;">
                        <span style="font-weight:700; font-size:14px;">{{ $b->adults }}</span>
                        <div style="font-size:11px; color:var(--muted);">₦{{ number_format($adultPrice / 100, 0) }} ea</div>
                    </td>
                    <td style="text-align:center;" class="hide-mobile">
                        @if($b->children > 0)
                            <span style="font-weight:700; font-size:14px;">{{ $b->children }}</span>
                            <div style="font-size:11px; color:var(--muted);">₦{{ number_format($childPrice / 100, 0) }} ea</div>
                        @else
                            <span style="color:var(--border);">—</span>
                        @endif
                    </td>
                    <td style="text-align:right; font-weight:700; font-size:14px; white-space:nowrap;">
                        ₦{{ number_format($total / 100, 2) }}
                    </td>
                    <td>@include('components.status-badge', ['status' => $b->status])</td>
                    <td>
                        @if($b->status === 'paid')
                            @if($b->attended)
                                <span style="font-size:11px; font-weight:700; background:#f0fdf4; color:#15803d; padding:3px 8px; border-radius:10px;">✓ In</span>
                                @if($b->attended_at)
                                    <div style="font-size:11px; color:var(--muted);">{{ $b->attended_at->format('H:i') }}</div>
                                @endif
                            @else
                                <form method="POST" action="{{ route('bookings.attend', $b->reference) }}" style="margin:0;">
                                    @csrf <input type="hidden" name="attended" value="1" />
                                    <button type="submit" class="btn btn-outline btn-sm" style="font-size:11px; padding:3px 9px;">Check In</button>
                                </form>
                            @endif
                        @else
                            <span style="color:var(--border);">—</span>
                        @endif
                    </td>
                    <td class="hide-mobile" style="font-size:12px; white-space:nowrap;">
                        {{ $b->created_at->format('d M Y') }}
                        <div style="color:var(--muted);">{{ $b->created_at->format('H:i') }}</div>
                    </td>
                    @if(auth()->user()->isAdmin())
                        <td>
                            @if($b->attended)
                                <form method="POST" action="{{ route('bookings.attend', $b->reference) }}" style="margin:0;">
                                    @csrf <input type="hidden" name="attended" value="0" />
                                    <button type="submit" class="btn btn-outline btn-sm" style="font-size:11px; padding:3px 9px; color:var(--muted);">Undo</button>
                                </form>
                            @endif
                        </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center; padding:48px; color:var(--muted); font-size:14px;">
                    No tickets yet.
                    @if(request()->hasAny(['search','status','category']))
                        <a href="{{ route('dashboard.bookings') }}" style="color:var(--accent);">Clear filters</a>
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($bookings->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border);">
            {{ $bookings->withQueryString()->links('components.pagination') }}
        </div>
    @endif
</div>
@endif


{{-- ════════════════════════════════════════════════════════
     RESERVATION TABLE
════════════════════════════════════════════════════════ --}}
@if($modeConfig['mode'] === 'reservation')
<div class="card" style="padding:0; overflow:hidden;">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Guest</th>
                    <th>Room / Space</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th style="text-align:center;" class="hide-mobile">Nights</th>
                    <th style="text-align:right;">Total</th>
                    <th>Status</th>
                    <th>Checked Out</th>
                    <th class="hide-mobile">Booked On</th>
                    @if(auth()->user()->isAdmin()) <th></th> @endif
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $b)
                @php
                    $nights = $b->nights() ?? 1;
                    $total  = $b->amount * $nights;
                @endphp
                <tr>
                    <td class="mono" style="font-size:11px;">
                        <button onclick="viewBooking('{{ $b->reference }}')"
                                style="background:none;border:none;cursor:pointer;color:var(--accent);font-family:'DM Mono',monospace;font-size:11px;padding:0;text-decoration:underline;">
                            {{ $b->reference }}
                        </button>
                    </td>
                    <td>
                        <div style="font-weight:600; font-size:13px;">{{ $b->customer_name ?: '—' }}</div>
                        <div style="font-size:11px; color:var(--muted);">{{ $b->customer_email }}</div>
                        @if($b->customer_phone)
                            <div style="font-size:11px; color:var(--muted);">{{ $b->customer_phone }}</div>
                        @endif
                    </td>
                    <td style="font-size:13px;">
                        {{ $b->category?->name ?? $b->description }}
                        @if($b->category?->capacity)
                            <div style="font-size:11px; color:var(--muted);">👥 {{ $b->category->capacity }} guests max</div>
                        @endif
                    </td>
                    <td style="white-space:nowrap;">
                        @if($b->check_in)
                            <div style="font-weight:600; font-size:13px;">{{ $b->check_in->format('d M') }}</div>
                            <div style="font-size:11px; color:var(--muted);">{{ $b->check_in->format('Y') }}</div>
                        @else —
                        @endif
                    </td>
                    <td style="white-space:nowrap;">
                        @if($b->check_out)
                            <div style="font-weight:600; font-size:13px;">{{ $b->check_out->format('d M') }}</div>
                            <div style="font-size:11px; color:var(--muted);">{{ $b->check_out->format('Y') }}</div>
                        @else —
                        @endif
                    </td>
                    <td style="text-align:center;" class="hide-mobile">
                        <span style="font-weight:700; font-size:14px;">{{ $nights }}</span>
                        <div style="font-size:11px; color:var(--muted);">₦{{ number_format($b->amount / 100, 0) }}/n</div>
                    </td>
                    <td style="text-align:right; font-weight:700; font-size:14px; white-space:nowrap;">
                        ₦{{ number_format($total / 100, 2) }}
                    </td>
                    <td>@include('components.status-badge', ['status' => $b->status])</td>
                    <td>
                        @if($b->status === 'paid')
                            @if($b->attended)
                                <span style="font-size:11px; font-weight:700; background:#f0fdf4; color:#15803d; padding:3px 8px; border-radius:10px;">✓ Out</span>
                                @if($b->attended_at)
                                    <div style="font-size:11px; color:var(--muted);">{{ $b->attended_at->format('d M H:i') }}</div>
                                @endif
                            @else
                                <form method="POST" action="{{ route('bookings.attend', $b->reference) }}" style="margin:0;">
                                    @csrf <input type="hidden" name="attended" value="1" />
                                    <button type="submit" class="btn btn-outline btn-sm" style="font-size:11px; padding:3px 9px;">Check Out</button>
                                </form>
                            @endif
                        @else
                            <span style="color:var(--border);">—</span>
                        @endif
                    </td>
                    <td class="hide-mobile" style="font-size:12px; white-space:nowrap;">
                        {{ $b->created_at->format('d M Y') }}
                        <div style="color:var(--muted);">{{ $b->created_at->format('H:i') }}</div>
                    </td>
                    @if(auth()->user()->isAdmin())
                        <td>
                            @if($b->attended)
                                <form method="POST" action="{{ route('bookings.attend', $b->reference) }}" style="margin:0;">
                                    @csrf <input type="hidden" name="attended" value="0" />
                                    <button type="submit" class="btn btn-outline btn-sm" style="font-size:11px; padding:3px 9px; color:var(--muted);">Undo</button>
                                </form>
                            @endif
                        </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="11" style="text-align:center; padding:48px; color:var(--muted); font-size:14px;">
                    No reservations yet.
                    @if(request()->hasAny(['search','status','category']))
                        <a href="{{ route('dashboard.bookings') }}" style="color:var(--accent);">Clear filters</a>
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($bookings->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border);">
            {{ $bookings->withQueryString()->links('components.pagination') }}
        </div>
    @endif
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
  /* Slide-out detail panel */
  .detail-overlay {
    position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:200;
    display:none;opacity:0;transition:opacity .25s;
  }
  .detail-overlay.open { display:block; opacity:1; }
  .detail-panel {
    position:fixed;top:0;right:0;bottom:0;width:420px;max-width:100vw;
    background:#fff;z-index:201;
    transform:translateX(100%);transition:transform .3s ease;
    display:flex;flex-direction:column;overflow:hidden;
    box-shadow:-8px 0 32px rgba(0,0,0,.12);
  }
  .detail-panel.open { transform:translateX(0); }
  .detail-header {
    padding:20px 24px;border-bottom:1px solid var(--border);
    display:flex;align-items:center;justify-content:space-between;flex-shrink:0;
  }
  .detail-body { flex:1;overflow-y:auto;padding:20px 24px; }
  .detail-row { display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);font-size:13px; }
  .detail-row:last-of-type { border-bottom:none; }
  .detail-label { color:var(--muted); }
  .detail-value { font-weight:600;text-align:right;max-width:60%; }
  .detail-section { font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin:16px 0 8px;padding-top:12px;border-top:1px solid var(--border); }
</style>

{{-- Overlay + panel --}}
<div class="detail-overlay" id="detail-overlay" onclick="closeDetail()"></div>
<div class="detail-panel" id="detail-panel">
  <div class="detail-header">
    <div>
      <div style="font-weight:700;font-size:15px;" id="dp-title">Booking Details</div>
      <div style="font-size:12px;color:var(--muted);margin-top:2px;" id="dp-ref"></div>
    </div>
    <button onclick="closeDetail()" style="background:none;border:none;cursor:pointer;font-size:22px;color:var(--muted);padding:4px;">×</button>
  </div>
  {{-- QR code shown for ticket mode --}}
  <div id="dp-qr" style="display:none;text-align:center;padding:16px 0 8px;border-bottom:1px solid var(--border);margin-bottom:4px;">
    <div id="dp-qr-img" style="display:inline-block;padding:8px;border:1px solid var(--border);border-radius:10px;background:#fff;"></div>
    <div style="font-size:11px;color:var(--muted);margin-top:6px;font-family:monospace;" id="dp-qr-ref"></div>
  </div>
  <div class="detail-body" id="dp-body"></div>
</div>

<script>
  const MODE = '{{ $modeConfig['mode'] }}';

  // All bookings data — passed from blade
  const BOOKINGS = {
    @foreach($bookings as $b)
    '{{ $b->reference }}': {
      reference:      '{{ $b->reference }}',
      customer_name:  '{{ $b->customer_name ?: '—' }}',
      customer_email: '{{ $b->customer_email }}',
      customer_phone: '{{ $b->customer_phone ?: '—' }}',
      category:       '{{ $b->category?->name ?? $b->description }}',
      amount:         '₦{{ number_format($b->amount / 100, 2) }}',
      status:         '{{ $b->status }}',
      attended:       {{ $b->attended ? 'true' : 'false' }},
      attended_at:    '{{ $b->attended_at?->format('d M Y, H:i') ?? '' }}',
      attendance_note:'{{ $b->attendance_note ?? '' }}',
      created_at:     '{{ $b->created_at->format('d M Y, H:i') }}',
      paid_at:        '{{ $b->paid_at?->format('d M Y, H:i') ?? '' }}',
      @if($modeConfig['mode'] === 'appointment')
      preferred_date: '{{ $b->preferred_date?->format('d M Y') ?? '—' }}',
      preferred_time: '{{ $b->preferred_time ? \Carbon\Carbon::parse($b->preferred_time)->format('g:i A') : '—' }}',
      duration:       '{{ $b->category?->duration_minutes ? $b->category->duration_minutes . ' min' : '—' }}',
      @elseif($modeConfig['mode'] === 'ticket')
      adults:         {{ $b->adults ?? 1 }},
      children:       {{ $b->children ?? 0 }},
      unit_price:     '₦{{ number_format($b->amount / 100, 2) }}',
      @elseif($modeConfig['mode'] === 'reservation')
      check_in:       '{{ $b->check_in?->format('d M Y') ?? '—' }}',
      check_out:      '{{ $b->check_out?->format('d M Y') ?? '—' }}',
      nights:         {{ $b->nights() ?? 0 }},
      rate_per_unit:  '₦{{ number_format($b->amount / 100, 2) }}',
      total:          '₦{{ $b->check_in && $b->check_out ? number_format(($b->amount * $b->nights()) / 100, 2) : number_format($b->amount / 100, 2) }}',
      @endif
    },
    @endforeach
  };

  function viewBooking(ref) {
    const b = BOOKINGS[ref];
    if (!b) return;

    document.getElementById('dp-title').textContent = b.category;
    document.getElementById('dp-ref').textContent   = ref;

    // ── QR code in panel ──────────────────────────────────────────────────
    const qrWrap = document.getElementById('dp-qr');
    const qrImg  = document.getElementById('dp-qr-img');
    const qrRef  = document.getElementById('dp-qr-ref');

    // Show QR for ticket always, for others only if paid
    if (MODE === 'ticket' || b.status === 'paid') {
      qrWrap.style.display = 'block';
      qrRef.textContent    = ref;
      qrImg.innerHTML      = '';
      if (window.QRCode) {
        new QRCode(qrImg, {
          text:          ref,
          width:         120,
          height:        120,
          colorDark:     '#0d0d14',
          colorLight:    '#ffffff',
          correctLevel:  QRCode.CorrectLevel.M,
        });
      } else {
        // Fallback — monospace ref
        qrImg.innerHTML = `<div style="font-family:monospace;font-size:11px;padding:8px;word-break:break-all;">${ref}</div>`;
      }
    } else {
      qrWrap.style.display = 'none';
    }

    const statusColors = { pending:'#d97706', paid:'#15803d', failed:'#dc2626', cancelled:'#6b7280' };
    const statusBg     = { pending:'#fffbeb', paid:'#f0fdf4', failed:'#fef2f2', cancelled:'#f3f4f6' };

    let html = '';

    // Status badge
    html += `<div style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px;background:${statusBg[b.status]||'#f3f4f6'};color:${statusColors[b.status]||'#374151'};margin-bottom:16px;">
      ${b.status.toUpperCase()}
    </div>`;

    // Customer
    html += `<div class="detail-section">Customer</div>`;
    html += row('Name',  b.customer_name);
    html += row('Email', b.customer_email);
    html += row('Phone', b.customer_phone);

    // Booking
    html += `<div class="detail-section">Booking</div>`;
    html += row(MODE === 'ticket' ? 'Ticket Type' : MODE === 'reservation' ? 'Room / Space' : 'Service', b.category);
    html += row('Amount', b.amount);
    if (b.paid_at) html += row('Paid at', b.paid_at);
    html += row('Booked on', b.created_at);

    // Mode-specific
    if (MODE === 'appointment') {
      html += `<div class="detail-section">Schedule</div>`;
      html += row('Date', b.preferred_date);
      html += row('Time', b.preferred_time);
      if (b.duration !== '—') html += row('Duration', b.duration);
    }

    if (MODE === 'ticket') {
      html += `<div class="detail-section">Tickets</div>`;
      html += row('Adults',   b.adults + ' × ' + b.unit_price);
      if (b.children > 0) html += row('Children', b.children + ' × ' + b.unit_price);
      html += row('Total', '₦' + ((parseInt(b.adults||1) + parseInt(b.children||0)) * parseFloat((b.unit_price||'₦0').replace(/[₦,]/g,''))).toLocaleString('en-NG', {minimumFractionDigits:2}));
    }

    if (MODE === 'reservation') {
      html += `<div class="detail-section">Stay</div>`;
      html += row('Check-in',  b.check_in);
      html += row('Check-out', b.check_out);
      html += row('Duration',  b.nights + ' {{ $modeConfig['rate_unit_label'] ?? 'night' }}' + (b.nights !== 1 ? 's' : ''));
      html += row('Rate',      b.rate_per_unit + ' / {{ $modeConfig['rate_unit_label'] ?? 'night' }}');
      html += row('Total',     b.total);
    }

    // Attendance
    if (b.status === 'paid') {
      html += `<div class="detail-section">{{ $modeConfig['attendance_label'] }}</div>`;
      html += row('Status', b.attended
        ? `<span style="color:#15803d;font-weight:700;">✓ Yes${b.attended_at ? ' · ' + b.attended_at : ''}</span>`
        : '<span style="color:#d97706;">Not yet</span>');
      if (b.attendance_note) html += row('Note', b.attendance_note);
    }

    document.getElementById('dp-body').innerHTML = html;
    document.getElementById('detail-overlay').classList.add('open');
    document.getElementById('detail-panel').classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function row(label, value) {
    if (!value || value === '—' || value === '') return '';
    return `<div class="detail-row"><span class="detail-label">${label}</span><span class="detail-value">${value}</span></div>`;
  }

  function closeDetail() {
    document.getElementById('detail-overlay').classList.remove('open');
    document.getElementById('detail-panel').classList.remove('open');
    document.body.style.overflow = '';
  }

  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDetail(); });
</script>
@endpush