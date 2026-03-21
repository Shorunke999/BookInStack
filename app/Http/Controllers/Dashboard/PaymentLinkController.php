<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PaymentLink;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Models\Developer;
class PaymentLinkController extends Controller
{
    public function __construct(private PaystackService $paystack) {}

    private function effectiveDeveloper(Request $request): Developer
    {
        $developer = $request->user();
        // Staff see their admin's data
        return $developer->isStaff() ? $developer->owner : $developer;
    }
    public function index(Request $request): View
    {
          $developer = $this->effectiveDeveloper($request);
        $links     = PaymentLink::where('developer_id', $developer->id)
            ->with('category')->latest()->paginate(20);
        return view('dashboard.payment-links.index', compact('developer', 'links'));
    }

    public function create(Request $request): View
    {
        $developer = $this->effectiveDeveloper($request);
        $categories = $developer->bookingCategories()
            ->active()->forMode($developer->booking_mode)->orderBy('sort_order')->get();
        return view('dashboard.payment-links.create', compact('developer', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
       $developer = $this->effectiveDeveloper($request);
        $data = $request->validate([
            'customer_name'  => 'required|string|max:100',
            'customer_email' => 'required|email',
            'customer_phone' => 'nullable|string|max:20',
            'category_id'    => 'nullable|integer|exists:booking_categories,id',
            'amount'         => 'required|integer|min:100',
            'description'    => 'required|string|max:255',
            'note'           => 'nullable|string|max:500',
            'expires_hours'  => 'nullable|integer|min:1|max:720',
        ]);
        $link = PaymentLink::create([
            'token'          => PaymentLink::generateToken(),
            'developer_id'   => $developer->id,
            'category_id'    => $data['category_id'] ?? null,
            'customer_name'  => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'amount'         => $data['amount'],
            'description'    => $data['description'],
            'note'           => $data['note'] ?? null,
            'status'         => 'pending',
            'expires_at'     => isset($data['expires_hours'])
                ? now()->addHours((int) $data['expires_hours']) : null,
        ]);

        return redirect()->route('payment-links.show-dashboard', $link->token)
            ->with('success', 'Payment link created! Share it with your customer.');
    }

    public function showDashboard(string $token): View
    {
       $developer = $this->effectiveDeveloper(request());
        $link      = PaymentLink::where('token', $token)
            ->where('developer_id', $developer->id)
            ->with('category')->firstOrFail();

        $wa  = preg_replace('/\D/', '', $developer->whatsapp_number ?? '');
        $txt = urlencode($link->whatsappText());
        $whatsappUrl = $wa
            ? "https://wa.me/{$wa}?text={$txt}"
            : "https://wa.me/?text={$txt}";

        return view('dashboard.payment-links.show', compact('link', 'whatsappUrl', 'developer'));
    }

    public function cancel(string $token): RedirectResponse
    {
       $developer = $this->effectiveDeveloper(request());
        PaymentLink::where('token', $token)
            ->where('developer_id', $developer->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
        return redirect()->route('payment-links.index')->with('success', 'Link cancelled.');
    }

    // ── PUBLIC: payment page ──────────────────────────────────────────────────

    public function publicShow(string $token): View
    {
        $link = PaymentLink::where('token', $token)
            ->with('developer', 'category')->firstOrFail();

        if ($link->isExpired() && $link->status === 'pending') {
            $link->update(['status' => 'expired']);
        }

        return view('pay.show', compact('link'));
    }

   // ── PUBLIC: init payment + create booking ─────────────────────────────────
 
    public function publicPay(Request $request, string $token): JsonResponse
    {
        $link = PaymentLink::where('token', $token)
            ->where('status', 'pending')
            ->with('developer', 'category')
            ->firstOrFail();
 
        if ($link->isExpired()) {
            $link->update(['status' => 'expired']);
            return response()->json(['message' => 'Payment link has expired.'], 422);
        }
 
        // ── Booking window check ───────────────────────────────────────────────
        $dev = $link->developer;
        if ($dev->enable_booking_window && $dev->booking_window) {
            $window    = $dev->booking_window;
            $now       = now();
            $dayName   = strtolower($now->format('l'));
            $time      = $now->format('H:i');
            $openDays  = $window['days']       ?? [];
            $openFrom  = $window['open_time']  ?? '00:00';
            $openUntil = $window['close_time'] ?? '23:59';
 
            if (!in_array($dayName, $openDays)) {
                return response()->json(['message' => 'Bookings are not accepted on ' . ucfirst($dayName) . 's.'], 422);
            }
            if ($time < $openFrom || $time > $openUntil) {
                return response()->json(['message' => "Bookings are accepted between {$openFrom} and {$openUntil}."], 422);
            }
        }
 
        $mode = $link->category?->booking_mode ?? $link->developer->booking_mode;
 
        // ── Validate mode-specific fields ──────────────────────────────────────
        $rules = [
            'customer_name'  => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:20',
        ];
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
 
        // ── Calculate final amount ─────────────────────────────────────────────
        $amount = $link->amount;
 
        if ($mode === 'reservation' && $link->category) {
            $nights = max(1, \Carbon\Carbon::parse($data['check_in'])
                ->diffInDays(\Carbon\Carbon::parse($data['check_out'])));
            $amount = $link->category->price * $nights;
        }
 
        // if ($mode === 'ticket' && $link->category) {
        //     $adults      = (int) ($data['adults']   ?? 1);
        //     $children    = (int) ($data['children'] ?? 0);
        //     $childPrice  = ($link->category->enable_child_pricing && $link->category->child_price)
        //         ? $link->category->child_price
        //         : $link->category->price;
        //     $amount = ($link->category->price * $adults) + ($childPrice * $children);
        // }
 
        // ── Create booking record ──────────────────────────────────────────────
        $payload = [
            'developer_id'   => $link->developer_id,
            'category_id'    => $link->category_id,
            'reference'      => 'BKG-' . strtoupper(Str::random(12)),
            'amount'         => $link->category?->price ?? $link->amount,
            'description'    => $link->description,
            'customer_email' => $link->customer_email ,
            'customer_name'  => $data['customer_name']  ?? $link->customer_name,
            'customer_phone' => $data['customer_phone'] ?? $link->customer_phone,
            'status'         => 'pending',
            'adults'         => 1,
            'children'       => 0,
            'metadata'       => ['payment_link_token' => $token],
        ];
 
        if ($mode === 'reservation') {
            $payload['check_in']  = $data['check_in'];
            $payload['check_out'] = $data['check_out'];
        }
        if ($mode === 'appointment') {
            $payload['preferred_date'] = $data['preferred_date'] ?? null;
            $payload['preferred_time'] = $data['preferred_time'] ?? null;
        }
        // if ($mode === 'ticket') {
        //     $payload['adults']   = $data['adults']   ?? 1;
        //     $payload['children'] = $data['children'] ?? 0;
        // }
 
        $booking = Booking::create($payload);
 
        // ── Initialize Paystack ────────────────────────────────────────────────
        try {
            $response = $this->paystack->initializeTransaction([
                'customer_email'              => $link->customer_email,
                'amount'             => $amount,
                'booking_id'  =>  $booking->id,
                'booking_reference' => $booking->reference,
                'developer_id' => $link->developer_id,
                'description' => $link->description ?? null,
                'reference'          => 'PLK-' . strtoupper(Str::random(10)),
                'callback_url'       => url("/pay/{$token}?paid=1&booking={$booking->reference}"),
                'metadata'           => [
                    'payment_link_token' => $token,
                    'booking_reference'  => $booking->reference,
                    'customer_name'      => $link->customer_name,
                    'description'        => $link->description,
                ],
                'subaccount'         => $link->developer->paystack_subaccount_code,
                'bearer'             => 'subaccount',
                'transaction_charge' => (int) round($amount * 0.05),
            ]);
 
            return response()->json([
                'authorization_url' => $response['authorization_url'],
                'booking_reference' => $booking->reference,
            ]);
        } catch (\Exception $e) {
            $booking->delete();
            return response()->json([
                'message' => 'Payment initialization failed: ' . $e->getMessage()
            ], 500);
        }
    }
}