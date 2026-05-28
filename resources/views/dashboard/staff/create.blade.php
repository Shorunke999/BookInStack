@extends('layouts.app')
@section('title', 'Add Staff')
@section('page-title', 'Add Staff Member')

@section('content')
<div style="max-width:520px;">
    <div class="card">

        <p style="color:var(--muted);font-size:14px;margin:0 0 24px;">
            Create a login for a staff member. Assign them to one or more services —
            they will only see bookings for those services.
        </p>

        @if($errors->any())
            <div class="alert alert-error" style="margin-bottom:16px;">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('staff.store') }}">
            @csrf

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control"
                       value="{{ old('name') }}" placeholder="Jane Doe" required>
                @error('name') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="{{ old('email') }}" placeholder="jane@yourbusiness.com" required>
                @error('email') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Min 8 characters" required>
                <div style="font-size:12px;color:var(--muted);margin-top:4px;">
                    Share this password securely. The staff member can change it after logging in.
                </div>
                @error('password') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="form-control" placeholder="Repeat password" required>
            </div>

            {{-- Service assignments ───────────────────────────────────────────── --}}
            <div class="form-group" style="margin-top:20px;">
                <label style="font-weight:600;font-size:14px;display:block;margin-bottom:8px;">
                    Assign to Services
                </label>
                <div style="font-size:13px;color:var(--muted);margin-bottom:12px;">
                    Staff will only see bookings for selected services.
                </div>

                @if($services->isEmpty())
                    <div style="padding:12px;background:var(--soft);border-radius:8px;
                                font-size:13px;color:var(--muted);">
                        No active services yet.
                        <a href="{{ route('dashboard.services.create') }}" style="color:var(--accent);">
                            Create one first →
                        </a>
                    </div>
                @else
                    <div style="display:grid;gap:8px;">
                        @foreach($services as $service)
                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;
                                padding:10px 12px;border-radius:9px;border:1px solid var(--border);
                                background:#fff;transition:border-color .15s;"
                                onmouseover="this.style.borderColor='var(--accent)'"
                                onmouseout="this.style.borderColor='var(--border)'">
                                <input type="checkbox" name="service_ids[]"
                                       value="{{ $service->id }}"
                                       {{ in_array($service->id, old('service_ids', [])) ? 'checked' : '' }}
                                       style="width:16px;height:16px;accent-color:var(--accent);">
                                <div style="flex:1;">
                                    <div style="font-weight:500;font-size:14px;">{{ $service->name }}</div>
                                    @if($service->description)
                                        <div style="font-size:12px;color:var(--muted);">{{ $service->description }}</div>
                                    @endif
                                </div>
                                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;
                                    background:{{ $service->modeBadgeColor() }}1a;
                                    color:{{ $service->modeBadgeColor() }};">
                                    {{ $service->modeLabel() }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('service_ids') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="btn btn-primary">Create Staff Account</button>
                <a href="{{ route('staff.index') }}" class="btn btn-outline">Cancel</a>
            </div>

        </form>
    </div>
</div>
@endsection
