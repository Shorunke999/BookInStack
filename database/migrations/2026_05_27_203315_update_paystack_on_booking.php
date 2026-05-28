<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // New: JSON array of active service IDs
            $table->string('paystack_reference')->nullable();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('sms_number')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('paystack_reference');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('sms_number');
        });
    }
};
