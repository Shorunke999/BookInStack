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
                    <td class="mono" style="font-size:11px;">{{ $b->reference }}</td>
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
                        ₦{{ number_format($b->amount , 2) }}
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
                    <td class="mono" style="font-size:11px;">{{ $b->reference }}</td>
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
                    <td class="mono" style="font-size:11px;">{{ $b->reference }}</td>
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
                        <div style="font-size:11px; color:var(--muted);">₦{{ number_format($b->amount , 0) }}/n</div>
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