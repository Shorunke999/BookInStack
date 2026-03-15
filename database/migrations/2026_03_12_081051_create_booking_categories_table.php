<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->cascadeOnDelete();

            // Which mode this category belongs to
            $table->enum('booking_mode', ['appointment', 'ticket', 'reservation']);

            $table->string('name');               // "VIP", "Standard Room", "Haircut"
            $table->text('description')->nullable();

            // Pricing
            $table->unsignedBigInteger('price');         // in kobo — primary / adult price
            $table->unsignedBigInteger('child_price')->nullable(); // ticket mode only

            // Appointment-specific
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedSmallInteger('total_slots')->nullable();
            // Reservation-specific
            $table->unsignedSmallInteger('capacity')->nullable(); // max guests per room

            // Ticket-specific
            $table->boolean('enable_child_pricing')->default(false);
            $table->unsignedSmallInteger('max_per_order')->nullable(); // e.g. max 10 tickets

            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['developer_id', 'booking_mode', 'status']);
        });

        // Add category_id + adults/children to bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('booking_categories')
                  ->nullOnDelete();
            $table->unsignedSmallInteger('adults')->default(1);
            $table->unsignedSmallInteger('children')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\BookingCategory::class, 'category_id');
            $table->dropColumn(['category_id', 'adults', 'children']);
        });

        Schema::dropIfExists('booking_categories');
    }
};
