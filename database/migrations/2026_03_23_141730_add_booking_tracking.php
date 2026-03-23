<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Who created / booked this — developer_id (admin) or staff member
            $table->unsignedBigInteger('booked_by_id')->nullable()->after('developer_id');
            $table->string('booked_by_type')->nullable()->after('booked_by_id');
            // e.g. 'widget', 'payment_link', 'dashboard', 'scan'
            $table->string('booked_via')->nullable()->after('booked_by_type');

            // Who marked attendance
            $table->unsignedBigInteger('attended_by_id')->nullable()->after('attended_at');

            // Payment link token if booked via a link
            $table->string('payment_link_token', 32)->nullable()->after('paystack_reference');

            $table->foreign('booked_by_id')->references('id')->on('developers')->nullOnDelete();
            $table->foreign('attended_by_id')->references('id')->on('developers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['booked_by_id']);
            $table->dropForeign(['attended_by_id']);
            $table->dropColumn(['booked_by_id','booked_by_type','booked_via','attended_by_id','payment_link_token']);
        });
    }
};