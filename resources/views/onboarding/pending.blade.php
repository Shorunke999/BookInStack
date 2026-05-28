@extends('layouts.auth')

@section('title', 'Verification Pending — BookInStack')

@section('content')

<div style="text-align:center;margin-bottom:32px;">
    <a href="/" style="text-decoration:none;">
        <span style="font-size:22px;font-weight:800;color:#0d0d14;letter-spacing:-.4px;">
            BookIn<span style="color:#4f46e5;">Stack</span>
        </span>
    </a>
</div>

@include('onboarding._progress', ['step' => 3])

@php
    $status   = $onboarding?->kyc_status;
    $isRejected = $status === \App\Enums\KycStatus::Rejected;
    $isError    = $status === \App\Enums\KycStatus::Error;
    $isAwaiting = $status === \App\Enums\KycStatus::AwaitingDocument;
    $isBusiness = $onboarding?->isBusiness();
@endphp

@if($isRejected || $isError)
    {{-- ── Rejected state ─────────────────────────────────────────── --}}
    <div style="text-align:center;padding:8px 0 24px;">
        <div style="font-size:52px;margin-bottom:16px;">❌</div>
        <h2 style="font-size:22px;font-weight:700;color:#0d0d14;margin-bottom:8px;">Verification failed</h2>
        <p style="font-size:14px;color:#64748b;line-height:1.6;margin-bottom:28px;">
            We could not verify your details. Please check the information you submitted and try again.
            If the issue persists, contact us at <a href="mailto:support@bookinstack.com" style="color:#4f46e5;">support@bookinstack.com</a>.
        </p>
        <a href="{{ route('onboarding.' . ($isBusiness ? 'business' : 'individual')) }}"
            style="display:inline-block;padding:12px 28px;background:#4f46e5;color:#fff;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;">
            Try Again →
        </a>
    </div>

@elseif($isAwaiting && $isBusiness)
    {{-- ── Awaiting documents state ────────────────────────────────── --}}
    <div style="text-align:center;padding:8px 0 24px;">
        <div style="font-size:52px;margin-bottom:16px;">📄</div>
        <h2 style="font-size:22px;font-weight:700;color:#0d0d14;margin-bottom:8px;">Additional documents needed</h2>
        <p style="font-size:14px;color:#64748b;line-height:1.6;margin-bottom:20px;">
            Our compliance team needs a few more documents to complete your business verification.
            We've sent an email to <strong>{{ $developer->email }}</strong> with details.
        </p>

        @if($onboarding->kyc_documents_required)
        <div style="text-align:left;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:16px;margin-bottom:24px;">
            <p style="font-size:13px;font-weight:700;color:#92400e;margin-bottom:8px;">Required documents:</p>
            <ul style="list-style:none;padding:0;margin:0;">
                @foreach($onboarding->kyc_documents_required as $doc)
                <li style="font-size:13px;color:#78350f;padding:4px 0;display:flex;align-items:center;gap:8px;">
                    <span style="color:#f59e0b;">●</span> {{ $doc }}
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <p style="font-size:13px;color:#64748b;line-height:1.6;">
            Please email the documents to
            <a href="mailto:shorunke998@gmail.com" style="color:#4f46e5;font-weight:600;">shorunke998@gmail.com</a>
            with your business name in the subject line.
        </p>
    </div>

@else
    {{-- ── Default: under review ───────────────────────────────────── --}}
    <div style="text-align:center;padding:8px 0 24px;">
        <div style="font-size:52px;margin-bottom:16px;">
            {{ $isBusiness ? '🏢' : '🔍' }}
        </div>
        <h2 style="font-size:22px;font-weight:700;color:#0d0d14;margin-bottom:8px;">
            {{ $isBusiness ? 'Business under review' : 'Verifying your identity' }}
        </h2>
        <p style="font-size:14px;color:#64748b;line-height:1.6;margin-bottom:28px;max-width:340px;margin-left:auto;margin-right:auto;">
            @if($isBusiness)
                Our team is reviewing your business documents. This usually takes <strong>24–48 hours</strong>.
                We'll email you at <strong>{{ $developer->email }}</strong> once it's done.
            @else
                We're verifying your identity with Anchor. This is usually instant —
                you'll receive an email at <strong>{{ $developer->email }}</strong> when approved.
            @endif
        </p>

        {{-- Status timeline --}}
        <div style="text-align:left;max-width:300px;margin:0 auto 28px;">
            @php
                $steps = $isBusiness
                    ? [
                        ['Details submitted', true],
                        ['Documents emailed to team', true],
                        ['Manual KYB review', false],
                        ['Payment account created', false],
                    ]
                    : [
                        ['Details submitted', true],
                        ['BVN verification', $status === \App\Enums\KycStatus::Submitted],
                        ['Identity confirmed', false],
                        ['Payment account created', false],
                    ];
            @endphp

            @foreach($steps as [$label, $done])
            <div style="display:flex;align-items:center;gap:12px;padding:8px 0;">
                <div style="
                    width:22px;height:22px;border-radius:50%;flex-shrink:0;
                    display:flex;align-items:center;justify-content:center;
                    background:{{ $done ? '#4f46e5' : '#e2e8f0' }};
                    font-size:11px;color:{{ $done ? '#fff' : '#94a3b8' }};
                ">
                    {{ $done ? '✓' : '·' }}
                </div>
                <span style="font-size:13px;color:{{ $done ? '#0d0d14' : '#94a3b8' }};font-weight:{{ $done ? '600' : '400' }};">
                    {{ $label }}
                </span>
            </div>
            @endforeach
        </div>

        <p style="font-size:12px;color:#94a3b8;line-height:1.6;">
            No action needed. You can safely close this tab.<br>
            We'll notify you by email when your account is ready.
        </p>
    </div>
@endif

{{-- Logout link --}}
<div style="text-align:center;margin-top:24px;padding-top:20px;border-top:1px solid var(--border);">
    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
        @csrf
        <button type="submit" style="background:none;border:none;font-size:13px;color:#94a3b8;cursor:pointer;">
            Sign out
        </button>
    </form>
</div>

@endsection