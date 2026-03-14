@extends('layouts.app')
@section('title', 'Payments')
@section('page-title', 'Payments')

@section('content')

{{-- ── Summary cards ───────────────────────────────────────────────────────── --}}
{{-- <div class="stat-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-label">Total Settled</div>
        <div class="stat-value" style="color:var(--green);">
            ₦{{ number_format($stats['total_settled'], 2) }}
        </div>
        <div class="stat-sub">Your 95% share</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">{{ now()->format('F') }}</div>
        <div class="stat-value">₦{{ number_format($stats['this_month'], 2) }}</div>
        <div class="stat-sub">This month</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Transactions</div>
        <div class="stat-value">{{ number_format($stats['total_transactions']) }}</div>
        <div class="stat-sub">Successful payments</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Platform Fees</div>
        <div class="stat-value" style="font-size:1.4rem;">
            ₦{{ number_format($stats['total_fees'], 2) }}
        </div>
        <div class="stat-sub">5% BookStack fee</div>
    </div>
</div> --}}

{{-- ── Table ────────────────────────────────────────────────────────────────── --}}
<div class="card" style="padding:0; overflow:hidden;">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Paystack Ref</th>
                    <th>Booking</th>
                    <th class="hide-mobile">Category</th>
                    <th>Charged</th>
                    <th>Your Share</th>
                    <th class="hide-mobile">Fee</th>
                    <th class="hide-mobile">Channel</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td class="mono" style="font-size:11px;">
                            {{ $payment->paystack_reference }}
                        </td>
                        <td class="mono" style="font-size:11px;">
                            {{ $payment->booking?->reference ?? '—' }}
                        </td>
                        <td class="hide-mobile" style="font-size:13px; color:var(--muted);">
                            {{ $payment->booking?->category?->name ?? '—' }}
                        </td>
                        <td style="font-size:13px; white-space:nowrap;">
                            ₦{{ number_format($payment->amount / 100, 2) }}
                        </td>
                        <td style="font-weight:700; color:var(--green); white-space:nowrap; font-size:13px;">
                            ₦{{ number_format($payment->developer_amount / 100, 2) }}
                        </td>
                        <td class="hide-mobile" style="font-size:12px; color:var(--muted); white-space:nowrap;">
                            ₦{{ number_format($payment->platform_fee / 100, 2) }}
                        </td>
                        <td class="hide-mobile">
                            @if($payment->channel)
                                <span style="
                                    font-size:11px; font-weight:600; text-transform:capitalize;
                                    background:#f3f4f6; color:var(--muted);
                                    padding:3px 8px; border-radius:8px;
                                ">{{ $payment->channel }}</span>
                            @else —
                            @endif
                        </td>
                        <td style="font-size:12px; white-space:nowrap;">
                            {{ $payment->created_at->format('d M Y') }}
                            <div style="color:var(--muted);">{{ $payment->created_at->format('H:i') }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8"
                            style="text-align:center; color:var(--muted); padding:48px; font-size:14px;">
                            No payments yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border);">
            {{ $payments->withQueryString()->links('components.pagination') }}
        </div>
    @endif
</div>

@endsection