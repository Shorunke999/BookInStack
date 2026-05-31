@extends('layouts.app')
@section('title', 'Overview')
@section('page-title', 'Overview')

@section('content')

<div class="flex justify-end items-center mb-12 gap-3">
    <button onclick="toggleExportModal()" class="btn btn-outline btn-sm">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Export Bookings
    </button>
</div>
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
        <div class="stat-label">Total {{ $modeConfig['plural'] ?? 'bookings' }}</div>
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
                    <th>Payment Status</th>
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
                            ₦{{ number_format($booking->amount *($booking->nights() ?? 1) , 2) }}
                        </td>
                        <td>@include('components.status-badge', ['payment_status' => $booking->payment_status])</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; color:var(--muted); padding:40px; font-size:14px;">
                            No {{ strtolower($modeConfig['plural']) }} yet.
                            <a href="{{ route('services.create') }}" style="color:var(--accent);">
                                Set up your Service →
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Export Modal -->
<div id="exportModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; max-width: 500px; width: 90%; padding: 24px;">
        <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">Export Bookings</h3>

        <form action="{{ route('bookings.export') }}" method="GET">
            <div class="form-group">
                <label>Date Range</label>
                <select name="date_range" class="form-control" id="dateRangeSelect">
                    <option value="all">All Time</option>
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="this_week">This Week</option>
                    <option value="last_week">Last Week</option>
                    <option value="this_month">This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>

            <div id="customRange" style="display: none;">
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="from_date" class="form-control">
                </div>
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="to_date" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label>Payment Status</label>
                <select name="payment_status" class="form-control">
                    <option value="">All Payment Statuses</option>
                    <option value="paid">Paid</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            @php
                $categories = $service?->bookingCategories()->orderBy('name')->get() ?? collect();
            @endphp
            @if($categories->isNotEmpty())
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" class="form-control">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif


            <div class="form-group" style="margin-bottom: 0;">
                <label>Include Metrics Summary</label>
                <select name="include_metrics" class="form-control">
                    <option value="yes">Yes (with separate metrics sheet)</option>
                    <option value="no">No (bookings only)</option>
                </select>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="toggleExportModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Download CSV</button>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>
function toggleExportModal() {
    const modal = document.getElementById('exportModal');
    if (modal.style.display === 'flex') {
        modal.style.display = 'none';
    } else {
        modal.style.display = 'flex';
    }
}

document.getElementById('dateRangeSelect').addEventListener('change', function() {
    const customRange = document.getElementById('customRange');
    customRange.style.display = this.value === 'custom' ? 'block' : 'none';
});

// Close modal when clicking outside
document.getElementById('exportModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});
</script>
@endpush
