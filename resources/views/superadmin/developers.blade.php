@extends('layouts.app')
@section('title','Developers')
@section('page-title','Developers')

@section('content')


{{-- Search + filter --}}
<form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
           placeholder="Search name, email, business…" style="max-width:280px;" />
    <select name="status" class="form-control" style="max-width:140px;" onchange="this.form.submit()">
        <option value="">All statuses</option>
        @foreach(['active','pending','suspended'] as $s)
            <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-outline btn-sm">Search</button>
    @if(request()->hasAny(['search','status']))
        <a href="{{ route('superadmin.developers') }}" class="btn btn-outline btn-sm">Clear</a>
    @endif
</form>

<div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Developer</th>
                    <th>Mode</th>
                    <th style="text-align:right;">Volume</th>
                    <th style="text-align:center;">Fee %</th>
                    <th style="text-align:right;">Earned</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($developers as $dev)
                    @php
                        $volume  = $dev->paid_amount ?? 0;
                        $earned  = (int) round($volume * ($dev->platform_fee_percent / 100));
                        $sc      = ['active'=>['#15803d','#f0fdf4'],'pending'=>['#d97706','#fffbeb'],'suspended'=>['#ef4444','#fef2f2']];
                        [$c,$bg] = $sc[$dev->status] ?? ['#6b7280','#f3f4f6'];
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:13px;">{{ $dev->business_name ?? $dev->name }}</div>
                            <div style="font-size:11px;color:var(--muted);">{{ $dev->email }}</div>
                        </td>
                        <td style="font-size:12px;color:var(--muted);">{{ ucfirst($dev->booking_mode ?? '—') }}</td>
                        <td style="text-align:right;font-size:13px;font-weight:600;">₦{{ number_format($volume/100,2) }}</td>

                        {{-- Inline fee edit --}}
                        <td style="text-align:center;">
                            <form method="POST" action="{{ route('superadmin.developers.fee', $dev->id) }}"
                                  style="display:inline-flex;align-items:center;gap:4px;">
                                @csrf @method('PATCH')
                                <input type="number" name="platform_fee_percent"
                                       value="{{ number_format($dev->platform_fee_percent ?? 5, 2) }}"
                                       min="0" max="50" step="0.01"
                                       style="width:64px;padding:3px 6px;border:1px solid var(--border);border-radius:5px;font-size:12px;font-family:inherit;text-align:center;" />
                                <span style="font-size:12px;color:var(--muted);">%</span>
                                <button type="submit" class="btn btn-outline btn-sm"
                                        style="font-size:11px;padding:2px 8px;">✓</button>
                            </form>
                        </td>

                        <td style="text-align:right;font-size:13px;font-weight:700;color:var(--accent);">₦{{ number_format($earned/100,2) }}</td>

                        {{-- Inline status change --}}
                        <td>
                            <form method="POST" action="{{ route('superadmin.developers.status', $dev->id) }}"
                                  style="margin:0;">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()"
                                        style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:10px;border:1px solid;cursor:pointer;background:{{ $bg }};color:{{ $c }};border-color:{{ $c }}22;">
                                    @foreach(['active','pending','suspended'] as $s)
                                        <option value="{{ $s }}" {{ $dev->status===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>

                        <td><a href="{{ route('superadmin.developers.show', $dev->id) }}" style="font-size:12px;color:var(--accent);">Details →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">No developers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($developers->hasPages())
        <div style="padding:14px 20px;border-top:1px solid var(--border);">{{ $developers->links('components.pagination') }}</div>
    @endif
</div>

@endsection