@extends('layouts.app')
@section('title', 'Edit Staff')
@section('page-title', 'Edit Staff Member')

@section('content')
<div style="max-width:520px;">
    <div class="card">

        @if($errors->any())
            <div class="alert alert-error" style="margin-bottom:16px;">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('staff.update', $staff->id) }}">
            @csrf @method('PUT')

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control"
                       value="{{ old('name', $staff->name) }}" required>
                @error('name') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="{{ old('email', $staff->email) }}" required>
                @error('email') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Leave blank to keep current">
                <div style="font-size:12px;color:var(--muted);margin-top:4px;">
                    Only fill this in if you want to change their password.
                </div>
                @error('password') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="form-control" placeholder="Repeat new password">
            </div>

            {{-- Service assignments ───────────────────────────────────────────── --}}
            <div class="form-group" style="margin-top:20px;">
                <label style="font-weight:600;font-size:14px;display:block;margin-bottom:8px;">
                    Assigned Services
                </label>
                <div style="font-size:13px;color:var(--muted);margin-bottom:12px;">
                    Staff will only see bookings for selected services.
                </div>

                @php $assignedIds = old('service_ids', $staff->assignedServices->pluck('id')->toArray()); @endphp

                @if($services->isEmpty())
                    <div style="padding:12px;background:var(--soft);border-radius:8px;
                                font-size:13px;color:var(--muted);">
                        No active services yet.
                    </div>
                @else
                    <div style="display:grid;gap:8px;">
                        @foreach($services as $service)
                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;
                                padding:10px 12px;border-radius:9px;
                                border:1px solid {{ in_array($service->id, $assignedIds) ? 'var(--accent)' : 'var(--border)' }};
                                background:#fff;transition:border-color .15s;"
                                onmouseover="this.style.borderColor='var(--accent)'"
                                onmouseout="this.style.borderColor=this.querySelector('input').checked ? 'var(--accent)' : 'var(--border)'">
                                <input type="checkbox" name="service_ids[]"
                                       value="{{ $service->id }}"
                                       {{ in_array($service->id, $assignedIds) ? 'checked' : '' }}
                                       style="width:16px;height:16px;accent-color:var(--accent);"
                                       onchange="this.closest('label').style.borderColor=this.checked?'var(--accent)':'var(--border)'">
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
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('staff.index') }}" class="btn btn-outline">Cancel</a>
            </div>

        </form>
    </div>
</div>
@endsection
