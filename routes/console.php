<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Booking;

Schedule::call(function () {
    $bookings = \App\Models\Booking::where('status', 'pending')
        ->where('booking_expires_at', '<', now())->get();
    foreach($bookings as $booking)
    {
        $booking->delete();
    }
})->everyFiveMinutes();