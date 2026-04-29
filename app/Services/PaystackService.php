<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    private const BASE_URL = 'https://api.paystack.co';

    private PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::baseUrl(self::BASE_URL)
            ->withToken(config('services.paystack.secret_key'))
            ->acceptJson()
            ->throw(); // throw on 4xx/5xx
    }

      // ─── BVN Verification ─────────────────────────────────────────────────────────
 
    /**
     * Verify BVN matches a bank account via Paystack BVN Match API.
     *
     * Requires Paystack to enable this endpoint on your account.
     * Contact support@paystack.com to request access.
     *
     * Returns: [ verified => bool, first_name => ?string, last_name => ?string ]
     */
    public function verifyBvn(string $bvn, string $bankCode, string $accountNumber): array
    {
        try {
            $response = $this->http->post('/bvn/match', [
                'bvn'            => $bvn,
                'bank_code'      => $bankCode,
                'account_number' => $accountNumber,
            ]);
 
            $data = $response->json('data');
 
            $verified = isset($data['account_number'])
                && $data['account_number'] === true
                && ($data['is_blacklisted'] ?? false) === false;
 
            return [
                'verified'   => $verified,
                'first_name' => $data['first_name'] ?? null,
                'last_name'  => $data['last_name']  ?? null,
            ];
 
        } catch (\Exception $e) {
            Log::error('BVN verification API error', ['error' => $e->getMessage()]);
            throw new \Exception('BVN verification failed: ' . $e->getMessage());
        }
    }
    // ─── Subaccounts ────────────────────────────────────────────────────────────

    /**
     * Create a Paystack subaccount for a developer.
     * The subaccount receives 95% of every transaction automatically.
     */
    public function createSubaccount(array $data): array
    {
        $developer = auth()->user()->effectiveDeveloper();
        $response = $this->http->post('/subaccount', [
            'business_name' => $data['business_name'],
            'settlement_bank' => $data['settlement_bank'],
            'account_number' => $data['account_number'],
            'percentage_charge' => $developer->platform_fee_percent, // 5 (Paystack uses integer %)
            'description' => "BookStackIn subaccount for {$data['business_name']}",
            'primary_contact_email' => $data['email'],
        ]);

        return $response->json('data');
    }

    public function fetchSubaccount(string $subaccountCode): array
    {
        $response = $this->http->get("/subaccount/{$subaccountCode}");

        return $response->json('data');
    }

    // ─── Payments ───────────────────────────────────────────────────────────────

    /**
     * Initialize a transaction with split payment.
     * Paystack automatically routes 95% to the subaccount.
     */
    public function initializeTransaction(array $data): array
    {
        $payload = [
            'email' => $data['customer_email'],
            'amount' => (int) $data['amount'], // must be in kobo
            'reference' => $data['reference'],
            'callback_url' => $data['callback_url'] ?? config('app.url').'/payments/callback',
            'metadata' => [
                'booking_id' => $data['booking_id'],
                'developer_id' => $data['developer_id'],
                'custom_fields' => [
                    [
                        'display_name' => 'Booking Reference',
                        'variable_name' => 'booking_reference',
                        'value' => $data['booking_reference'],
                    ],
                    [
                        'display_name' => 'Description',
                        'variable_name' => 'description',
                        'value' => $data['description'],
                    ],
                ],
            ],
        ];

        // Attach subaccount for split payment
        if (! empty($data['subaccount_code'])) {
            $payload['subaccount'] = $data['subaccount_code'];
            $payload['bearer'] = 'account'; // subaccount bears Paystack fees
            // percentage_charge on the subaccount definition handles the split automatically
        }

        $response = $this->http->post('/transaction/initialize', $payload);

        return $response->json('data');
    }

     public function updateSubaccountFee(string $subaccountCode, $feePercent): array
    {
        $response = $this->http->put("/subaccount/{$subaccountCode}", [
            'percentage_charge' => intval($feePercent),
        ]);
 
        return $response->json('data');
    }
 
    /**
     * Verify a transaction by reference.
     */
    public function verifyTransaction(string $reference): array
    {
        $response = $this->http->get("/transaction/verify/{$reference}");

        return $response->json('data');
    }

    /**
     * List transactions (for a subaccount).
     */
    public function listTransactions(array $filters = []): array
    {
        $response = $this->http->get('/transaction', $filters);

        return $response->json('data') ?? [];
    }

    // ─── Webhooks ───────────────────────────────────────────────────────────────

    /**
     * Verify webhook signature from Paystack.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('services.paystack.secret_key');
        $expected = hash_hmac('sha512', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    // ─── Banks ──────────────────────────────────────────────────────────────────

    public function listBanks(): array
    {
        $response = $this->http->get('/bank', ['country' => 'nigeria', 'perPage' => 100]);

        return $response->json('data') ?? [];
    }

    public function resolveAccount(string $accountNumber, string $bankCode): array
    {
        $response = $this->http->get('/bank/resolve', [
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
        ]);

        return $response->json('data');
    }

    // ─── Fee Calculation ────────────────────────────────────────────────────────

    /**
     * Calculate the split for a given amount.
     *
     * @param  int  $amountKobo  Amount in kobo
     * @return array{platform_fee: int, developer_amount: int, paystack_fee: int}
     */
    public function calculateSplit(int $amountKobo): array
    {
        // Paystack fee: 1.5% + ₦100 for local, capped at ₦2000
        // Simplified estimate:
        $paystackFeeKobo = (int) min(($amountKobo * 0.015) + 10000, 200000);

        $platformFee = (int) ($amountKobo * self::SPLIT_RATIO);
        $developerAmount = $amountKobo - $platformFee;

        return [
            'platform_fee' => $platformFee,
            'developer_amount' => $developerAmount,
            'paystack_fee' => $paystackFeeKobo,
        ];
    }
}
