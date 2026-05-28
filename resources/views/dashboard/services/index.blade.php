@extends('layouts.app')

@section('title', 'Services')
@section('page-title', 'Services')

@section('content')

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
    <p style="color:var(--muted); font-size:14px;">
        Each service has its own booking mode, categories, and settings.
    </p>
    <a href="{{ route('services.create') }}" class="btn btn-primary">+ New Service</a>
</div>

@if($services->isEmpty())
    <div class="card" style="text-align:center; padding:60px 24px;">
        <div style="font-size:40px; margin-bottom:12px;">📦</div>
        <h3 style="margin-bottom:8px;">No services yet</h3>
        <p style="color:var(--muted); margin-bottom:20px; font-size:14px;">
            Create a service to define a booking mode and start accepting bookings.
        </p>
        <a href="{{ route('services.create') }}" class="btn btn-primary">Create your first service</a>
    </div>
@else
    <div style="display:grid; gap:14px;">
        @foreach($services as $service)
            @php $isActive = $developer->active_service_id === $service->id; @endphp
            <div class="card" style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;
                {{ $isActive ? 'border:1.5px solid var(--accent);' : '' }}">

                {{-- Mode icon ──────────────────────────────────────────────── --}}
                <div style="width:42px;height:42px;border-radius:10px;flex-shrink:0;
                    background:{{ $service->modeBadgeColor() }}1a;
                    display:flex;align-items:center;justify-content:center;">
                    @if($service->booking_mode === 'ticket')
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="{{ $service->modeBadgeColor() }}" stroke-width="2">
                            <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/>
                        </svg>
                    @elseif($service->booking_mode === 'reservation')
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="{{ $service->modeBadgeColor() }}" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    @else
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="{{ $service->modeBadgeColor() }}" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    @endif
                </div>

                {{-- Info ───────────────────────────────────────────────────── --}}
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span style="font-weight:600;font-size:15px;">{{ $service->name }}</span>

                        <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;
                            background:{{ $service->modeBadgeColor() }}1a;color:{{ $service->modeBadgeColor() }};">
                            {{ $service->modeLabel() }}
                        </span>

                        @if($isActive)
                            <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;
                                background:var(--accent-light);color:var(--accent);">● Active</span>
                        @endif

                        @if($service->status === 'inactive')
                            <span style="font-size:11px;padding:2px 8px;border-radius:20px;
                                background:#fef2f2;color:#ef4444;">Inactive</span>
                        @endif
                    </div>

                    @if($service->description)
                        <p style="font-size:13px;color:var(--muted);margin-top:3px;
                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $service->description }}
                        </p>
                    @endif

                    <div style="font-size:12px;color:var(--muted);margin-top:4px;">
                        {{ $service->booking_categories_count ?? 0 }} categor{{ ($service->booking_categories_count ?? 0) === 1 ? 'y' : 'ies' }}
                        &nbsp;·&nbsp;
                        {{ $service->bookings_count ?? 0 }} booking{{ ($service->bookings_count ?? 0) === 1 ? '' : 's' }}
                    </div>
                </div>

                {{-- Actions ────────────────────────────────────────────────── --}}
                <div style="display:flex;gap:8px;flex-shrink:0;flex-wrap:wrap;">
                    @unless($isActive)
                        <form method="POST" action="{{ route('services.activate', $service) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">Switch to</button>
                        </form>
                    @endunless

                    <a href="{{ route('services.edit', $service) }}" class="btn btn-white btn-sm">Edit</a>

                    <form method="POST" action="{{ route('services.toggle', $service) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-white btn-sm"
                            style="{{ $service->status === 'active' ? 'color:#ef4444;' : '' }}">
                            {{ $service->status === 'active' ? 'Disable' : 'Enable' }}
                        </button>
                    </form>

                    @unless($isActive)
                        <form method="POST" action="{{ route('services.destroy', $service) }}"
                              onsubmit="return confirm('Delete \'{{ addslashes($service->name) }}\'? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-white btn-sm" style="color:#ef4444;">Delete</button>
                        </form>
                    @endunless
                </div>

            </div>
        @endforeach
    </div>
@endif

@endsection