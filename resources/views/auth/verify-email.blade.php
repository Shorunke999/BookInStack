@extends('layouts.auth')
@section('title', 'Verify Your Email')

@section('content')

    <div style="text-align:center; margin-bottom:28px;">
        <div style="font-size:48px; margin-bottom:12px;">📬</div>
        <h2 style="margin-bottom:6px;">Check your inbox</h2>
        <p class="subtitle">
            We sent a verification link to<br />
            <strong>{{ $email ?? 'your email address' }}</strong>
        </p>
    </div>

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

    <p style="font-size:13px; color:var(--muted); text-align:center; margin-bottom:20px; line-height:1.6;">
        Click the link in the email to activate your account.
        It may take a minute to arrive. Check your spam folder if you don't see it.
    </p>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        @if($email)
            <input type="hidden" name="email" value="{{ $email }}" />
        @endif
        <button type="submit" class="btn-block" style="width:100%;">
            Resend Verification Email
        </button>
    </form>

    <div style="text-align:center; margin-top:20px;">
        <a href="{{ route('login') }}" style="font-size:13px; color:var(--muted);">
            ← Back to login
        </a>
    </div>

@endsection