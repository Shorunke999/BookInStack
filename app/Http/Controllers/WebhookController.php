<?php

namespace App\Http\Controllers;

use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Services\PaystackService;
use App\Services\Sms\EBulkSmsAlertService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
class WebhookController extends Controller
{
    public function __construct(
        private PaystackService $paystack,
        private \App\Http\Controllers\PaymentController $paymentController
    ) {}

    /**
     * POST /webhooks/paystack
     *
     * Paystack sends events here after every transaction.
     * This is the source of truth — always prefer this over redirect callbacks.
     */
    public function handle(Request $request): Response
    {
        // ── 1. Verify the webhook signature ────────────────────────────────
        $signature = $request->header('x-paystack-signature');
        $rawBody = $request->getContent();

        if (! $signature || ! $this->paystack->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('Invalid Paystack webhook signature', [
                'ip' => $request->ip(),
            ]);

            return response('Unauthorized', 401);
        }

        // ── 2. Decode the event ────────────────────────────────────────────
        $event = $request->json()->all();
        $type = $event['event'] ?? null;
        $data = $event['data'] ?? [];

        Log::info("Paystack webhook received: {$type}", [
            'reference' => $data['reference'] ?? null,
        ]);

        // ── 3. Route to handler ────────────────────────────────────────────
        match ($type) {
            'charge.success' => $this->handleChargeSuccess($data),
            'transfer.success' => $this->handleTransferSuccess($data),
            'transfer.failed' => $this->handleTransferFailed($data),
            'transfer.reversed' => $this->handleTransferReversed($data),
            'subscription.create' => null, // future
            'invoice.payment_failed' => null, // future
            default => Log::info("Unhandled webhook event: {$type}"),
        };

        // Paystack expects a 200 response quickly
        return response('OK', 200);
    }

    // ─── Event Handlers ──────────────────────────────────────────────────────────

    private function handleChargeSuccess(array $data): void
    {
        $reference = $data['reference'] ?? null;

        if (! $reference) {
            Log::error('charge.success missing reference', $data);

            return;
        }

        // Find the booking by paystack reference
        $booking = Booking::where('paystack_reference', $reference)->first();

        if (! $booking) {
            Log::warning("Booking not found for paystack reference: {$reference}");

            return;
        }

        // Idempotency: skip if already processed
        if ($booking->status === 'paid') {
            Log::info("Booking {$booking->reference} already marked as paid, skipping.");

            return;
        }

        try {
            $this->paymentController->recordSuccessfulPayment($booking, $data);

            Log::info("Booking {$booking->reference} marked as paid via webhook.");
             // ── Send confirmation email ────────────────────────────────────
            $this->sendConfirmation($booking->fresh(['developer']));

        } catch (\Exception $e) {
            Log::error("Failed to process charge.success for {$reference}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleTransferSuccess(array $data): void
    {
        Log::info('Transfer success', ['reference' => $data['reference'] ?? null]);
        // Update transfer status if you track manual transfers
    }

    private function handleTransferFailed(array $data): void
    {
        Log::warning('Transfer failed', ['reference' => $data['reference'] ?? null]);
        // Alert developer / retry logic
    }

    private function handleTransferReversed(array $data): void
    {
        Log::warning('Transfer reversed', ['reference' => $data['reference'] ?? null]);

        // Mark payment as reversed
        $reference = $data['reference'] ?? null;
        if ($reference) {
            \App\Models\Payment::where('paystack_reference', $reference)
                ->update(['status' => 'reversed']);
        }
    }

    private function sendConfirmation(Booking $booking): void
    {
        Log::info('in the send Confirmation method');
        // ── Send confirmation email ────────────────────────────────────
        try {
            Mail::to($booking->customer_email)
                ->send(new BookingConfirmed($booking, $booking->developer));
            Log::info('in the send booking Confirmed try-catch');
        } catch (\Exception $e) {
            Log::error("Failed to send confirmation email for {$booking->reference}", [
                'error' => $e->getMessage(),
            ]);
        }
        $phone = $booking->service()->sms_number ?? null;
        // ── Send SMS alert ─────────────────────────────────────────────
        if (! $phone) {
            try {
                $developer = $booking->developer;
                 Log::info('in the phone sms try-catch');
                app(EBulkSmsAlertService::class)->sendCreditAlert($phone, [
                    'business_name'  => $developer->business_name ?? $developer->name,
                    'account_number' => $developer->account_number ?? '0000000000',
                    'amount'         => $booking->amount,
                    'description'    => "Booking payment - " . ($developer->business_name ?? $developer->name),
                    'balance'        => null, // you can pass real balance if available
                    'charge'         => $booking->platform_fee ?? 0,
                    'reference'      => $booking->reference,
                ]);

                Log::info("SMS alert sent for booking {$booking->reference}", [
                    'phone' => $phone,
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to send SMS alert for {$booking->reference}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
