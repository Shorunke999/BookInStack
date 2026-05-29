{{--
    Component: status-badge
    Props: $status (string)
--}}
@php
    $classes = match($payment_status) {
        'paid', 'success', 'active' => 'badge-green',
        'pending'                   => 'badge-yellow',
        'failed', 'cancelled'       => 'badge-red',
        default                     => 'badge-gray',
    };
@endphp

<span class="badge {{ $classes }}">{{ $payment_status }}</span>
