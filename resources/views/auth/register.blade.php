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
            
            <div style="position:relative;">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="8+ characters"
                    autocomplete="new-password"
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

        <div class="form-group" style="margin-bottom:20px;">
            <label for="password_confirmation">Confirm Password</label>
           
            <div style="position:relative;">
                 <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                class="form-control"
                placeholder="Repeat password"
                autocomplete="new-password"
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
        </div>
        {{-- Terms + Privacy agreement --}}
        <div style="margin-bottom:16px;">
            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                <input type="checkbox" name="terms" value="1"
                    style="width:16px;height:16px;margin-top:2px;accent-color:#4f46e5;flex-shrink:0;cursor:pointer;"
                    {{ old('terms') ? 'checked' : '' }}
                    required />
                <span style="font-size:13px;color:#64748b;line-height:1.5;">
                    I agree to the
                    <a href="{{ route('terms') }}" target="_blank"
                    style="color:#4f46e5;text-decoration:none;font-weight:600;">Terms of Service</a>
                    and
                    <a href="{{ route('privacy') }}" target="_blank"
                    style="color:#4f46e5;text-decoration:none;font-weight:600;">Privacy Policy</a>.
                    I confirm that I am at least 18 years old.
                </span>
            </label>
            @error('terms')
                <div style="color:#ef4444;font-size:12px;margin-top:4px;margin-left:26px;">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="btn-submit">Create Account</button>
    </form>

    <div class="auth-footer">
        Already have an account? <a href="{{ route('login') }}">Sign in</a>
    </div>

@endsection
