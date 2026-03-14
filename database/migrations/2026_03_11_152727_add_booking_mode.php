<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Add booking_mode to developers ────────────────────────────────────
        Schema::table('developers', function (Blueprint $table) {
            $table->enum('booking_mode', ['appointment', 'ticket', 'reservation'])
                ->default('appointment')
                ->after('booking_window');
        });

        // ── Add mode-specific metadata columns to bookings ────────────────────
        Schema::table('bookings', function (Blueprint $table) {
            // Shared mode metadata (ticket, reservation, appointment extras)
            $table->unsignedSmallInteger('quantity')->default(1)->after('description');
            $table->date('check_in')->nullable()->after('quantity');
            $table->date('check_out')->nullable()->after('check_in');
            $table->date('preferred_date')->nullable()->after('check_out');
            $table->time('preferred_time')->nullable()->after('preferred_date');
        });
    }

    public function down(): void
    {
        Schema::table('developers', function (Blueprint $table) {
            $table->dropColumn('booking_mode');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'check_in', 'check_out', 'preferred_date', 'preferred_time']);
        });
    }
};
