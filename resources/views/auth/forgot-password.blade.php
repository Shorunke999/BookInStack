@extends('layouts.auth')
@section('title', 'Forgot Password')

@section('content')

    <h2>Reset your password</h2>
    <p class="subtitle">Enter your email and we'll send you a reset link.</p>

    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom:16px;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error" style="margin-bottom:16px;">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   class="form-control"
                   value="{{ old('email') }}"
                   placeholder="you@example.com"
                   required autofocus />
            @error('email')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn-submit">
            Send Reset Link
        </button>
    </form>

    <div style="text-align:center; margin-top:20px;">
        <a href="{{ route('login') }}" style="font-size:13px; color:var(--muted);">
            ← Back to login
        </a>
    </div>

@endsection