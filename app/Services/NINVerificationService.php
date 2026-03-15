<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * NIN Verification Service
 *
 * Integrates with NIMC (National Identity Management Commission) or
 * a licensed identity verification provider like:
 * - Smile Identity  (https://smileidentity.com)
 * - Prembly         (https://prembly.com)
 * - VerifyMe        (https://verifyme.ng)
 * - Dojah           (https://dojah.io)
 *
 * This implementation uses Prembly as the example provider.
 * Swap the provider by changing the protected methods below.
 */
class NINVerificationService
{
    private string $apiKey;

    private string $appId;

    private string $baseUrl = 'https://api.prembly.com/identitypass/verification';

    public function __construct()
    {
        $this->apiKey = config('services.nin.api_key', '');
        $this->appId = config('services.nin.app_id', '');
    }

    /**
     * Verify a Nigerian National Identification Number.
     *
     * @param  string  $nin  11-digit NIN
     * @param  string  $firstName  Expected first name (for cross-check)
     * @param  string  $lastName  Expected last name
     * @return array{verified: bool, data: array, message: string}
     */
    public function verify(string $nin, string $firstName, string $lastName): array
    {
        try {

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'app-id' => $this->appId,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/ng/nin", [
                'number' => $nin,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? false)) {
                return [
                    'verified' => true,
                    'data' => $data['data'] ?? [],
                    'message' => 'NIN verified successfully',
                ];
            }

            return [
                'verified' => false,
                'data' => [],
                'message' => $data['detail'] ?? 'NIN verification failed',
            ];
        } catch (\Exception $e) {
            Log::error('NIN verification error', [
                'nin' => substr($nin, 0, 3).'****', // mask for logs
                'error' => $e->getMessage(),
            ]);

            // In development/testing, you can mock this
            if (app()->environment('local', 'testing')) {
                return $this->mockVerification($nin, $firstName, $lastName);
            }

            throw $e;
        }
    }

    /**
     * Validate NIN format (must be exactly 11 digits).
     */
    public function validateFormat(string $nin): bool
    {
        return (bool) preg_match('/^\d{11}$/', $nin);
    }

    /**
     * Mock verification for local development.
     * Any NIN starting with '0000' returns as verified.
     */
    private function mockVerification(string $nin, string $firstName, string $lastName): array
    {
        if (str_starts_with($nin, '0000')) {
            return [
                'verified' => true,
                'data' => [
                    'nin' => $nin,
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'middlename' => '',
                    'phone' => '08000000000',
                    'gender' => 'M',
                    'birthdate' => '1990-01-01',
                ],
                'message' => 'NIN verified (mock)',
            ];
        }

        return [
            'verified' => false,
            'data' => [],
            'message' => 'NIN not found (mock - use NIN starting with 0000 for testing)',
        ];
    }
}
