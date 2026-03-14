<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('developer_id')->constrained()->cascadeOnDelete();
            $table->string('paystack_reference')->unique();
            $table->decimal('amount', 12, 2);          // total charged (kobo)
            $table->decimal('platform_fee', 12, 2);    // 5%
            $table->decimal('developer_amount', 12, 2); // 95%
            $table->decimal('paystack_fee', 12, 2)->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->string('channel')->nullable();      // card, bank, ussd, etc.
            $table->string('ip_address')->nullable();
            $table->json('paystack_metadata')->nullable();
            $table->enum('status', ['pending', 'success', 'failed', 'reversed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['developer_id', 'status']);
            $table->index(['developer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
