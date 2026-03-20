<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-developer: enable/disable the negotiate feature globally
        Schema::table('developers', function (Blueprint $table) {
            $table->boolean('enable_negotiate')->default(false)->after('reservation_unit');
            $table->string('whatsapp_number', 20)->nullable()->after('enable_negotiate');
        });

        // Per-category: whether price is fixed or customer can enter custom amount
        // Schema::table('booking_categories', function (Blueprint $table) {
        //     $table->boolean('fixed_price')->default(true)->after('price');
        //     // min/max guard when fixed_price = false
        //     // $table->unsignedBigInteger('min_price')->nullable()->after('fixed_price');
        //     // $table->unsignedBigInteger('max_price')->nullable()->after('min_price');
        // });

        // Payment links table
        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->string('token', 32)->unique();     // public token e.g. pay/abc123
            $table->foreignId('developer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('booking_categories')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->unsignedBigInteger('amount');       // negotiated amount in kobo
            $table->string('description')->nullable();
            $table->text('note')->nullable();           // private note from developer
            $table->enum('status', ['pending', 'paid', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['developer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('developers', function (Blueprint $table) {
            $table->dropColumn(['enable_negotiate', 'whatsapp_number']);
        });
        Schema::table('booking_categories', function (Blueprint $table) {
            $table->dropColumn(['fixed_price', 'min_price', 'max_price']);
        });
        Schema::dropIfExists('payment_links');
    }
};