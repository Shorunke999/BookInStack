<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private PaystackService $paystack) {}

    /**
     * POST /payments/initialize
     *
     * Called by SDK after Booking.create().
     * Returns Paystack access_code + authorization_url to open the popup.
     */
    public function initialize(Request $request): JsonResponse
    {
        $developer = $request->get('developer');

        $data = $request->validate([
            'booking_reference' => 'required|string',
            'callback_url' => 'nullable|url',
        ]);

        $booking = Booking::where('reference', $data['booking_reference'])
            ->where('developer_id', $developer->id)
            ->where('status', 'pending')
            ->firstOrFail();

        try {
            $transaction = $this->paystack->initializeTransaction([
                'customer_email' => $booking->customer_email,
                'amount' => (int) $booking->amount,
                'reference' => 'PAY-'.$booking->reference.'-'.time(),
                'booking_id' => $booking->id,
                'developer_id' => $developer->id,
                'booking_reference' => $booking->reference,
                'description' => $booking->description,
                'subaccount_code' => $developer->paystack_subaccount_code,
                'callback_url' => $data['callback_url'] ?? null,
            ]);

            // Store the Paystack reference on the booking
            $booking->update([
                'paystack_reference' => $transaction['reference'],
                'paystack_access_code' => $transaction['access_code'],
                'payment_url' => $transaction['authorization_url'],
            ]);

            return response()->json([
                'access_code' => $transaction['access_code'],
                'authorization_url' => $transaction['authorization_url'],
                'reference' => $transaction['reference'],
            ]);

        } catch (\Exception $e) {
            Log::error('Payment initialization failed', [
                'booking' => $booking->reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Payment initialization failed'], 500);
        }
    }

    /**
     * GET /payments/verify/{reference}
     * Can be called by SDK to confirm payment after redirect.
     */
    public function verify(Request $request, string $reference): JsonResponse
    {
        $developer = $request->get('developer');

        try {
            $txData = $this->paystack->verifyTransaction($reference);

            $booking = Booking::where('paystack_reference', $reference)
                ->where('developer_id', $developer->id)
                ->firstOrFail();

            if ($txData['status'] === 'success' && $booking->status !== 'paid') {
                $this->recordSuccessfulPayment($booking, $txData);
            }

            return response()->json([
                'status' => $txData['status'],
                'booking' => [
                    'reference' => $booking->reference,
                    'status' => $booking->fresh()->status,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Verification failed'], 500);
        }
    }

    /**
     * GET /dashboard/payments
     * Dashboard list — Sanctum auth.
     */
    public function dashboardList(Request $request): JsonResponse
    {
        $developer = $request->user();

        $payments = Payment::where('developer_id', $developer->id)
            ->with('booking:id,reference,description,customer_email')
            ->latest()
            ->paginate(20);

        return response()->json($payments);
    }

    // ─── Private ────────────────────────────────────────────────────────────────

    public function recordSuccessfulPayment(Booking $booking, array $txData): void
    {
        DB::transaction(function () use ($booking, $txData) {
            $split = $this->paystack->calculateSplit((int) $txData['amount']);

            // Create payment record
            Payment::create([
                'booking_id' => $booking->id,
                'developer_id' => $booking->developer_id,
                'paystack_reference' => $txData['reference'],
                'amount' => $txData['amount'],
                'platform_fee' => $split['platform_fee'],
                'developer_amount' => $split['developer_amount'],
                'paystack_fee' => $split['paystack_fee'],
                'currency' => $txData['currency'] ?? 'NGN',
                'channel' => $txData['channel'] ?? null,
                'ip_address' => $txData['ip_address'] ?? null,
                'paystack_metadata' => $txData,
                'status' => 'success',
                'paid_at' => now(),
            ]);

            // Update booking status
            $booking->markAsPaid($txData['reference']);
        });
    }
}
