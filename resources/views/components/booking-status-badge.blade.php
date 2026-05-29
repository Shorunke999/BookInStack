{{--
    Component: status-badge
    Props: $status (string)
--}}
@php
    $classes = match($booking_status) {
        'completed' => 'badge-green',
        'checked_in'                   => 'badge-yellow',
        'active'                    => 'badge-gray',
    };
@endphp

<span class="badge {{ $classes }}">{{ $booking_status }}</span>
