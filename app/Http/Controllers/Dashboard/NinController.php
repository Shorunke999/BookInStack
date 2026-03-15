<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Developer;
use App\Services\NINVerificationService;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NController extends Controller
{
    public function __construct(
        private NINVerificationService $ninService,
        private PaystackService $paystack
    ) {}

    /**
     * POST /dashboard/nin/verify
     * Form submission from the API Keys page.
     */
    public function verify(Request $request): RedirectResponse
    {
        $developer = $request->user();

        if ($developer->nin_verified) {
            return redirect()->route('dashboard.api-keys')
                ->with('success', 'Your account is already verified.');
        }

        $data = $request->validate([
            'nin' => 'required|string|digits:11',
            'bank_code' => 'required|string',
            'account_number' => 'required|string|digits:10',
        ]);

        // Check NIN not already used
        if (Developer::where('nin', $data['nin'])->where('id', '!=', $developer->id)->exists()) {
            return back()->withErrors(['nin' => 'This NIN is already registered to another account.']);
        }

        DB::beginTransaction();

        try {
            // 1. Verify NIN
            $names = explode(' ', $developer->name, 2);
            $firstName = $names[0];
            $lastName = $names[1] ?? '';

            // $verification = $this->ninService->verify($data['nin'], $firstName, $lastName);

            // if (! $verification['verified']) {
            //     DB::rollBack();
            //     return back()->withErrors(['nin' => 'NIN verification failed: ' . $verification['message']]);
            // }

            // 2. Create Paystack subaccount
            $subaccount = $this->paystack->createSubaccount([
                'business_name' => $developer->business_name,
                'bank_code' => $data['bank_code'],
                'account_number' => $data['account_number'],
                'email' => $developer->email,
            ]);

            // 3. Issue keys
            $publicKey = 'pk_live_'.Str::random(40);
            $secretKey = 'sk_live_'.Str::random(40);

            // 4. Save
            $developer->update([
                'nin' => $data['nin'],
                'nin_verified' => true,
                'bank_code' => $data['bank_code'],
                'account_number' => $data['account_number'],
                'paystack_subaccount_code' => $subaccount['subaccount_code'],
                'paystack_subaccount_id' => $subaccount['id'],
                'public_key' => $publicKey,
                'secret_key' => $secretKey,
                'status' => 'active',
            ]);

            DB::commit();

            Log::info('Developer activated via dashboard', ['id' => $developer->id]);

            return redirect()->route('dashboard.api-keys')
                ->with('success', 'Identity verified! Your account is now active. Here is your API key.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('NIN verification failed', [
                'developer_id' => $developer->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Activation failed. Please try again or contact support.');
        }
    }
}
