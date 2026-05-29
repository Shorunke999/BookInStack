<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Developer;
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
        try {
             $service = $request->get('service');

            $data = $request->validate([
                'booking_reference' => 'required|string',
                'callback_url' => 'nullable|url',
            ]);

            $booking = Booking::where('reference', $data['booking_reference'])
                ->where('service_id', $service->id)
                ->where('payment_status', 'pending')
                ->firstOrFail();


            $payment = $this->initializePaystackPayment(
                $booking,
                $booking->developer,
                $data['callback_url']
            );
            if ($payment['status'] === 'error') {
                return response()->json(['message' => $payment['message']], 400);
            }
            return response()->json([
                    'authorization_url' => $payment['authorization_url'],
                     'reference' => $payment['reference'],
                ],200);;


        } catch (\Exception $e) {
              Log::error('Payment initialization failed', [
                'booking' => $booking->reference,
                'error'   => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Payment initialization failed.'], 500);
        }
      }
    private function initializePaystackPayment(Booking $booking, Developer $developer, $callbackUrl)
    {
        try {
            $amountKobo         = (int) $booking->amount * 100;
            $split = $this->paystack->calculateSplit((int) $amountKobo,$booking->developer);
            $platformFeeKobo    = $split['platform_fee'];
            $paystackFeeKobo    = $split['paystack_fee'];
            $transactionCharge  = max($platformFeeKobo, $paystackFeeKobo);

            $tx = $this->paystack->initializeTransaction([
                'customer_email'     => $booking->customer_email,
                'amount'             => $amountKobo,
                'reference'          => 'PAY-' . $booking->reference . '-' . time(),
                'booking_id'         => $booking->id,
                'developer_id'       => $developer->id,
                'booking_reference'  => $booking->reference,
                'description'        => $booking->description,
                'subaccount_code'    => $developer->paystack_subaccount_code,
                'transaction_charge' => $transactionCharge,
                'bearer'             => 'account',
                'callback_url'       => $callbackUrl,
            ]);

            $booking->update(['paystack_reference' => $tx['reference']]);

            return [
                'status'            => 'success',
                'method'            => 'online',
                'authorization_url' => $tx['authorization_url'],
                'reference'         => $tx['reference'],
                'booking_reference' => $booking->reference,
            ];

        } catch (\Exception $e) {
            Log::error('Paystack init failed for dashboard booking', ['error' => $e->getMessage()]);
            return [
                'status'  => 'error',
                'message' => 'Online payment initialization failed.',
                'error'   => $e->getMessage()
            ];
        }
    }
    // public function initialize(Request $request): JsonResponse
    // {
    //     $developer = $request->get('developer');

    //     $data = $request->validate([
    //         'booking_reference' => 'required|string',
    //         'callback_url' => 'nullable|url',
    //     ]);

    //     $booking = Booking::where('reference', $data['booking_reference'])
    //         ->where('developer_id', $developer->id)
    //         ->where('payment_status', 'pending')
    //         ->firstOrFail();

    //     // Idempotency — if virtual account already generated and not expired, return it
    //     if ($booking->anchor_virtual_account_number && $booking->anchor_va_expires_at?->isFuture()) {
    //         return response()->json([
    //             'account_number' => $booking->anchor_virtual_account_number,
    //             'bank_name'      => $booking->anchor_va_bank_name,
    //             'account_name'   => $booking->anchor_va_account_name,
    //             'amount'         => $booking->amount,
    //             'expires_at'     => $booking->anchor_va_expires_at,
    //             'reference'      => $booking->reference,
    //         ]);
    //     }

    //    try {
    //     $amountKobo = (int) $booking->amount * 100;

    //     $virtualAccount = app(\App\Services\AnchorService::class)->createPayWithTransfer(
    //         bookingReference: $booking->reference,
    //         amountKobo:       $amountKobo,
    //         customerName:     $booking->customer_name,
    //         customerEmail:    $booking->customer_email,
    //         expirySeconds:    1800,  // 30 minutes
    //     );

    //     $attrs = $virtualAccount['attributes'];

    //     // Store virtual account details on the booking
    //     $booking->update([
    //         'anchor_virtual_account_number' => $attrs['accountNumber'],
    //         'anchor_va_bank_name'           => $attrs['bank']['name'],
    //         'anchor_va_account_name'        => $attrs['accountName'],
    //         'anchor_va_reference'           => $attrs['reference'],
    //         'anchor_va_expires_at'          => now()->addSeconds(1800),
    //     ]);

    //     return response()->json([
    //         'account_number' => $attrs['accountNumber'],
    //         'bank_name'      => $attrs['bank']['name'],
    //         'account_name'   => $attrs['accountName'],
    //         'amount'         => $booking->amount,
    //         'expires_at'     => now()->addSeconds(1800),
    //         'reference'      => $booking->reference,
    //     ]);

    // } catch (\Exception $e) {
    //     Log::error('Payment initialization failed', [
    //         'booking' => $booking->reference,
    //         'error'   => $e->getMessage(),
    //     ]);

    //     return response()->json(['message' => 'Payment initialization failed.'], 500);
    // }
    // }

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
                ->where('payment_status', 'pending')
                ->firstOrFail();

            if ($txData['status'] === 'success' && $booking->payment_status !== 'paid') {
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
            $split = $this->paystack->calculateSplit((int) $txData['amount'],$booking->developer);
            Log::info("Recording successful payment for booking {$booking->reference}", [
                'amount' => $txData['amount'],
                'split' => $split,
            ]);
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
        Log::info("Payment record created for booking {$booking->reference}");
            // Update booking status
            $booking->markAsPaid();
            // $token = $booking->payment_link_token;
            // if ($token) {
            //     PaymentLink::where('token', $token)->update(['payment_status' => 'paid', 'booking_id' => $booking->id]);
            // }
            // Log::info("Booking {$booking->reference} marked as paid : {$booking->payment_status}");
        });
    }
}
