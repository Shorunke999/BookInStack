<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('developers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            $table->enum('role', ['admin', 'staff'])->default('admin');
            $table->unsignedBigInteger('owner_id')->nullable();
            // owner_id links staff members back to their admin developer
            $table->foreign('owner_id')->references('id')->on('developers')->nullOnDelete();

            $table->string('nin', 11)->unique()->nullable();
            $table->boolean('nin_verified')->default(false);

           $table->enum('booking_mode', ['appointment', 'ticket', 'reservation'])
                ->default('appointment');
            $table->boolean('enable_booking_window')->default(false);
            $table->json('booking_window')->nullable();

            $table->string('public_key', 64)->unique()->nullable();
            $table->string('secret_key', 64)->unique()->nullable();

            $table->string('paystack_subaccount_code')->nullable();
            $table->string('paystack_subaccount_id')->nullable();

            $table->string('business_name')->nullable();
            $table->string('bank_code', 10)->nullable();
            $table->string('account_number', 20)->nullable();

            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->enum('reservation_unit', ['night', 'day'])->default('night');
            $table->json('widget_config')->nullable();

            $table->string('remember_token', 100)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('developers');
    }
};
