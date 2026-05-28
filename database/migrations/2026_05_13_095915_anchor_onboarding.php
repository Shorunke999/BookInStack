<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Drop Paystack columns from developers ──────────────────────
        Schema::table('developers', function (Blueprint $table) {
            $table->dropColumn([
                'bvn_verified',
                'public_key',
                'secret_key',
                'paystack_subaccount_code',
                'paystack_subaccount_id',
                'bank_code',
                'account_number',
            ]);
        });

        // ── 2. Add Anchor + onboarding columns to developers ──────────────
        Schema::table('developers', function (Blueprint $table) {
            $table->string('onboarding_status')->default('incomplete')->after('status');
            // Anchor-issued keys (replaces paystack public/secret)
            $table->string('anchor_public_key')->nullable()->after('onboarding_status');
        });

        // ── 3. Create onboardings table ───────────────────────────────────
        Schema::create('onboardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained('developers')->cascadeOnDelete();

            // Type
            $table->string('customer_type')->nullable();          // CustomerType enum

            // Anchor IDs
            $table->string('anchor_customer_id')->nullable()->unique();
            $table->string('anchor_account_id')->nullable();
            $table->string('anchor_reserved_account_id')->nullable();
            $table->string('anchor_nuban')->nullable();
            $table->string('anchor_bank_name')->nullable();
            $table->string('anchor_counterparty_id')->nullable(); // for NIP transfers

            // KYC
            $table->string('kyc_status')->default('pending');     // KycStatus enum
            $table->string('kyc_tier')->default('TIER_0');
            $table->json('kyc_documents_required')->nullable();   // from awaitingDocument webhook

            // Individual KYC fields
            $table->string('bvn')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();

            // Business KYB fields
            $table->string('rc_number')->nullable();
            $table->string('registration_type')->nullable();
            $table->date('date_of_registration')->nullable();
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->string('director_first_name')->nullable();
            $table->string('director_last_name')->nullable();
            $table->string('director_bvn')->nullable();
            $table->date('director_dob')->nullable();
            $table->string('director_id_type')->nullable();       // DRIVERS_LICENSE, NIN_SLIP etc
            $table->string('director_id_number')->nullable();

            // Settlement (developer's real bank for NIP payout)
            $table->string('settlement_bank_code')->nullable();
            $table->string('settlement_bank_nip_code')->nullable();
            $table->string('settlement_account_number')->nullable();
            $table->string('settlement_account_name')->nullable();

            $table->json('meta')->nullable();                      // raw Anchor responses
            $table->timestamps();
        });
        
       Schema::table('bookings', function (Blueprint $table) {
            // Virtual account columns (per-booking dynamic account)
            $table->string('anchor_virtual_account_number')->nullable();
            $table->string('anchor_va_bank_name')->nullable();
            $table->string('anchor_va_account_name')->nullable();
            $table->string('anchor_va_reference')->nullable()->index(); // ← indexed for fast webhook lookup
            $table->timestamp('anchor_va_expires_at')->nullable();
        
            // Payment confirmation
            $table->string('anchor_payin_ref')->nullable();
            $table->string('anchor_session_id')->nullable();
        
            // Drop Paystack columns
            $table->dropColumn(['paystack_reference', 'paystack_access_code', 'payment_url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboardings');

        Schema::table('developers', function (Blueprint $table) {
            $table->dropColumn(['onboarding_status', 'anchor_public_key']);

            // Restore dropped columns
            $table->boolean('bvn_verified')->default(false);
            $table->string('public_key')->nullable();
            $table->string('secret_key')->nullable();
            $table->string('paystack_subaccount_code')->nullable();
            $table->string('paystack_subaccount_id')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('account_number')->nullable();
        });
    }
};
