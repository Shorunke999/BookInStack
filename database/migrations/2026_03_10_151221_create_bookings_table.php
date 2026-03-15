<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->foreignId('developer_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2); // in kobo (smallest unit)
            $table->string('description');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('customer_email');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->json('metadata')->nullable();

            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled', 'refunded'])
                ->default('pending');
            $table->enum('booking_mode', ['appointment', 'ticket', 'reservation'])
                ->default('appointment');
            $table->string('paystack_reference')->nullable();
            $table->string('paystack_access_code')->nullable();
            $table->string('payment_url')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->boolean('attended')->default(false);
            $table->timestamp('attended_at')->nullable();
            $table->text('attendance_note')->nullable();
            $table->date('check_in')->nullable();
            $table->date('check_out')->nullable();
            $table->date('preferred_date')->nullable();
            $table->time('preferred_time')->nullable();
            $table->timestamps();

            $table->index(['developer_id', 'status']);
            $table->index(['developer_id', 'created_at']);
            $table->index('paystack_reference');

            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
