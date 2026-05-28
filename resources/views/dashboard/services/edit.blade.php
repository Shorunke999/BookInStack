@extends('layouts.app')

@section('title', 'Edit Service — ' . $service->name)
@section('page-title', 'Edit Service')

@section('content')

<div style="max-width:640px;">

    <form method="POST" action="{{ route('services.update', $service) }}">
        @csrf @method('PUT')

        @include('dashboard.services._form', ['service' => $service])

        <div style="display:flex;gap:10px;margin-top:24px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('services.index') }}" class="btn btn-white">Cancel</a>
        </div>
    </form>

    {{-- ── Categories section ──────────────────────────────────────────────── --}}
    <div style="margin-top:40px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:600;">
                Categories
                <span style="font-size:12px;color:var(--muted);font-weight:400;margin-left:6px;">
                    ({{ $service->booking_mode }})
                </span>
            </h3>
            <button onclick="document.getElementById('add-category-form').style.display='block'"
                    class="btn btn-white btn-sm">+ Add Category</button>
        </div>

        {{-- Add category inline form ──────────────────────────────────────── --}}
        <div id="add-category-form" style="display:none;" class="card" style="margin-bottom:16px;">
            <form method="POST" action="{{ route('services.categories.store', $service) }}">
                @csrf
                @include('dashboard.services._category-fields', ['service' => $service, 'category' => null])
                <div style="display:flex;gap:8px;margin-top:16px;">
                    <button type="submit" class="btn btn-primary btn-sm">Add</button>
                    <button type="button" class="btn btn-white btn-sm"
                        onclick="document.getElementById('add-category-form').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>

        {{-- Category list ────────────────────────────────────────────────── --}}
        @forelse($service->bookingCategories()->orderBy('sort_order')->get() as $cat)
            <div class="card" style="margin-bottom:10px;padding:14px 16px;">
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-weight:500;font-size:14px;">{{ $cat->name }}</span>
                            @if($cat->status === 'inactive')
                                <span style="font-size:11px;padding:1px 7px;border-radius:20px;
                                    background:#fef2f2;color:#ef4444;">Inactive</span>
                            @endif
                        </div>
                        @if($cat->price)
                            <span style="font-size:12px;color:var(--muted);">
                                ₦{{ number_format($cat->price / 100, 2) }}
                            </span>
                        @endif
                    </div>

                    <div style="display:flex;gap:6px;">
                        {{-- Toggle --}}
                        <form method="POST"
                              action="{{ route('services.categories.toggle', [$service, $cat]) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-white btn-sm"
                                style="{{ $cat->status === 'active' ? 'color:#ef4444;' : '' }}">
                                {{ $cat->status === 'active' ? 'Disable' : 'Enable' }}
                            </button>
                        </form>

                        {{-- Delete --}}
                        <form method="POST"
                              action="{{ route('services.categories.destroy', [$service, $cat]) }}"
                              onsubmit="return confirm('Delete this category?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-white btn-sm" style="color:#ef4444;">✕</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <p style="color:var(--muted);font-size:13px;">No categories yet. Add one above.</p>
        @endforelse
    </div>

</div>

@endsection