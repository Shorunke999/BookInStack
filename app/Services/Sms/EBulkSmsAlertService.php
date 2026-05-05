<?php
namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EBulkSmsAlertService
{
    protected string $username;
    protected string $apiKey;
    protected string $sender;
    protected string $supportPhone;

    public function __construct()
    {
        $this->username     = config('services.ebulksms.username');
        $this->apiKey       = config('services.ebulksms.apikey');
        $this->sender       = config('services.ebulksms.sender');
        $this->supportPhone = config('app.support_phone', '0800-000-0000');
    }

    // ─── Message Builders ────────────────────────────────────────────

    public function creditAlert(array $data): string
    {
        return sprintf(
            "%s Alert\nAcct: ****%s\nCR: NGN %s\nDesc: %s\nBal: NGN %s\nDate: %s\nCharge: NGN %s\nRef: %s\nThank you for banking with us.",
            $data['business_name'] ?? config('app.business_name', 'MyBusiness'),
            substr($data['account_number'], -4),
            number_format($data['amount'], 2),
            $data['description'],
            number_format($data['balance'], 2),
            now()->format('d-M-Y h:iA'),
            number_format($data['charge'] ?? 0, 2),
            $data['reference']
        );
    }

    public function debitAlert(array $data): string
    {
        return sprintf(
            "%s Alert\nAcct: ****%s\nDR: NGN %s\nDesc: %s\nBal: NGN %s\nDate: %s\nCharge: NGN %s\nRef: %s\nQueries? Call %s",
            $data['business_name'] ?? config('app.business_name', 'MyBusiness'),
            substr($data['account_number'], -4),
            number_format($data['amount'], 2),
            $data['description'],
            number_format($data['balance'], 2),
            now()->format('d-M-Y h:iA'),
            number_format($data['charge'] ?? 0, 2),
            $data['reference'],
            $this->supportPhone
        );
    }

    // ─── SMS Sender ───────────────────────────────────────────────────

    public function send(string $phone, string $message): array
    {
        $phone = $this->formatPhone($phone);

        try {
            $response = Http::timeout(10)->get('https://api.ebulksms.com/sendsms', [
                'username'    => $this->username,
                'apikey'      => $this->apiKey,
                'sender'      => $this->sender,
                'messagetext' => $message,
                'flash'       => 0,
                'recipients'  => $phone,
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['response']['status']) && $result['response']['status'] === 'SUCCESS') {
                Log::info('SMS sent successfully', ['phone' => $phone, 'status' => $result]);
                return ['success' => true, 'data' => $result];
            }

            Log::warning('SMS sending failed', ['phone' => $phone, 'response' => $result]);
            return ['success' => false, 'error' => $result['response']['message'] ?? 'Unknown error'];

        } catch (\Exception $e) {
            Log::error('SMS exception', ['phone' => $phone, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─── Convenience Methods ──────────────────────────────────────────

    public function sendCreditAlert(string $phone, array $data): array
    {
        $message = $this->creditAlert($data);
        return $this->send($phone, $message);
    }

    public function sendDebitAlert(string $phone, array $data): array
    {
        $message = $this->debitAlert($data);
        return $this->send($phone, $message);
    }

    // ─── Phone Formatter ──────────────────────────────────────────────

    protected function formatPhone(string $phone): string
    {
        // Remove spaces, dashes, plus signs
        $phone = preg_replace('/[\s\-\+]/', '', $phone);

        // Convert 08012345678 → 2348012345678
        if (str_starts_with($phone, '0')) {
            $phone = '234' . substr($phone, 1);
        }

        return $phone;
    }
}
