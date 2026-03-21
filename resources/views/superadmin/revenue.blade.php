@extends('layouts.app')
@section('title','Revenue')
@section('page-title','Revenue Breakdown')

@section('content')

{{-- Totals --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;">
    @foreach([
        ['Total Volume','₦'.number_format($totals['volume']/100,2),'💰',''],
        ['Platform Earned','₦'.number_format($totals['platform']/100,2),'🏦','color:var(--accent);'],
        ['Paid to Developers','₦'.number_format($totals['payout']/100,2),'💳',''],
    ] as [$l,$v,$i,$s])
        <div class="card" style="text-align:center;padding:24px;">
            <div style="font-size:32px;margin-bottom:8px;">{{ $i }}</div>
            <div style="font-size:24px;font-weight:800;{{ $s }}">{{ $v }}</div>
            <div style="font-size:12px;color:var(--muted);margin-top:6px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;">{{ $l }}</div>
        </div>
    @endforeach
</div>

{{-- Per-developer breakdown --}}
<div class="card" style="padding:0;overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
        <h3 style="font-size:14px;">Per Developer Breakdown</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Developer</th>
                    <th style="text-align:center;">Fee %</th>
                    <th style="text-align:right;">Volume</th>
                    <th style="text-align:right;">Platform Earned</th>
                    <th style="text-align:right;">Developer Received</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($developers as $dev)
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:13px;">{{ $dev->business_name ?? $dev->name }}</div>
                            <div style="font-size:11px;color:var(--muted);">{{ $dev->email }}</div>
                        </td>
                        <td style="text-align:center;font-size:13px;font-weight:700;">{{ $dev->platform_fee_percent }}%</td>
                        <td style="text-align:right;font-size:13px;font-weight:600;">₦{{ number_format(($dev->paid_volume??0)/100,2) }}</td>
                        <td style="text-align:right;font-size:13px;font-weight:700;color:var(--accent);">₦{{ number_format($dev->platform_earned/100,2) }}</td>
                        <td style="text-align:right;font-size:13px;">₦{{ number_format($dev->developer_earned/100,2) }}</td>
                        <td><a href="{{ route('superadmin.developers.show',$dev->id) }}" style="font-size:12px;color:var(--accent);">Details →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">No data yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection