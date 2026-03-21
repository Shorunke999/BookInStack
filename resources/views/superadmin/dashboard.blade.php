@extends('layouts.app')
@section('title','Superadmin')
@section('page-title','Superadmin Dashboard')

@section('content')

{{-- ── Stat cards ────────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;">
    @foreach([
        ['Total Developers', $stats['total_developers'], '👥', null],
        ['Active Developers',$stats['active_developers'],'✅', null],
        ['Total Bookings',   $stats['total_bookings'],   '📋', null],
        ['Paid Bookings',    $stats['paid_bookings'],    '💳', null],
        ['Platform Volume',  '₦'.number_format($stats['total_revenue']/100,2), '💰', null],
        ['Platform Earned',  '₦'.number_format($stats['platform_revenue']/100,2),'🏦','color:var(--accent);'],
    ] as [$label,$value,$icon,$style])
        <div class="card" style="text-align:center;padding:20px 16px;">
            <div style="font-size:28px;margin-bottom:8px;">{{ $icon }}</div>
            <div style="font-size:22px;font-weight:800;{{ $style }}">{{ $value }}</div>
            <div style="font-size:12px;color:var(--muted);margin-top:4px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;">{{ $label }}</div>
        </div>
    @endforeach
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

    {{-- Recent developers --}}
    <div class="card" style="padding:0;overflow:hidden;">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <h3 style="font-size:14px;">Recent Developers</h3>
            <a href="{{ route('superadmin.developers') }}" style="font-size:12px;color:var(--accent);">View all →</a>
        </div>
        <div class="table-wrap">
            <table>
                <tbody>
                    @foreach($recentDevelopers as $dev)
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:13px;">{{ $dev->business_name ?? $dev->name }}</div>
                            <div style="font-size:11px;color:var(--muted);">{{ $dev->email }}</div>
                        </td>
                        <td>
                            @php $sc=['active'=>'#15803d:#f0fdf4','pending'=>'#d97706:#fffbeb','suspended'=>'#ef4444:#fef2f2']; [$c,$bg]=explode(':',$sc[$dev->status]??'#6b7280:#f3f4f6'); @endphp
                            <span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:10px;background:{{ $bg }};color:{{ $c }};">{{ ucfirst($dev->status) }}</span>
                        </td>
                        <td style="font-size:12px;color:var(--muted);">{{ $dev->platform_fee_percent ?? 5 }}%</td>
                        <td><a href="{{ route('superadmin.developers.show', $dev->id) }}" style="font-size:12px;color:var(--accent);">View</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent paid bookings --}}
    <div class="card" style="padding:0;overflow:hidden;">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <h3 style="font-size:14px;">Recent Paid Bookings</h3>
            <a href="{{ route('superadmin.bookings') }}" style="font-size:12px;color:var(--accent);">View all →</a>
        </div>
        <div class="table-wrap">
            <table>
                <tbody>
                    @foreach($recentBookings as $b)
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:13px;">{{ $b->customer_name ?? '—' }}</div>
                            <div style="font-size:11px;color:var(--muted);">{{ $b->developer->business_name ?? $b->developer->name }}</div>
                        </td>
                        <td style="text-align:right;font-weight:700;font-size:13px;white-space:nowrap;">₦{{ number_format($b->amount/100,2) }}</td>
                        <td style="font-size:11px;color:var(--muted);">{{ $b->paid_at?->format('d M') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection