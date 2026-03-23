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
         Schema::table('developers', function (Blueprint $table) {
             // Drop NIN columns — we never store raw identity numbers
            if (Schema::hasColumn('developers', 'nin')) {
                $table->dropColumn('nin');
            }
            if (Schema::hasColumn('developers', 'nin_verified')) {
                $table->dropColumn('nin_verified');
            }
 
            // Add BVN verified flag — we store status only, never the raw BVN
            if (!Schema::hasColumn('developers', 'bvn_verified')) {
                $table->boolean('bvn_verified')->default(false)->after('status');
            }
            $table->timestamp('booking_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('developers', function (Blueprint $table) {
            $table->dropColumn('booking_expires_at');
             $table->string('nin', 11)->nullable()->unique();
            $table->boolean('nin_verified')->default(false);
 
            if (Schema::hasColumn('developers', 'bvn_verified')) {
                $table->dropColumn('bvn_verified');
            }
        });
    }
};
