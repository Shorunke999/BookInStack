{{-- resources/views/onboarding/_progress.blade.php --}}
{{-- Usage: @include('onboarding._progress', ['step' => 1]) --}}

@php
    $steps = [
        1 => 'Account Type',
        2 => 'Verification',
        3 => 'Under Review',
    ];
@endphp

<div style="margin-bottom:28px;">
    {{-- Step labels --}}
    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
        @foreach($steps as $n => $label)
            <span style="font-size:11px;font-weight:600;color:{{ $n <= $step ? '#4f46e5' : '#94a3b8' }};text-align:center;flex:1;">
                {{ $label }}
            </span>
        @endforeach
    </div>

    {{-- Bar --}}
    <div style="display:flex;gap:4px;">
        @foreach($steps as $n => $label)
            <div style="flex:1;height:4px;border-radius:99px;background:{{ $n <= $step ? '#4f46e5' : '#e2e8f0' }};transition:background .3s;"></div>
        @endforeach
    </div>
</div>