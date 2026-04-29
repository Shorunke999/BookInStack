<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('developers', function (Blueprint $table) {
            // Configurable per-developer platform fee — default 5%
            // Superadmin can override per developer
            $table->decimal('platform_fee_percent', 5, 2)
                  ->default(5.00)
                  ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('developers', function (Blueprint $table) {
            $table->dropColumn('platform_fee_percent');
        });
    }
};