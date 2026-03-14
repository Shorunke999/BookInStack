@extends('layouts.app')
@section('title', 'Overview')
@section('page-title', 'Overview')

@section('content')

{{-- ── Stat cards ──────────────────────────────────────────────────────────── --}}
<div class="stat-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-label">Revenue (All Time)</div>
        <div class="stat-value">₦{{ number_format($stats['total_revenue'], 2) }}</div>
        <div class="stat-sub">Your 95% share</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">{{ now()->format('F') }} Revenue</div>
        <div class="stat-value">₦{{ number_format($stats['monthly_revenue'], 2) }}</div>
        <div class="stat-sub">This month</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total {{ $modeConfig['plural'] }}</div>
        <div class="stat-value">{{ number_format($stats['total_bookings']) }}</div>
        <div class="stat-sub">
            {{ number_format($stats['pending_bookings']) }} pending
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Paid</div>
        <div class="stat-value">{{ number_format($stats['paid_bookings']) }}</div>
        <div class="stat-sub">
            {{ $stats['attended'] }} {{ strtolower($modeConfig['attendance_label']) }}
        </div>
    </div>
</div>

{{-- ── Recent bookings ─────────────────────────────────────────────────────── --}}
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <div>
            <h3 style="margin:0; font-size:15px; font-weight:700;">Recent {{ $modeConfig['plural'] }}</h3>
            <span style="font-size:12px; color:var(--muted);">
                Mode:
                <span style="
                    display:inline-flex; align-items:center; gap:4px;
                    background:var(--accent-light); color:var(--accent);
                    font-size:11px; font-weight:600;
                    padding:2px 8px; border-radius:10px; margin-left:2px;
                ">
                    {{ match($modeConfig['mode']) { 'ticket'=>'🎟', 'reservation'=>'🏨', default=>'🗓' } }}
                    {{ $modeConfig['label'] }}
                </span>
            </span>
        </div>
        <a href="{{ route('dashboard.bookings') }}"
           style="font-size:13px; color:var(--accent); text-decoration:none; white-space:nowrap;">
            View all →
        </a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>{{ $modeConfig['desc_label'] }}</th>
                    @if($modeConfig['mode'] === 'ticket')
                        <th>Qty</th>
                    @elseif($modeConfig['mode'] === 'reservation')
                        <th>Dates</th>
                    @else
                        <th class="hide-mobile">Date/Time</th>
                    @endif
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentBookings as $booking)
                    <tr>
                        <td class="mono" style="font-size:11px;">{{ $booking->reference }}</td>
                        <td>
                            <div style="font-weight:500; font-size:13px;">{{ $booking->customer_name ?: '—' }}</div>
                            <div style="font-size:11px; color:var(--muted);">{{ $booking->customer_email }}</div>
                        </td>
                        <td style="font-size:13px;">
                            {{ $booking->category?->name ?? $booking->description }}
                        </td>

                        @if($modeConfig['mode'] === 'ticket')
                            <td>
                                <span style="font-weight:600;">{{ $booking->adults }}</span>
                                @if($booking->children > 0)
                                    <span style="font-size:11px; color:var(--muted);">+{{ $booking->children }}c</span>
                                @endif
                            </td>
                        @elseif($modeConfig['mode'] === 'reservation')
                            <td style="font-size:12px; white-space:nowrap;">
                                @if($booking->check_in)
                                    {{ $booking->check_in->format('d M') }} →
                                    {{ $booking->check_out?->format('d M') }}
                                @else —
                                @endif
                            </td>
                        @else
                            <td class="hide-mobile" style="font-size:12px; white-space:nowrap;">
                                @if($booking->preferred_date)
                                    {{ $booking->preferred_date->format('d M') }}
                                    @if($booking->preferred_time)
                                        <span style="color:var(--muted);">
                                            {{ \Carbon\Carbon::parse($booking->preferred_time)->format('g:i A') }}
                                        </span>
                                    @endif
                                @else —
                                @endif
                            </td>
                        @endif

                        <td style="font-weight:600; font-size:13px; white-space:nowrap;">
                            ₦{{ number_format($booking->amount / 100, 2) }}
                        </td>
                        <td>@include('components.status-badge', ['status' => $booking->status])</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; color:var(--muted); padding:40px; font-size:14px;">
                            No {{ strtolower($modeConfig['plural']) }} yet.
                            <a href="{{ route('dashboard.integration') }}" style="color:var(--accent);">
                                Set up your widget →
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection