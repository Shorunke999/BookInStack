<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_categories', function (Blueprint $table) {
            // Ticket mode — fixed date+time window for when staff can check in attendees
            $table->date('checkin_start_date')->nullable()->after('total_slots');
            $table->date('checkin_end_date')->nullable()->after('checkin_start_date');
            $table->time('checkin_start_time')->nullable()->after('checkin_end_date');
            $table->time('checkin_end_time')->nullable()->after('checkin_start_time');

            // Reservation + appointment — relative days around the booking date
            // e.g. checkin_days_before=1 allows check-in the day before
            $table->unsignedSmallInteger('checkin_days_before')->default(0)->after('checkin_end_time');
            $table->unsignedSmallInteger('checkin_days_after')->default(0)->after('checkin_days_before');
        });
    }

    public function down(): void
    {
        Schema::table('booking_categories', function (Blueprint $table) {
            $table->dropColumn([
                'checkin_start_date', 'checkin_end_date',
                'checkin_start_time', 'checkin_end_time',
                'checkin_days_before', 'checkin_days_after',
            ]);
        });
    }
};