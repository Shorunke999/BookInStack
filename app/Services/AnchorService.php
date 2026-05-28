<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnchorService
{
    private const BASE_URL_SANDBOX = 'https://api.sandbox.getanchor.co';
    private const BASE_URL_PROD    = 'https://api.getanchor.co';

    private PendingRequest $http;

    public function __construct()
    {
        $baseUrl = config('services.anchor.env') === 'production'
            ? self::BASE_URL_PROD
            : self::BASE_URL_SANDBOX;
        
        $this->http = Http::baseUrl($baseUrl)
            ->withHeaders(['x-anchor-key' => config('services.anchor.secret_key')])
            ->acceptJson()
            ->asJson()
            ->throw();
 
    }

    // ─── Individual Customer ──────────────────────────────────────────────────

    /**
     * Create an individual customer on Anchor (Tier 0).
     * Returns the Anchor customer ID.
     */
    public function createIndividualCustomer(array $data): array
    {
        $response = $this->http->post('/api/v1/customers', [
            'data' => [
                'type' => 'IndividualCustomer',
                'attributes' => [
                    'fullName' => [
                        'firstName'  => $data['first_name'],
                        'lastName'   => $data['last_name'],
                    ],
                    'address' => [
                        'addressLine_1' => $data['address_line1'],
                        'addressLine_2' => $data['address_line2'] ?? $data['address_line1'],
                        'city'          => $data['city'],
                        'state'         => strtoupper($data['state']),
                        'postalCode'    => $data['postal_code'] ?? '100001',
                        'country'       => 'NG',
                    ],
                    'email'       =>$data['email'],
                    'phoneNumber' => $data['phone_number'],
                    'metadata'    => ['developer_id' => $data['developer_id']],
                ],
            ],
        ]);

        return $response->json('data');
    }

    /**
     * Trigger TIER_1 KYC for an individual customer using BVN.
     * Listen for customer.identification.approved webhook.
     */
    public function triggerIndividualKyc(string $anchorCustomerId, array $data): array
    {
        $response = $this->http->post(
            "/api/v1/customers/{$anchorCustomerId}/verification/individual",
            [
                'data' => [
                    'type' => 'Verification',
                    'attributes' => [
                        'level' => 'TIER_2',
                        'level2' => [
                            'bvn'         => $data['bvn'],
                            'dateOfBirth' => $data['date_of_birth'],  // YYYY-MM-DD
                            'gender'      => ucfirst(strtolower($data['gender'])), // Male|Female
                        ],
                    ],
                ],
            ]
        );

        return $response->json('data');
    }

    // ─── Business Customer ────────────────────────────────────────────────────

    /**
     * Create a business customer on Anchor.
     */
    public function createBusinessCustomer(array $data): array
    {
        $state = strtoupper($data['state']);
        $payload = [                    // ← rename to $payload
            'data' => [
                'type'       => 'BusinessCustomer',
                'attributes' => [
                    'address' => [
                        'country' => 'NG',
                        'state'   => $state,
                    ],
                    'basicDetail' => [
                        'businessName'       => $data['business_name'],
                        'businessBvn'        => $data['director_bvn'],
                        'registrationType'   => $data['registration_type'],
                        'dateOfRegistration' => $data['date_of_registration'],
                        'industry'           => $data['industry'],
                        'description'        => $data['description'] ?? $data['business_name'],
                        'country'            => 'NG',
                        'website'            => $data['website'] ?? null,
                    ],
                    'contact' => [
                        'email' => [
                            'general' => $data['email'],
                            'support' => $data['email'],
                            'dispute' => $data['email'],
                        ],
                        'phoneNumber' => $data['phone_number'],
                        'address' => [
                            'main' => [
                                'country'       => 'NG',
                                'state'         => $state,
                                'addressLine_1' => $data['address_line1'],
                                'addressLine_2' => $data['address_line2'] ?? $data['address_line1'],
                                'city'          => $data['city'],
                                'postalCode'    => $data['postal_code'] ?? '100001',
                            ],
                            'registered' => [
                                'country'       => 'NG',
                                'state'         => $state,
                                'addressLine_1' => $data['address_line1'],
                                'addressLine_2' => $data['address_line2'] ?? $data['address_line1'],
                                'city'          => $data['city'],
                                'postalCode'    => $data['postal_code'] ?? '100001',
                            ],
                        ],
                    ],
                    'officers' => [
                        [
                            'role'            => 'OWNER',
                            'nationality'     => 'NG',
                            'title'           => $data['director_title'] ?? 'CEO',
                            'fullName'        => [
                                'firstName' => $data['director_first_name'],
                                'lastName'  => $data['director_last_name'],
                            ],
                            'dateOfBirth'     => $data['director_dob'],
                            'email'           => $data['email'],
                            'phoneNumber'     => $data['phone_number'],
                            'bvn'             => $data['director_bvn'],
                            'percentageOwned' => 100,
                            'address'         => [
                                'country'       => 'NG',
                                'state'         => $state,
                                'addressLine_1' => $data['address_line1'],
                                'addressLine_2' => $data['address_line2'] ?? $data['address_line1'],
                                'city'          => $data['city'],
                                'postalCode'    => $data['postal_code'] ?? '100001',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->http->post('/api/v1/customers', $payload);  // ← use $payload
        return $response->json('data');
    }


    /**
     * Add a director/owner officer to a business customer.
     */
    public function addBusinessOfficer(string $anchorBusinessId, array $data): array
    {
        $response = $this->http->post(
            "/api/v1/businesses/{$anchorBusinessId}/officers",
            [
                'data' => [
                    'type' => 'BusinessOfficer',
                    'attributes' => [
                        'fullName' => [
                            'firstName' => $data['first_name'],
                            'lastName'  => $data['last_name'],
                        ],
                        'role'               => 'OWNER',
                        'dateOfBirth'        => $data['date_of_birth'],
                        'email'              => $data['email'],
                        'phoneNumber'        => $data['phone_number'],
                        'nationality'        => 'NG',
                        'identificationType' => $data['id_type'],   // DRIVERS_LICENSE | NIN_SLIP | INTL_PASSPORT
                        'idDocumentNumber'   => $data['id_number'],
                        'bvn'                => $data['bvn'],
                        'percentageOwned'    => 100.0,
                        'address' => [
                            'addressLine_1' => $data['address_line1'] ?? 'Nigeria',
                            'city'          => $data['city'] ?? 'Lagos',
                            'state'         => $data['state'] ?? 'Lagos',
                            'country'       => 'NG',
                        ],
                    ],
                ],
            ]
        );

        return $response->json('data');
    }

    /**
     * Trigger KYB for a business customer.
     * Listen for customer.identification.awaitingDocument webhook.
     */
    public function triggerBusinessKyb(string $anchorCustomerId): array
    {
        $response = $this->http->withBody('{}', 'application/json')
            ->post("/api/v1/customers/{$anchorCustomerId}/verification/business");


        return $response->json('data');
    }

    // ─── Reserved Account (NUBAN) ─────────────────────────────────────────────

    /**
     * Step 1: Create a deposit account for the verified customer.
     */
    public function createDepositAccount(string $anchorCustomerId): array
    {
        $response = $this->http->post('/api/v1/accounts', [
            'data' => [
                'type'          => 'DepositAccount',
                'attributes'    => [
                    'accountType' => 'CURRENT',
                    'currency'    => 'NGN',
                ],
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'id'   => $anchorCustomerId,
                            'type' => 'Customer',
                        ],
                    ],
                ],
            ],
        ]);

        return $response->json('data');
    }

    /**
     * Step 2: Fetch the NUBAN (account number) linked to the deposit account.
     */
    public function getAccountNumber(string $depositAccountId): ?array
    {
        $response = $this->http->get('/api/v1/account-numbers', [
            'AccountId' => $depositAccountId,
        ]);

        $data = $response->json('data');

        // Returns array of account numbers — return the default/first one
        return $data[0] ?? null;
    }

    // ─── NIP Transfer ─────────────────────────────────────────────────────────

    /**
     * List available banks (NIP codes needed for transfers).
     */
    public function listBanks(): array
    {
        $response = $this->http->get('/api/v1/banks');

        return $response->json('data') ?? [];
    }

    /**
     * Verify a bank account before creating a counterparty.
     */
    public function verifyAccount(string $nipCodeOrBankId, string $accountNumber): array
    {
        $response = $this->http->get(
            "/api/v1/payments/verify-account/{$nipCodeOrBankId}/{$accountNumber}"
        );

        return $response->json('data');
    }

    /**
     * Create a counterparty (beneficiary) for NIP transfers.
     * If counterparty already exists, Anchor returns the existing one.
     */
    public function createCounterparty(array $data): array
    {
        $response = $this->http->post('/api/v1/counterparties', [
            'data' => [
                'type' => 'CounterParty',
                'attributes' => [
                    'accountName'   => $data['account_name'],
                    'accountNumber' => $data['account_number'],
                    'bank' => [
                        'nipCode' => $data['nip_code'],
                    ],
                    'verifyName' => true,
                ],
            ],
        ]);

        return $response->json('data');
    }

    /**
     * Initiate a NIP (NIBSS) transfer to a counterparty.
     * This is how we pay out to developers after receiving a booking payment.
     *
     * @param  string  $sourceAccountId   Anchor deposit account ID to debit
     * @param  string  $counterpartyId    Anchor counterparty ID
     * @param  int     $amountKobo        Amount in kobo
     * @param  string  $reference         Your unique reference
     * @param  string  $narration         Transfer description
     */
    public function initiateNipTransfer(
        string $sourceAccountId,
        string $counterpartyId,
        int $amountKobo,
        string $reference,
        string $narration = 'BookInStack payout'
    ): array {
        $response = $this->http->post('/api/v1/transfers', [
            'data' => [
                'type' => 'NIPTransfer',
                'attributes' => [
                    'amount'    => $amountKobo,
                    'currency'  => 'NGN',
                    'reference' => $reference,
                    'narration' => substr($narration, 0, 100),
                ],
                'relationships' => [
                    'sourceAccount' => [
                        'data' => [
                            'id'   => $sourceAccountId,
                            'type' => 'DepositAccount',
                        ],
                    ],
                    'counterParty' => [
                        'data' => [
                            'id'   => $counterpartyId,
                            'type' => 'CounterParty',
                        ],
                    ],
                ],
            ],
        ]);

        return $response->json('data');
    }

    /**
     * Verify a transfer status by reference.
     */
    public function verifyTransfer(string $reference): array
    {
        $response = $this->http->get("/api/v1/transfers/{$reference}");

        return $response->json('data');
    }

    // ─── Webhooks ─────────────────────────────────────────────────────────────

    /**
     * Verify Anchor webhook HMAC signature.
     * Anchor signs with SHA-512 HMAC using your webhook secret.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret   = config('services.anchor.webhook_secret');
        $expected = hash_hmac('sha512', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    // ─── Fee Calculation ──────────────────────────────────────────────────────

    /**
     * Calculate what the developer receives after platform fee and NIP charges.
     * NIP transfer fee on Anchor: ₦10–₦52.50 depending on amount (CBN NIP tiers).
     *
     * @param  int  $amountKobo          Incoming payment in kobo
     * @param  float  $platformFeePercent  e.g. 5.0 for 5%
     */
    public function calculatePayout(int $amountKobo, float $platformFeePercent): array
    {
        // CBN NIP fee tiers (in kobo)
        $nipFeeKobo = match (true) {
            $amountKobo <= 500_000    => 1_000,   // ₦10
            $amountKobo <= 5_000_000  => 2_500,   // ₦25
            default                   => 5_250,   // ₦52.50
        };

        $platformFeeKobo  = (int) round(($platformFeePercent / 100) * $amountKobo);
        $developerAmountKobo = $amountKobo - $platformFeeKobo - $nipFeeKobo;

        return [
            'amount_kobo'          => $amountKobo,
            'platform_fee_kobo'    => $platformFeeKobo,
            'nip_fee_kobo'         => $nipFeeKobo,
            'developer_payout_kobo' => max(0, $developerAmountKobo),
        ];
    }
// ── Add this method to AnchorService ─────────────────────────────────────────

/**
 * Create a dynamic virtual account for a specific booking payment.
 * The account expires after $expirySeconds (default 30 mins).
 * Anchor fires payin.received webhook when payment lands.
 *
 * @param  string  $bookingReference   Your booking reference — stored in metadata for matching
 * @param  int     $amountKobo         Exact amount the customer must pay
 * @param  string  $customerName       Customer's full name (shown on bank receipt)
 * @param  string  $customerEmail      Customer's email
 * @param  int     $expirySeconds      How long the account stays alive (default 1800 = 30 mins)
 * @param  string  $provider           ninepsb | providus
 */
    public function createPayWithTransfer(
        string $bookingReference,
        int    $amountKobo,
        string $customerName,
        string $customerEmail,
        int    $expirySeconds = 1800,
        string $provider = 'ninepsb'
    ): array {
          if (config('app.env') !== 'production') {
        return [
            'id'         => 'sandbox-pwt-' . $bookingReference,
            'type'       => 'PayWithTransfer',
            'attributes' => [
                'reference'   => $bookingReference,
                'amount'      => $amountKobo,
                'accountName' => $customerName,
                'accountNumber' => '0000000000',   // fake number for testing
                'bank'        => [
                    'provider' => $provider,
                    'name'     => '9 Payment Service Bank (Sandbox)',
                ],
                'expiryTime'  => $expirySeconds,
            ],
        ];
    }
        $response = $this->http->post('/pay/pay-with-transfer', [
            'data' => [
                'type'       => 'PayWithTransfer',
                'attributes' => [
                    'reference'  => $bookingReference,          // YOUR reference — comes back in webhook
                    'amount'     => $amountKobo,
                    'expiryTime' => $expirySeconds,
                    'provider'   => $provider,
                    'customer'   => [
                        'fullName' => $customerName,
                        'email'    => $customerEmail,
                    ],
                    'metadata'   => [
                        'booking_reference' => $bookingReference,
                    ],
                ],
            ],
        ]);

        return $response->json('data');
    }
}
