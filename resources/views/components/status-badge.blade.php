{{--
    Component: status-badge
    Props: $status (string)
--}}
@php
    $classes = match($status) {
        'paid', 'success', 'active' => 'badge-green',
        'pending'                   => 'badge-yellow',
        'failed', 'cancelled'       => 'badge-red',
        default                     => 'badge-gray',
    };
@endphp

<span class="badge {{ $classes }}">{{ $status }}</span>
