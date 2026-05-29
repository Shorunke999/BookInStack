<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingCategory;
use App\Models\Payment;
use App\Models\Developer;
use App\Services\AnchorService;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookingController extends Controller
{
     public function __construct(
        private AnchorService   $anchor,
        private PaystackService $paystack,
    ) {}
    private function effectiveDeveloper(Request $request): Developer
    {
        $user = $request->user();
        return $user->isStaff() ? $user->owner : $user;
    }
    public function create(Request $request)
    {
        $developer  = $request->user();
        $activeService = $developer->activeService();
        $categories = $activeService->bookingCategories;
        return view('dashboard.bookings.create', compact('developer', 'categories','activeService'));
    }


     public function store(Request $request)
    {
        $developer  = auth()->user();
        $effectiveDeveloper = $this->effectiveDeveloper($request);
        $rules = [
            'customer_name'  => 'required|string|max:100',
            'customer_email' => 'required|email',
            'customer_phone' => 'nullable|string|max:20',
            'category_id'    => 'nullable|integer|exists:booking_categories,id',
            'amount'         => 'required|numeric|min:1',
            'description'    => 'required|string|max:255',
            'payment_method' => 'required|in:online,cash',
        ];
        // Mode-specific fields
        $mode = $developer->activeService()->booking_mode;
        if ($mode === 'reservation') {
            $rules['check_in']  = 'required|date';
            $rules['check_out'] = 'required|date|after:check_in';
        }
        if ($mode === 'appointment') {
            $rules['preferred_date'] = 'nullable|date';
            $rules['preferred_time'] = 'nullable|date_format:H:i';
        }
        if ($mode === 'ticket') {
            $rules['adults']   = 'nullable|integer|min:1';
            $rules['children'] = 'nullable|integer|min:0';
        }
        $data = $request->validate($rules);
        $cat = BookingCategory::find($data['category_id']);

        // if ($cat && $cat->slotsRemaining() <= 0) {
        //     return redirect()->back()->with([
        //        'error' => 'Sorry, that slot is filled. Please choose another.'
        //     ]);
        // }
        // Build booking payload
        $payload = [
            'developer_id'   => $effectiveDeveloper->id,
            'category_id'    => $data['category_id'] ?? null,
            'reference'      => 'BKG-' . strtoupper(Str::random(12)),
            'amount'         => $data['amount'],
            'description'    => $data['description'],
            'customer_name'  => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'payment_status'         => 'pending',
            'booked_via'     => 'dashboard',
            'payment_method' => $data['payment_method'],
            'booked_by'      => $request->user()->id,
            'booking_mode'   => $mode,
            'service_id'     => $developer->activeService()?->id,
            'adults'         => $data['adults']   ?? 1,
            'children'       => $data['children'] ?? 0,
        ];
        if ($mode === 'reservation') {
            $payload['check_in']  = $data['check_in'];
            $payload['check_out'] = $data['check_out'];
            $nights = max(1, \Carbon\Carbon::parse($data['check_in'])
                ->diffInDays(\Carbon\Carbon::parse($data['check_out'])));
            $payload['amount'] = $data['amount'] * $nights;
        }
        if ($mode === 'appointment') {
            $payload['preferred_date'] = $data['preferred_date'] ?? null;
            $payload['preferred_time'] = $data['preferred_time'] ?? null;
        }
        //$booking = Booking::create($payload);
        try {
            $booking = match ($mode) {
                // ── TICKET: atomic slot check + 15-min hold ────────────────────
                'ticket' => DB::transaction(function () use ($cat, $payload) {
                    if (!$cat) {
                        // No cat — no slot limit, just create
                        return Booking::create($payload);
                    }

                    // Lock this cat row — serialises concurrent requests
                    $cat = \App\Models\BookingCategory::lockForUpdate()->find($cat->id);
                    $hasCurrentBooking = Booking::where('category_id', $cat->id)
                                            ->where('customer_email',$payload['customer_email'])
                                            ->first();
                    if($hasCurrentBooking)
                    {
                        if($hasCurrentBooking->payment_status == 'paid')
                        {
                            throw new \Exception('TICKET SOLD TO THIS CUSTOMER EMAIL ALREADY!');
                        }
                        return $hasCurrentBooking;
                    }
                    if ($cat->total_slots !== null) {
                        // Count paid + non-expired pending only
                        $sold = Booking::where('category_id', $cat->id)
                            ->where(function ($q) {
                                $q->where('payment_status', 'paid')
                                ->orWhere(function ($q2) {
                                    $q2->where('payment_status', 'pending')
                                        ->where(function ($q3) {
                                            $q3->whereNull('booking_expires_at')
                                                ->orWhere('booking_expires_at', '>', now());
                                        });
                                });
                            })
                            ->count();

                        if ($sold >= $cat->total_slots) {
                            throw new \Exception('SOLD_OUT');
                        }
                    }

                    // Set 15-minute seat hold — expires if not paid
                    $payload['booking_expires_at'] = now()->addMinutes(15);

                    return Booking::create($payload);
                }),

                // ── RESERVATION: date-range overlap check ──────────────────────
                'reservation' => DB::transaction(function () use ($cat, $payload, $data) {

                    if ($cat) {
                        $requestedIn  = \Carbon\Carbon::parse($data['check_in']);
                        $requestedOut = \Carbon\Carbon::parse($data['check_out']);

                        // Lock category row
                        \App\Models\BookingCategory::lockForUpdate()->find($cat->id);

                        $overlapping = Booking::where('category_id', $cat->id)
                            ->where('payment_status', 'paid')
                            ->where('check_in',  '<', $requestedOut)
                            ->where('check_out', '>', $requestedIn)
                            ->count();

                        if ($cat->total_slots !== null && $overlapping >= $cat->total_slots) {
                            throw new \Exception(
                                "Sorry, {$cat->name} is fully booked for your selected dates.|DATES_UNAVAILABLE"
                            );
                        }
                    }

                    return Booking::create($payload);
                }),
                // ── APPOINTMENT: prevent duplicate date/time bookings ───────────────
                'appointment' => DB::transaction(function () use ($cat, $payload) {

                    if (
                        !empty($payload['preferred_date']) &&
                        !empty($payload['preferred_time'])
                    ) {
                        $exists = Booking::where('service_id', $payload['service_id'])
                            ->where('category_id', $cat->id)
                            ->whereDate('preferred_date', $payload['preferred_date'])
                            ->where('preferred_time', $payload['preferred_time'])
                            ->where('payment_status', 'paid')
                            ->exists();

                        if ($exists) {
                            throw new \Exception(
                                'Sorry, this appointment time is already booked.|TIME_SLOT_FILLED'
                            );
                        }
                    }
                    return Booking::create($payload);
                }),
                // ── APPOINTMENT + default: simple create ──────────────────────
                default => Booking::create($payload),
            };
        } catch (\Exception $e) {
            $parts   = explode('|', $e->getMessage(), 2);
            $message = $parts[0];
            $error   = $parts[1] ?? 'BOOKING_FAILED';

            $status = match ($error) {
                'SOLD_OUT'          => 422,
                'DATES_UNAVAILABLE' => 422,
                'TIME_SLOT_FILLED'  => 422,
                default             => 500,
            };

            $response = ['message' => $message === 'SOLD_OUT'
                ? 'Sorry, this ticket type is sold out.'
                : $message
            ];

            if (isset($parts[1])) {
                $response['error'] = $error;
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $response['message']);
        }

                // ── Payment method branch ─────────────────────────────────────────────

        if ($data['payment_method'] === 'online') {
            do { $token = Str::random(12); }
            while (Booking::where('payment_link_token', $token)->exists());

            $booking->payment_link_token = $token;
            //$booking->payment_link_expires_at = now()->addDays(1);
            $booking->save();
            return redirect()->route(
                'payment.link.dashboard',
                $booking->payment_link_token
            );
            //return $this->initializeTransferPayment($booking, $developer);
        }elseif($data['payment_method'] === 'cash')
        {
            $booking->markAsPaid();
            $cost = 0;
             // Create payment record
            Payment::create([
                'booking_id' => $booking->id,
                'developer_id' => $booking->developer_id,
                'amount' => $booking->amount,
                'paystack_reference' => '',
                'platform_fee' =>  $cost,
                'developer_amount' => $booking->amount - $cost,
                'currency' =>'NGN',
                'channel' => 'cash',
                'status' => 'success',
                'paid_at' => now(),
            ]);
            return redirect()->route('dashboard.bookings')->with('success', 'Booking created and marked as paid.')  ;
        }
    }

      // ─── Transfer: generate Anchor VA ────────────────────────────────────────

    private function initializeTransferPayment(Booking $booking, Developer $developer): JsonResponse
    {
        try {
            $amountKobo = (int) $booking->amount * 100;

            $va = $this->anchor->createPayWithTransfer(
                bookingReference: $booking->reference,
                amountKobo:       $amountKobo,
                customerName:     $booking->customer_name,
                customerEmail:    $booking->customer_email,
                expirySeconds:    1800,
            );

            $attrs = $va['attributes'];

            $booking->update([
                'anchor_virtual_account_number' => $attrs['accountNumber'],
                'anchor_va_bank_name'           => $attrs['bank']['name'],
                'anchor_va_account_name'        => $attrs['accountName'],
                'anchor_va_reference'           => $attrs['reference'],
                'anchor_va_expires_at'          => now()->addSeconds(1800),
            ]);

            return response()->json([
                'method'         => 'transfer',
                'booking'        => [
                    'reference'      => $booking->reference,
                    'customer_name'  => $booking->customer_name,
                    'customer_email' => $booking->customer_email,
                    'customer_phone' => $booking->customer_phone,
                    'amount'         => $booking->amount,
                    'description'    => $booking->description,
                ],
                'virtual_account' => [
                    'account_number' => $attrs['accountNumber'],
                    'bank_name'      => $attrs['bank']['name'],
                    'account_name'   => $attrs['accountName'],
                    'expires_at'     => now()->addSeconds(1800)->toISOString(),
                    'expires_in_min' => 30,
                ],
            ]);

        } catch (\Exception $e) {
            $booking->delete();
            Log::error('Anchor VA creation failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Could not generate transfer details. Please try again.'], 500);
        }
    }

    // ─── Card: Paystack initialize ────────────────────────────────────────────

    private function initializePaystackPayment(Booking $booking, Developer $developer)
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
                'callback_url'       => route('dashboard.bookings'),
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

    // ─── Resend VA (refresh expired virtual account) ──────────────────────────

    public function refreshVirtualAccount(Request $request, string $reference): JsonResponse
    {
        $developer = $this->effectiveDeveloper($request);

        $booking = Booking::where('reference', $reference)
            ->where('developer_id', $developer->id)
            ->where('payment_status', 'pending')
            ->firstOrFail();

        return $this->initializeTransferPayment($booking, $developer);
    }

     // ─── Mark attended (existing) ─────────────────────────────────────────────

    public function dashboardMarkAttended(Request $request, string $reference): RedirectResponse
    {
        $developer = $this->effectiveDeveloper($request);
        $booking   = Booking::where('reference', $reference)
            ->where('developer_id', $developer->id)
            ->firstOrFail();

        $attended = (bool) $request->input('attended', 1);

        $booking->update([
            'attended'         => $attended,
            'attended_at'      => $attended ? now() : null,
            'attended_by'      => $attended ? $request->user()->id : null,
            'attendance_note'  => $request->input('note'),
        ]);

        return back()->with('success', $attended ? 'Marked as attended.' : 'Attendance undone.');
    }

    public function dashboardShow(string $token): View
    {
        $booking = Booking::where('payment_link_token', $token)
            ->firstOrFail();

        $wa = preg_replace('/\D/', '', $booking->customer_phone ?? '');

        $text = urlencode(
            "Hi {$booking->customer_name}, complete your payment here: "
            . route('payment.link', $booking->payment_link_token)
        );

        $whatsappUrl = $wa
            ? "https://wa.me/{$wa}?text={$text}"
            : "https://wa.me/?text={$text}";

        return view(
            'dashboard.bookings.payment-link',
            compact('booking', 'whatsappUrl')
        );
    }

    public function publicShow(string $token): View
    {
        $booking = Booking::where('payment_link_token', $token)
            ->with('developer', 'service')
            ->firstOrFail();

        abort_if(
            $booking->payment_status === 'paid',
            403,
            'This booking has already been paid.'
        );

        return view('pay.booking', compact('booking'));
    }

    public function continue(string $token)
    {
        try {
            $booking = Booking::where('payment_link_token', $token)
                ->with('developer')
                ->firstOrFail();
            if ($booking->payment_status === 'paid') {
                return redirect()->back()->with('error', 'Payment already completed for this booking.');
            }

            $payment = $this->initializePaystackPayment(
                $booking,
                $booking->developer
            );
            if ($payment['status'] === 'error') {
                return redirect()->back()->with('error', $payment['message']);
            }
            return redirect()->away($payment['authorization_url']);

        } catch (\Throwable $e) {

            Log::error('Hosted payment init failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Unable to continue payment. Please try again.');
        }
    }
}
