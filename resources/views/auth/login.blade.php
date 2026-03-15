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
            
            <div style="position:relative;">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                />
                <button type="button"
                        onclick="const i=document.getElementById('password'); const show=i.type==='password'; i.type=show?'text':'password'; this.innerHTML=show?'<svg width=18 height=18 viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><path d=\'M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94\'/><path d=\'M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19\'/><line x1=\'1\' y1=\'1\' x2=\'23\' y2=\'23\'/></svg>':'<svg width=18 height=18 viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><path d=\'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z\'/><circle cx=\'12\' cy=\'12\' r=\'3\'/></svg>'"
                        style="position:absolute; right:10px; top:50%; transform:translateY(-50%);
                            background:none; border:none; cursor:pointer; color:var(--muted); padding:0; display:flex;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
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
