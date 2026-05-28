<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        /*
        |--------------------------------------------------------------------------
        | Recreate columns only if missing
        |--------------------------------------------------------------------------
        */

        Schema::table('developers', function (Blueprint $table) {

            if (!Schema::hasColumn('developers', 'paystack_subaccount_code')) {
                $table->string('paystack_subaccount_code')
                    ->nullable()
                    ->after('email');
            }

            if (!Schema::hasColumn('developers', 'paystack_subaccount_id')) {
                $table->string('paystack_subaccount_id')
                    ->nullable()
                    ->after('paystack_subaccount_code');
            }

            if (!Schema::hasColumn('developers', 'bank_code')) {
                $table->string('bank_code')
                    ->nullable()
                    ->after('paystack_subaccount_id');
            }

            if (!Schema::hasColumn('developers', 'account_number')) {
                $table->string('account_number')
                    ->nullable()
                    ->after('bank_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('developers', function (Blueprint $table) {

            $columns = [
                'paystack_subaccount_code',
                'paystack_subaccount_id',
                'bank_code',
                'account_number',
            ];

            foreach ($columns as $column) {

                if (Schema::hasColumn('developers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
