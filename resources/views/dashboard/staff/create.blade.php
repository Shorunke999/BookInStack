@extends('layouts.app')
@section('title', 'Add Staff')
@section('page-title', 'Add Staff Member')

@section('content')
<div style="max-width:500px;">
    <div class="card">

        <p style="color:var(--muted); font-size:14px; margin:0 0 24px;">
            Create a login for a staff member. They will be able to view bookings,
            payments and mark attendance — but cannot access API keys or settings.
        </p>

        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('staff.store') }}">
            @csrf

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name"
                       class="form-control" value="{{ old('name') }}"
                       placeholder="Jane Doe" required />
                @error('name')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       class="form-control" value="{{ old('email') }}"
                       placeholder="jane@yourbusiness.com" required />
                @error('email')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       class="form-control" placeholder="Min 8 characters"
                       required />
                <div style="font-size:12px; color:var(--muted); margin-top:4px;">
                    Share this password with the staff member securely.
                    They can change it after logging in.
                </div>
                @error('password')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation"
                       name="password_confirmation"
                       class="form-control" placeholder="Repeat password" required />
            </div>

            <div style="display:flex; gap:12px; margin-top:8px;">
                <button type="submit" class="btn btn-primary">Create Staff Account</button>
                <a href="{{ route('staff.index') }}" class="btn btn-outline">Cancel</a>
            </div>

        </form>
    </div>
</div>
@endsection