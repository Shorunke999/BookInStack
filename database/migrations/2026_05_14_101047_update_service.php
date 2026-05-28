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
        Schema::table('services', function (Blueprint $table) {
            $table->string('public_key')->nullable()->unique()->after('slug');
        });

         Schema::create('developer_service', function (Blueprint $table) {
            $table->foreignId('developer_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('service_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->primary(['developer_id', 'service_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('public_key');
        });
        Schema::dropIfExists('developer_service');
    }
};
