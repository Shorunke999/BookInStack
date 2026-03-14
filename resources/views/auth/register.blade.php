@extends('layouts.auth')

@section('title', 'Create Account')

@section('content')

    <h2>Create your account</h2>
    <p class="subtitle">Start accepting bookings and payments in minutes</p>

    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="form-group">
            <label for="name">Full Name</label>
            <input
                type="text"
                id="name"
                name="name"
                class="form-control"
                value="{{ old('name') }}"
                placeholder="John Doe"
                autocomplete="name"
                required
            />
            @error('name')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="business_name">Business Name</label>
            <input
                type="text"
                id="business_name"
                name="business_name"
                class="form-control"
                value="{{ old('business_name') }}"
                placeholder="Acme Consulting"
                required
            />
            @error('business_name')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                value="{{ old('email') }}"
                placeholder="you@example.com"
                autocomplete="email"
                required
            />
            @error('email')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                class="form-control"
                placeholder="8+ characters"
                autocomplete="new-password"
                required
            />
            @error('password')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group" style="margin-bottom:20px;">
            <label for="password_confirmation">Confirm Password</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                class="form-control"
                placeholder="Repeat password"
                autocomplete="new-password"
                required
            />
        </div>

        <button type="submit" class="btn-block">Create Account</button>
    </form>

    <div class="auth-footer">
        Already have an account? <a href="{{ route('login') }}">Sign in</a>
    </div>

@endsection
