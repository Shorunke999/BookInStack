@extends('layouts.app')
@section('title', 'Payment Links')
@section('page-title', 'Payment Links')

@section('content')

<div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Description</th>
                    <th style="text-align:right;">Amount</th>
                    <th>Status</th>
                    <th class="hide-mobile">Expires</th>
                    <th class="hide-mobile">Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($links as $link)
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:13px;">{{ $link->customer_name }}</div>
                        <div style="font-size:11px;color:var(--muted);">{{ $link->customer_email }}</div>
                    </td>
                    <td style="font-size:13px;">{{ $link->category?->name ?? $link->description }}</td>
                    <td style="text-align:right;font-weight:700;font-size:13px;white-space:nowrap;">{{ $link->formattedAmount() }}</td>
                    <td>
                        @php $sc = ['pending'=>'background:#fffbeb;color:#d97706;','paid'=>'background:#f0fdf4;color:#15803d;','expired'=>'background:#f3f4f6;color:#6b7280;','cancelled'=>'background:#fef2f2;color:#ef4444;']; @endphp
                        <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px;{{ $sc[$link->status] ?? '' }}">{{ ucfirst($link->status) }}</span>
                    </td>
                    <td class="hide-mobile" style="font-size:12px;color:var(--muted);">
                        {{ $link->expires_at ? $link->expires_at->format('d M, H:i') : '—' }}
                    </td>
                    <td class="hide-mobile" style="font-size:12px;">{{ $link->created_at->format('d M Y') }}</td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="{{ route('payment-links.show-dashboard', $link->token) }}"
                               class="btn btn-outline btn-sm" style="font-size:11px;padding:3px 10px;">View</a>
                            @if($link->status === 'pending')
                                <form method="POST" action="{{ route('payment-links.cancel', $link->token) }}" style="margin:0;" onsubmit="return confirm('Cancel this link?')">
                                    @csrf
                                    <button type="submit" class="btn btn-outline btn-sm" style="font-size:11px;padding:3px 10px;color:#ef4444;border-color:#fecaca;">Cancel</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:48px;color:var(--muted);font-size:14px;">
                        No payment links yet. <a href="{{ route('payment-links.create') }}" style="color:var(--accent);">Create your first →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($links->hasPages())
        <div style="padding:14px 20px;border-top:1px solid var(--border);">{{ $links->links('components.pagination') }}</div>
    @endif
</div>

@endsection