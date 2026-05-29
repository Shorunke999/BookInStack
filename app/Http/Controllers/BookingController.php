<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BookingController extends Controller
{
    /**
     * POST /bookings
     * Called by the SDK — authenticated via public key.
     */
    public function create(Request $request): JsonResponse
    {
        /** @var \App\Models\Developer $developer */
        $service = $request->get('service');
        $developer = $service->developer();
        $mode      = $service->booking_mode;

        // ── Validation ─────────────────────────────────────────────────────────
        $rules = [
            'category_id'    => 'nullable|integer|exists:booking_categories,id',
            'amount'         => 'nullable|integer|min:100',
            'description'    => 'nullable|string|max:255',
            'customer_email' => 'required|email',
            'customer_name'  => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:20',
            'metadata'       => 'nullable|array',
        ];

        if ($mode === 'ticket') {
            $rules['adults']   = 'nullable|integer|min:1';
            $rules['children'] = 'nullable|integer|min:0';
        }
        if ($mode === 'reservation') {
            $rules['check_in']  = 'required|date|after_or_equal:today';
            $rules['check_out'] = 'required|date|after:check_in';
        }
        if ($mode === 'appointment') {
            $rules['preferred_date'] = 'nullable|date|after_or_equal:today';
            $rules['preferred_time'] = 'nullable|date_format:H:i';
        }

        $data = $request->validate($rules);

        // ── Resolve category → amount + description ────────────────────────────
        $category    = null;
        $amount      = $data['amount'] ?? null;
        $description = $data['description'] ?? null;

        if (!empty($data['category_id'])) {
            $category = \App\Models\BookingCategory::where('id', $data['category_id'])
                ->where('service_id', $service->id)
                ->where('booking_mode', $mode)
                ->where('status', 'active')
                ->first();

            if (!$category) {
                return response()->json(['message' => 'Invalid or inactive category.'], 422);
            }

            $description = $description ?? $category->name;

            $amount = match ($mode) {
                'ticket'      => $this->ticketAmount($category, $data),
                'reservation' => $this->reservationAmount($category, $data),
                default       => $category->price,
            };
        }

        if (!$amount || $amount < 100) {
            return response()->json(['message' => 'amount is required when no category is set.'], 422);
        }
        if (!$description) {
            return response()->json(['message' => 'description is required when no category is set.'], 422);
        }
        do { $token = Str::random(12); }
        while (Booking::where('payment_link_token', $token)->exists());
        // ── Build base payload ─────────────────────────────────────────────────
        $payload = [
            'service_id' => $service->id,
            'developer_id'   => $service->developer->id,
            'category_id'    => $category?->id,
            'reference'      => 'BKG-' . strtoupper(\Illuminate\Support\Str::random(12)),
            'amount'         => $amount,
            'description'    => $description,
            'booking_mode'   => $mode,
            'customer_email' => $data['customer_email'],
            'customer_name'  => $data['customer_name']  ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'payment_status' => 'pending',
            'booking_status' => 'active',
            'metadata'       => $data['metadata'] ?? null,
            'adults'         => $data['adults']   ?? 1,
            'children'       => $data['children'] ?? 0,
            'booked_via'   => 'widget',        // always widget for SDK bookings
            'booked_by_id' => null,
            'payment_link_token' => $token
        ];

        if ($mode === 'reservation') {
            $payload['check_in']  = $data['check_in'];
            $payload['check_out'] = $data['check_out'];
            $payload['amount']    = $this->reservationAmount($category, $data);
        }
        if ($mode === 'appointment') {
            $payload['preferred_date'] = $data['preferred_date'] ?? null;
            $payload['preferred_time'] = $data['preferred_time'] ?? null;
        }

        // ── Create booking with appropriate locking per mode ───────────────────
        try {
            $booking = match ($mode) {

                // ── TICKET: atomic slot check + 15-min hold ────────────────────
                'ticket' => DB::transaction(function () use ($category, $payload) {
                    if (!$category) {
                        // No category — no slot limit, just create
                        return Booking::create($payload);
                    }

                    // Lock this category row — serialises concurrent requests
                    $cat = \App\Models\BookingCategory::lockForUpdate()->find($category->id);
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
                'reservation' => DB::transaction(function () use ($category, $payload, $data) {
                    if ($category) {
                        $requestedIn  = \Carbon\Carbon::parse($data['check_in']);
                        $requestedOut = \Carbon\Carbon::parse($data['check_out']);

                        // Lock category row
                        \App\Models\BookingCategory::lockForUpdate()->find($category->id);

                        $overlapping = Booking::where('category_id', $category->id)
                            ->where('payment_status', 'paid')
                            ->where('check_in',  '<', $requestedOut)
                            ->where('check_out', '>', $requestedIn)
                            ->count();

                        if ($category->total_slots !== null && $overlapping >= $category->total_slots) {
                            throw new \Exception(
                                "Sorry, {$category->name} is fully booked for your selected dates.|DATES_UNAVAILABLE"
                            );
                        }
                    }

                    return Booking::create($payload);
                }),
                // ── APPOINTMENT: prevent duplicate date/time bookings ───────────────
                'appointment' => DB::transaction(function () use ($category, $payload) {

                    if (
                        !empty($payload['preferred_date']) &&
                        !empty($payload['preferred_time'])
                    ) {
                        $exists = Booking::where('service_id', $payload['service_id'])
                            ->where('category_id', $category->id)
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

            return response()->json($response, $status);
        }

        return response()->json([
            'booking' => $this->formatBooking($booking->load('category'), $mode),
        ], 201);
    }

    /**
     * GET /bookings
     * List bookings for the authenticated developer (public key auth).
     */
    // public function list(Request $request): JsonResponse
    // {
    //     $developer = $request->get('developer');

    //     $bookings = Booking::where('developer_id', $developer->id)
    //         ->select(['id', 'reference', 'amount', 'description', 'customer_email', 'status', 'created_at', 'paid_at'])
    //         ->latest()
    //         ->paginate(20);

    //     return response()->json($bookings);
    // }

    /**
     * GET /bookings/{reference}
     */
    // public function show(Request $request, string $reference): JsonResponse
    // {
    //     $developer = $request->get('developer');

    //     $booking = Booking::where('reference', $reference)
    //         ->where('developer_id', $developer->id)
    //         ->with('payment')
    //         ->firstOrFail();

    //     return response()->json(['booking' => $booking]);
    // }

    /**
     * GET /dashboard/bookings
     * Dashboard view — authenticated via Sanctum token.
     */
    // public function dashboardList(Request $request): JsonResponse
    // {
    //     $developer = $request->user();

    //     $query = Booking::where('developer_id', $developer->id);

    //     // Filters
    //     if ($request->status) {
    //         $query->where('status', $request->status);
    //     }

    //     if ($request->search) {
    //         $query->where(function ($q) use ($request) {
    //             $q->where('reference', 'like', "%{$request->search}%")
    //                 ->orWhere('customer_email', 'like', "%{$request->search}%")
    //                 ->orWhere('description', 'like', "%{$request->search}%");
    //         });
    //     }

    //     $bookings = $query->latest()->paginate(20);

    //     return response()->json($bookings);
    // }

      /**
     * GET /booking-window/status
     * Returns window status + mode + catalog for the SDK.
     */
    public function getBookingWindowStatus(Request $request): JsonResponse
    {
        /** @var \App\Models\Developer $developer */
        $service  = $request->get('service');
        $mode       = $service->booking_mode;
        $modeConfig = $service->modeConfig();

        // ── Booking window check ───────────────────────────────────────────────
        $open   = true;
        $reason = null;

        if ($service->enable_booking_window && $service->booking_window) {

            $window    = $service->booking_window;
            $now       = now();
            $dayName   = strtolower($now->format('l'));
            $time      = $now->format('H:i');
            $openDays  = $window['days']  ?? [];
            $openFrom  = $window['open_time']  ?? '00:00';
            $openUntil = $window['close_time'] ?? '23:59';

            if (!in_array($dayName, $openDays)) {
                $open   = false;
                $reason = 'Bookings are not accepted on ' . ucfirst($dayName) . '.';
            } elseif ($time < $openFrom || $time > $openUntil) {
                $open   = false;
                $reason = "Bookings are accepted between {$openFrom} and {$openUntil}.";
            }
        }

        // ── Catalog ────────────────────────────────────────────────────────────
        $catalog = $service->bookingCategories()
            ->forMode($mode)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn($cat) => $cat->toApiArray($mode))
            ->values();
        return response()->json([
            'open'         => $open,
            'reason'       => $reason,
            'booking_mode' => $mode,
            'enable_negotiate' => $service->enable_negotiate ?? false,
            'whatsapp_number'  => $service->whatsapp_number ?? '',
            'catalog'      => $catalog,
            'widget_config'    => $service->widget_config ?? (object)[],
            'reservation_unit' => $service->reservation_unit ?? null
        ]);
    }

    /**
     * POST /bookings/{reference}/attend
     */
    // public function markAttended(Request $request, string $reference): JsonResponse
    // {
    //     $developer = $request->get('developer');

    //     $booking = Booking::where('reference', $reference)
    //         ->where('developer_id', $developer->id)
    //         ->where('payment_status', 'paid')   // can only mark paid bookings
    //         ->firstOrFail();

    //     $data = $request->validate([
    //         'attended' => 'required|boolean',
    //         'note' => 'nullable|string|max:500',
    //     ]);

    //     $booking->update([
    //         'attended' => $data['attended'],
    //         'attended_at' => $data['attended'] ? now() : null,
    //         'attended_by_id'  => auth()->id(),
    //         'attendance_note' => $data['note'] ?? null,
    //     ]);

    //     return response()->json([
    //         'message' => $data['attended'] ? 'Marked as attended' : 'Marked as not attended',
    //         'booking' => $booking->fresh(),
    //     ]);
    // }

    /**
     * Dashboard version — Sanctum auth
     * POST /dashboard/bookings/{reference}/attend
     */
   public function dashboardMarkAttended(Request $request, string $reference)
{
    $developer = $request->user()->effectiveDeveloper();

    $booking = Booking::where('reference', $reference)
        ->where('developer_id', $developer->id)
        ->where('payment_status', 'paid')
        ->with('category')
        ->first();

    if (!$booking) {
        return $request->wantsJson()
            ? response()->json([
                'message' => 'Booking not found or payment not completed.'
            ], 404)
            : back()->withErrors([
                'booking' => 'Booking not found or payment not completed.'
            ]);
    }

    $data = $request->validate([
        'booking_status' => 'required|in:checked_in,completed,no_show',
        'note'           => 'nullable|string|max:500',
    ]);

    $cat = $booking->category;
    $now = now();

    // ── Enforce attendance/check-in rules only for checked_in ──────────────
    if ($data['booking_status'] === 'checked_in' && $cat) {

        // ── Global category date window ─────────────────────────────────────
        if (
            $cat->checkin_start_date &&
            $now->toDateString() < $cat->checkin_start_date
        ) {

            $msg = 'Check-in not open yet. Opens '
                . \Carbon\Carbon::parse($cat->checkin_start_date)->format('d M Y');

            return $request->wantsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->withErrors(['booking_status' => $msg]);
        }

        if (
            $cat->checkin_end_date &&
            $now->toDateString() > $cat->checkin_end_date
        ) {

            $msg = 'Check-in window has closed.';

            return $request->wantsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->withErrors(['booking_status' => $msg]);
        }

        // ── Global category time window ─────────────────────────────────────
        if (
            $cat->checkin_start_time &&
            $now->format('H:i:s') < $cat->checkin_start_time
        ) {

            $msg = 'Check-in opens at '
                . \Carbon\Carbon::parse($cat->checkin_start_time)->format('g:i A');

            return $request->wantsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->withErrors(['booking_status' => $msg]);
        }

        if (
            $cat->checkin_end_time &&
            $now->format('H:i:s') > $cat->checkin_end_time
        ) {

            $msg = 'Check-in closed at '
                . \Carbon\Carbon::parse($cat->checkin_end_time)->format('g:i A');

            return $request->wantsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->withErrors(['booking_status' => $msg]);
        }

        // ── Reservation Mode ────────────────────────────────────────────────
        if (
            $booking->booking_mode === 'reservation' &&
            $booking->check_in
        ) {

            $earliest = \Carbon\Carbon::parse($booking->check_in)
                ->subDays($cat->checkin_days_before ?? 0)
                ->startOfDay();

            $latest = \Carbon\Carbon::parse(
                    $booking->check_out ?? $booking->check_in
                )
                ->addDays($cat->checkin_days_after ?? 0)
                ->endOfDay();

            if ($now->lt($earliest)) {

                $msg = 'Too early to check in. Earliest: '
                    . $earliest->format('d M Y');

                return $request->wantsJson()
                    ? response()->json(['message' => $msg], 422)
                    : back()->withErrors(['booking_status' => $msg]);
            }

            if ($now->gt($latest)) {

                $msg = 'Check-in window has passed.';

                return $request->wantsJson()
                    ? response()->json(['message' => $msg], 422)
                    : back()->withErrors(['booking_status' => $msg]);
            }
        }

        // ── Appointment Mode ────────────────────────────────────────────────
        if (
            $booking->booking_mode === 'appointment' &&
            $booking->preferred_date
        ) {

            $earliest = \Carbon\Carbon::parse($booking->preferred_date)
                ->subDays($cat->checkin_days_before ?? 0)
                ->startOfDay();

            $latest = \Carbon\Carbon::parse($booking->preferred_date)
                ->addDays($cat->checkin_days_after ?? 0)
                ->endOfDay();

            if ($now->lt($earliest)) {

                $msg = 'Too early to check in. Appointment is on '
                    . \Carbon\Carbon::parse($booking->preferred_date)
                        ->format('d M Y');

                return $request->wantsJson()
                    ? response()->json(['message' => $msg], 422)
                    : back()->withErrors(['booking_status' => $msg]);
            }

            if ($now->gt($latest)) {

                $msg = 'Attendance window has passed for this appointment.';

                return $request->wantsJson()
                    ? response()->json(['message' => $msg], 422)
                    : back()->withErrors(['booking_status' => $msg]);
            }
        }

        // ── Ticket/Event Mode ───────────────────────────────────────────────
        if (
            $booking->booking_mode === 'ticket' &&
            $booking->booking_status === 'checked_in'
        ) {

            $msg = 'This ticket has already been checked in.';

            return $request->wantsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->withErrors(['booking_status' => $msg]);
        }
    }

    // ── Prevent invalid transitions ────────────────────────────────────────
    if (in_array($booking->booking_status, [
        'completed',
        'cancelled',
        'expired',
        'no_show',
    ])) {

        $msg = 'This booking can no longer be updated.';

        return $request->wantsJson()
            ? response()->json(['message' => $msg], 422)
            : back()->withErrors(['booking_status' => $msg]);
    }

    // ── Update booking state ───────────────────────────────────────────────
    $booking->update([

        'booking_status' => $data['booking_status'],

        'attended_at' => $data['booking_status'] === 'checked_in'
            ? now()
            : $booking->attended_at,

        'attendance_note' => $data['note'] ?? $booking->attendance_note,
    ]);

    // ── Human readable success message ─────────────────────────────────────
    $message = match ($data['booking_status']) {

        'checked_in' => match ($booking->booking_mode) {
            'reservation' => 'Guest checked in successfully.',
            'appointment' => 'Appointment attendance confirmed.',
            'ticket'      => 'Ticket validated successfully.',
            default       => 'Booking checked in successfully.',
        },

        'completed' => match ($booking->booking_mode) {
            'reservation' => 'Guest checked out successfully.',
            default       => 'Booking marked as completed.',
        },

        'no_show' => 'Booking marked as no-show.',

        default => 'Booking updated successfully.',
    };

    return $request->wantsJson()
        ? response()->json(['message' => $message], 200)
        : back()->with('success', $message);
}

    /**
     * GET /booking-window/status
     * Returns booking window status AND mode config for the SDK.
     */
    public function windowStatus(Request $request): JsonResponse
    {
        $developer = $request->get('developer');
        $modeConfig = $developer->modeConfig();

        // ── Booking window check ───────────────────────────────────────────────
        $open = true;
        $reason = null;

        if ($developer->enable_booking_window && $developer->booking_window) {
            $window = $developer->booking_window;
            $now = now();
            $dayName = strtolower($now->format('l')); // monday, tuesday ...
            $time = $now->format('H:i');

            $openDays = $window['days'] ?? [];
            $openFrom = $window['open_time'] ?? '00:00';
            $openUntil = $window['close_time'] ?? '23:59';

            if (! in_array($dayName, $openDays)) {
                $open = false;
                $reason = 'Bookings are not accepted on '.ucfirst($dayName).'.';
            } elseif ($time < $openFrom || $time > $openUntil) {
                $open = false;
                $reason = "Bookings are only accepted between {$openFrom} and {$openUntil}.";
            }
        }

        return response()->json([
            'open' => $open,
            'reason' => $reason,
            'booking_mode' => $developer->booking_mode,
            'mode_config' => $modeConfig,
        ]);
    }
    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Format a booking for API responses — include mode-relevant fields.
     */
    private function formatBooking(Booking $booking, string $mode): array
    {
        $base = [
            'id' => $booking->id,
            'reference' => $booking->reference,
            'amount' => $booking->amount,
            'description' => $booking->description,
            'customer_email' => $booking->customer_email,
            'customer_name' => $booking->customer_name,
            'customer_phone' => $booking->customer_phone,
            'status' => $booking->status,
            'attended' => $booking->attended,
            'attended_at' => $booking->attended_at,
            'attendance_note' => $booking->attendance_note,
            'paid_at' => $booking->paid_at,
            'created_at' => $booking->created_at,
            'metadata' => $booking->metadata,
        ];

        if ($mode === 'ticket') {
            $base['quantity'] = $booking->quantity;
            $base['unit_price'] = $booking->amount;
            $base['total_amount'] = $booking->totalAmount();
        }

        if ($mode === 'reservation') {
            $base['check_in'] = $booking->check_in?->format('Y-m-d');
            $base['check_out'] = $booking->check_out?->format('Y-m-d');
            $base['nights'] = $booking->nights();
        }

        if ($mode === 'appointment') {
            $base['preferred_date'] = $booking->preferred_date?->format('Y-m-d');
            $base['preferred_time'] = $booking->preferred_time;
        }

        return $base;
    }
    // ── Add these two private helpers if not already present ──────────────────

    private function ticketAmount(\App\Models\BookingCategory $cat, array $data): int
    {
        $adults   = (int) ($data['adults']   ?? 1);
        $children = (int) ($data['children'] ?? 0);

        $total = $cat->price * $adults;

        if ($cat->enable_child_pricing && $cat->child_price && $children > 0) {
            $total += $cat->child_price * $children;
        } else {
            $total += $cat->price * $children;
        }

        return $total;
    }

    private function reservationAmount(\App\Models\BookingCategory $cat, array $data): int
    {
        $nights = max(1, \Carbon\Carbon::parse($data['check_in'])
            ->diffInDays(\Carbon\Carbon::parse($data['check_out'])));

        return $cat->price * $nights;
    }
}
