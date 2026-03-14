@extends('layouts.app')
@section('title', 'Edit Staff')
@section('page-title', 'Edit Staff Member')

@section('content')
<div style="max-width:500px;">
    <div class="card">

        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('staff.update', $staff->id) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name"
                       class="form-control"
                       value="{{ old('name', $staff->name) }}" required />
                @error('name')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       class="form-control"
                       value="{{ old('email', $staff->email) }}" required />
                @error('email')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password"
                       class="form-control" placeholder="Leave blank to keep current" />
                <div style="font-size:12px; color:var(--muted); margin-top:4px;">
                    Only fill this in if you want to change their password.
                </div>
                @error('password')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation"
                       name="password_confirmation"
                       class="form-control" placeholder="Repeat new password" />
            </div>

            <div style="display:flex; gap:12px; margin-top:8px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('staff.index') }}" class="btn btn-outline">Cancel</a>
            </div>

        </form>
    </div>
</div>
@endsection