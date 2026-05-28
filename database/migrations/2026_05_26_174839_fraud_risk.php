<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('risk_score')->default(0);

            $table->string('risk_level')
                ->default('low');

            $table->json('risk_reasons')
                ->nullable();

            $table->timestamp('flagged_at')
                ->nullable();

            $table->ipAddress('ip_address')
                ->nullable();
        });
        Schema::table('services', function (Blueprint $table) {
            $table->json('fraud_config')
                ->nullable()
                ->after('widget_config');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'risk_score',
                'risk_level',
                'risk_reasons',
                'flagged_at',
                'ip_address',
            ]);
        });
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('fraud_config');
        });
    }
};
