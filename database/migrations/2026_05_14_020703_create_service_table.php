<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->cascadeOnDelete();

            $table->string('name');                          // e.g. "Standard Rooms", "Weekend Events"
            $table->string('slug')->nullable();              // used in widget embed
            $table->text('description')->nullable();
            $table->string('booking_mode');                  // appointment | ticket | reservation
            $table->string('status')->default('active');     // active | inactive

            // ── Mode-specific settings (mirrors what was on Developer) ──────────
            $table->string('reservation_unit')->default('night');   // night | day
            $table->boolean('enable_negotiate')->default(false);
            $table->string('whatsapp_number')->nullable();

            // Booking window
            $table->boolean('enable_booking_window')->default(false);
            $table->json('booking_window')->nullable();             // {days, open_time, close_time}

            // Widget appearance override (inherits developer defaults if null)
            $table->json('widget_config')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['developer_id', 'status']);
            $table->index(['developer_id', 'booking_mode']);
        });
           Schema::table('booking_categories', function (Blueprint $table) {
            $table->foreignId('service_id')
                  ->nullable()
                  ->after('developer_id')
                  ->constrained()
                  ->nullOnDelete();
 
            $table->index('service_id');
        });
 
        // Booking now also scoped to a Service
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('service_id')
                  ->nullable()
                  ->after('developer_id')
                  ->constrained()
                  ->nullOnDelete();
 
            $table->index('service_id');
        });
         Schema::table('developers', function (Blueprint $table) {
            $table->foreignId('active_service_id')
                  ->nullable()
                  ->after('booking_mode')
                  ->constrained('services')
                  ->nullOnDelete();
        });
    
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
         Schema::table('booking_categories', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Service::class);
            $table->dropColumn('service_id');
        });
 
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Service::class);
            $table->dropColumn('service_id');
        });
           Schema::table('developers', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Service::class, 'active_service_id');
            $table->dropColumn('active_service_id');
        });
    }
};