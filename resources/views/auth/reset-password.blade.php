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
            
            <div style="position:relative;">
                 <input type="password" id="password" name="password"
                   class="form-control"
                   placeholder="Min 8 chars, upper + lower + number"
                   required autocomplete="new-password" />
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

        <div class="form-group">
            <label for="password_confirmation">Confirm Password</label>
            
            <div style="position:relative;">
                <input type="password" id="password_confirmation" name="password_confirmation"
                   class="form-control"
                   placeholder="Repeat your new password"
                   required autocomplete="new-password" />
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

        <button type="submit" class="btn-submit" style="width:100%; margin-top:8px;">
            Reset Password
        </button>
    </form>

@endsection