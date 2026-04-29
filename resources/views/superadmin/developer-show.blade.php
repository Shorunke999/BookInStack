@extends('layouts.app')
@section('title', $developer->business_name ?? $developer->name)
@section('page-title', $developer->business_name ?? $developer->name)

@section('content')


<div style="margin-bottom:16px;">
    <a href="{{ route('superadmin.developers') }}" style="font-size:13px;color:var(--muted);">← Back to Businessess</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

    {{-- Developer info --}}
    <div class="card">
        <h3 style="font-size:14px;margin-bottom:14px;">Account</h3>
        @foreach([
            ['Name',         $developer->name],
            ['Email',        $developer->email],
            ['Business',     $developer->business_name ?? '—'],
            ['Mode',         ucfirst($developer->booking_mode ?? '—')],
            ['Public Key',   $developer->public_key ?? '—'],
            ['Subaccount',   $developer->paystack_subaccount_code ?? '—'],
            ['Joined',       $developer->created_at->format('d M Y')],
        ] as [$k,$v])
            <div style="display:flex;gap:12px;font-size:13px;padding:8px 0;border-bottom:1px solid var(--border);">
                <span style="min-width:100px;font-weight:600;color:var(--muted);">{{ $k }}</span>
                <span style="word-break:break-all;font-family:{{ in_array($k,['Public Key','Subaccount'])?'monospace':'inherit' }};font-size:{{ in_array($k,['Public Key','Subaccount'])?'11px':'13px' }};">{{ $v }}</span>
            </div>
        @endforeach
    </div>

    {{-- Fee + status controls --}}
    <div>
        {{-- Platform fee --}}
        <div class="card" style="margin-bottom:16px;">
            <h3 style="font-size:14px;margin-bottom:14px;">Platform Fee</h3>
            <form method="POST" action="{{ route('superadmin.developers.fee', $developer->id) }}">
                @csrf @method('PATCH')
                <div class="form-group">
                    <label>Fee Percentage</label>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="number" name="platform_fee_percent"
                               value="{{ number_format($developer->platform_fee_percent ?? 5, 2) }}"
                               min="0" max="50" step="0.01" class="form-control"
                               style="max-width:120px;" />
                        <span style="font-size:14px;font-weight:700;color:var(--muted);">%</span>
                    </div>
                    <div style="font-size:11px;color:var(--muted);margin-top:4px;">
                        Default: {{ config('services.bookinstack.default_platform_fee', 5.00) }}%
                    </div>
                </div>

                <div style="padding:12px 14px;background:var(--soft);border-radius:8px;margin-bottom:14px;">
                    @php $fee = $developer->platform_fee_percent ?? 5.00; @endphp
                    <div style="font-size:12px;color:var(--muted);margin-bottom:6px;">On a ₦100,000 booking:</div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;">
                        <span>BookInStack earns</span>
                        <strong style="color:var(--accent);">₦{{ number_format(100000 * $fee / 100, 2) }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-top:4px;">
                        <span>Businesses receives</span>
                        <strong>₦{{ number_format(100000 * (1 - $fee/100), 2) }}</strong>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">Update Fee</button>
            </form>
        </div>

        {{-- Status --}}
        <div class="card">
            <h3 style="font-size:14px;margin-bottom:14px;">Account Status</h3>
            <form method="POST" action="{{ route('superadmin.developers.status', $developer->id) }}">
                @csrf @method('PATCH')
                <div class="form-group" style="margin-bottom:14px;">
                    <select name="status" class="form-control" style="max-width:180px;">
                        @foreach(['active','pending','suspended'] as $s)
                            <option value="{{ $s }}" {{ $developer->status===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-outline btn-sm">Update Status</button>
            </form>
        </div>
    </div>
</div>

{{-- Revenue summary --}}
<div class="card" style="margin-bottom:20px;">
    <h3 style="font-size:14px;margin-bottom:14px;">Revenue Summary</h3>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
        @foreach([
            ['Total Volume','₦'.number_format($totalPaid/100,2),'💰'],
            ['Platform Earned','₦'.number_format($platformEarned/100,2),'🏦'],
            ['Businesses Received','₦'.number_format(($totalPaid-$platformEarned)/100,2),'💳'],
        ] as [$label,$value,$icon])
            <div style="text-align:center;padding:16px;background:var(--soft);border-radius:8px;">
                <div style="font-size:24px;margin-bottom:6px;">{{ $icon }}</div>
                <div style="font-size:18px;font-weight:800;">{{ $value }}</div>
                <div style="font-size:11px;color:var(--muted);margin-top:4px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;">{{ $label }}</div>
            </div>
        @endforeach
    </div>
</div>

{{-- Recent bookings --}}
<div class="card" style="padding:0;overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
        <h3 style="font-size:14px;">Recent Paid Bookings</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th style="text-align:right;">Amount</th>
                    <th style="text-align:right;">Platform Fee</th>
                    <th>Paid At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $b)
                    @php $fee = (int) round($b->amount * ($developer->platform_fee_percent / 100)); @endphp
                    <tr>
                        <td style="font-family:monospace;font-size:12px;">{{ $b->reference }}</td>
                        <td>
                            <div style="font-size:13px;font-weight:600;">{{ $b->customer_name ?? '—' }}</div>
                            <div style="font-size:11px;color:var(--muted);">{{ $b->customer_email }}</div>
                        </td>
                        <td style="text-align:right;font-weight:700;font-size:13px;">₦{{ number_format($b->amount/100,2) }}</td>
                        <td style="text-align:right;font-size:13px;color:var(--accent);">₦{{ number_format($fee/100,2) }}</td>
                        <td style="font-size:12px;color:var(--muted);">{{ $b->paid_at?->format('d M Y, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--muted);">No paid bookings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection