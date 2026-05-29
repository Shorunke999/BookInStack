<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // New: JSON array of active service IDs
            $table->string('paystack_reference')->nullable();
              $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded'
            ])->default('pending')->after('status');

            $table->enum('booking_status', [
                'active',
                'checked_in',
                'completed',
                'cancelled',
                'expired',
                'no_show'
            ])->default('active')->after('payment_status');
        });
           // ── Migrate old data safely ─────────────────────────────
        DB::table('bookings')->orderBy('id')->chunkById(200, function ($bookings) {

            foreach ($bookings as $booking) {

                // OLD: status column
                $status = $booking->status;

                $paymentStatus = match ($status) {
                    'pending'  => 'pending',
                    'paid'     => 'paid',
                    'failed'   => 'failed',
                    'refunded' => 'refunded',
                    default    => 'pending',
                };

                // Default booking status mapping
                $bookingStatus = match ($status) {
                    'paid'     => 'active',
                    'pending'  => 'active',
                    'failed'   => 'cancelled',
                    'refunded' => 'cancelled',
                    'cancelled'=> 'cancelled',
                    default    => 'active',
                };

                DB::table('bookings')
                    ->where('id', $booking->id)
                    ->update([
                        'payment_status' => $paymentStatus,
                        'booking_status' => $bookingStatus,
                    ]);
            }
        });

        Schema::table('services', function (Blueprint $table) {
            $table->boolean('enable_sms_notification')->default(false);
            $table->string('sms_number')->nullable();
        });
          // Optional: drop old column after migration is confirmed working
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['paystack_reference','payment_status', 'booking_status']);
              $table->enum('status', [
                'pending',
                'paid',
                'failed',
                'cancelled',
                'refunded'
            ])->default('pending');
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled', 'refunded'])
                ->default('pending');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('enable_sms_notification');
            $table->dropColumn('sms_number');
        });
    }
};
