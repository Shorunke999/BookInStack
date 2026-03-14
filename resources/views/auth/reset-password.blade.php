@extends('layouts.auth')
@section('title', 'Reset Password')

@section('content')

    <h2>Set new password</h2>
    <p class="subtitle">Choose a strong password for your account.</p>

    @if($errors->any())
        <div class="alert alert-error" style="margin-bottom:16px;">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}" />

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   class="form-control"
                   value="{{ old('email', $email) }}"
                   placeholder="you@example.com"
                   required />
            @error('email')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password"
                   class="form-control"
                   placeholder="Min 8 chars, upper + lower + number"
                   required autocomplete="new-password" />
            @error('password')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   class="form-control"
                   placeholder="Repeat your new password"
                   required autocomplete="new-password" />
        </div>

        <button type="submit" class="btn-block" style="width:100%; margin-top:8px;">
            Reset Password
        </button>
    </form>

@endsection