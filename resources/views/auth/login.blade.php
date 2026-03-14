@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')

    <h2>Welcome back</h2>
    <p class="subtitle">Sign in to your developer account</p>

    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

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
                placeholder="••••••••"
                autocomplete="current-password"
                required
            />
            @error('password')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                <input type="checkbox" name="remember" style="accent-color:#4f46e5;" />
                Remember me
            </label>
            @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}" style="font-size:13px; color:#4f46e5;">Forgot password?</a>
            @endif
        </div>

        <button type="submit" class="btn-block">Sign In</button>
    </form>

    <div class="auth-footer">
        No account? <a href="{{ route('register') }}">Create one free</a>
    </div>

@endsection
