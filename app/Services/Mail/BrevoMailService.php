<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Http;

class BrevoMailService
{
    public function send(string $to, string $subject, string $html, array $data = []): bool
    {
        $response = Http::withHeaders([
            'api-key' => config('services.brevo.key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => env('MAIL_FROM_NAME'),
                'email' => env('MAIL_FROM_ADDRESS'),
            ],
            'to' => [
                ['email' => $to]
            ],
            'subject' => $subject,
            'htmlContent' => $html,
        ]);

        return $response->successful();
    }
}