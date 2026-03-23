<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Developer;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VerificationController extends Controller
{
    public function __construct(private PaystackService $paystack) {}

    public function verify(Request $request): RedirectResponse
    {
        /** @var Developer $developer */
        $developer = $request->user();

        if ($developer->bvn_verified) {
            return redirect()->route('dashboard.api-keys')
                ->with('success', 'Your account is already verified.');
        }
        $data = $request->validate([
            'bvn'            => 'required|digits:11',
            'bank_code'      => 'required|string',
            'account_number' => 'required|digits:10',
        ]);
        // ── Note: we intentionally do NOT check BVN uniqueness in DB
        // because we never store the raw BVN ────────────────────────────────

        DB::beginTransaction();

        try {
            $verification = $this->paystack->verifyBvn(
                $data['bvn'],
                $data['bank_code'],
                $data['account_number']
            );
            if (!$verification['verified']) {
                DB::rollBack();
                return back()->withErrors([
                    'bvn' => 'BVN verification failed. Please check your details.'
                ]);
            }
            // ── 2. Create Paystack subaccount ──────────────────────────────
            $subaccount = $this->paystack->createSubaccount([
                'business_name'     => $developer->business_name ?? $developer->name,
                'settlement_bank'   => $data['bank_code'],
                'account_number'    => $data['account_number'],
                'percentage_charge' => $developer->platform_fee_percent ?? 5.00,
                'email'             => $developer->email,
            ]);

            // ── 3. Issue API keys ──────────────────────────────────────────
            $publicKey = 'pk_live_' . Str::random(40);
            $secretKey = 'sk_live_' . Str::random(40);

            // ── 4. Save — NO raw BVN stored ───────────────────────────────
            $developer->update([
                'bvn_verified'             => true,     // status only
                'bank_code'                => $data['bank_code'],
                'account_number'           => $data['account_number'],
                'paystack_subaccount_code' => $subaccount['subaccount_code'],
                'paystack_subaccount_id'   => $subaccount['id'],
                'public_key'               => $publicKey,
                'secret_key'               => $secretKey,
                'status'                   => 'active',
                // 'bvn' intentionally omitted — never persisted
            ]);

            DB::commit();

            Log::info('Developer activated via BVN verification', [
                'developer_id' => $developer->id,
            ]);

            return redirect()->route('dashboard.api-keys')
                ->with('success', 'Identity verified! Your account is now active.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('BVN verification failed', [
                'developer_id' => $developer->id,
                'error'        => $e->getMessage(),
            ]);

            return back()->with('error', 'Activation failed: ' . $e->getMessage());
        }
    }
}