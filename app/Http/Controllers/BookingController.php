<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $developer = $request->get('developer');
        $mode      = $developer->booking_mode;

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
        Log::info('Creating booking with data: ', $request->all());
        // ── Resolve category → amount + description ────────────────────────────
        $category    = null;
        $amount      = $data['amount'] ?? null;
        $description = $data['description'] ?? null;

        if (!empty($data['category_id'])) {
            $category = \App\Models\BookingCategory::where('id', $data['category_id'])
                ->where('developer_id', $developer->id)
                ->where('booking_mode', $mode)
                ->first();

            if (!$category) {
                return response()->json(['message' => 'Invalid or inactive category.'], 422);
            }

            $description = $description ?? $category->name;

            $amount = match ($mode) {
                'ticket' => $this->ticketAmount($category, $data),
                'reservation' => $this->reservationAmount($category, $data),
                default => $category->price,
            };
        }

        if (!$amount || $amount < 100) {
            return response()->json(['message' => 'amount is required when no category is set.'], 422);
        }
        if (!$description) {
            return response()->json(['message' => 'description is required when no category is set.'], 422);
        }
        if ($mode === 'reservation' && $category) {
            $requested_in  = \Carbon\Carbon::parse($data['check_in']);
            $requested_out = \Carbon\Carbon::parse($data['check_out']);

            // Count paid/pending bookings for this category that overlap the requested dates
            $overlapping = \App\Models\Booking::where('category_id', $category->id)
                ->whereIn('status', ['paid'])
                ->where(function ($q) use ($requested_in, $requested_out) {
                    // Overlap condition: existing booking starts before new end AND ends after new start
                    $q->where('check_in',  '<', $requested_out)
                    ->where('check_out', '>', $requested_in);
                })
                ->count();
            if ($category->total_slots !== null && $overlapping >= $category->total_slots) {
                return response()->json([
                    'message' => "Sorry, {$category->name} is fully booked for your selected dates. Please choose different dates or another option.",
                    'error'   => 'DATES_UNAVAILABLE',
                ], 422);
            }
        }

        // ── Create booking ─────────────────────────────────────────────────────
        $payload = [
            'developer_id'   => $developer->id,
            'category_id'    => $category?->id,
            'reference'      => 'BKG-' . strtoupper(\Illuminate\Support\Str::random(12)),
            'amount'         => $amount,
            'description'    => $description,
            'booking_mode' => $mode,
            'customer_email' => $data['customer_email'],
            'customer_name'  => $data['customer_name']  ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'status'         => 'pending',
            'metadata'       => $data['metadata'] ?? null,
            'adults'         => $data['adults']   ?? 1,
            'children'       => $data['children'] ?? 0,
        ];

        if ($mode === 'reservation') {
            $payload['check_in']  = $data['check_in'];
            $payload['check_out'] = $data['check_out'];
            $payload['amount'] = $this->reservationAmount($category, $data);
        }
        if ($mode === 'appointment') {
            $payload['preferred_date'] = $data['preferred_date'] ?? null;
            $payload['preferred_time'] = $data['preferred_time'] ?? null;
        }
        $booking = Booking::create($payload);

        return response()->json([
            'booking' => $this->formatBooking($booking->load('category'), $mode),
        ], 201);
    }

    /**
     * GET /bookings
     * List bookings for the authenticated developer (public key auth).
     */
    public function list(Request $request): JsonResponse
    {
        $developer = $request->get('developer');

        $bookings = Booking::where('developer_id', $developer->id)
            ->select(['id', 'reference', 'amount', 'description', 'customer_email', 'status', 'created_at', 'paid_at'])
            ->latest()
            ->paginate(20);

        return response()->json($bookings);
    }

    /**
     * GET /bookings/{reference}
     */
    public function show(Request $request, string $reference): JsonResponse
    {
        $developer = $request->get('developer');

        $booking = Booking::where('reference', $reference)
            ->where('developer_id', $developer->id)
            ->with('payment')
            ->firstOrFail();

        return response()->json(['booking' => $booking]);
    }

    /**
     * GET /dashboard/bookings
     * Dashboard view — authenticated via Sanctum token.
     */
    public function dashboardList(Request $request): JsonResponse
    {
        $developer = $request->user();

        $query = Booking::where('developer_id', $developer->id);

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('reference', 'like', "%{$request->search}%")
                    ->orWhere('customer_email', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        $bookings = $query->latest()->paginate(20);

        return response()->json($bookings);
    }

      /**
     * GET /booking-window/status
     * Returns window status + mode + catalog for the SDK.
     */
    public function getBookingWindowStatus(Request $request): JsonResponse
    {
        /** @var \App\Models\Developer $developer */
        $developer  = $request->get('developer');
        $mode       = $developer->booking_mode;
        $modeConfig = $developer->modeConfig();

        // ── Booking window check ───────────────────────────────────────────────
        $open   = true;
        $reason = null;

        if ($developer->enable_booking_window && $developer->booking_window) {
            $window    = $developer->booking_window;
            $now       = now();
            $dayName   = strtolower($now->format('l'));
            $time      = $now->format('H:i');
            $openDays  = $window['days']       ?? [];
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
        $catalog = $developer->bookingCategories()
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
            'catalog'      => $catalog,
            'widget_config'    => $developer->widget_config ?? (object)[],
            'reservation_unit' => $developer->reservation_unit ?? null
        ]);
    }

    /**
     * POST /bookings/{reference}/attend
     */
    public function markAttended(Request $request, string $reference): JsonResponse
    {
        $developer = $request->get('developer');

        $booking = Booking::where('reference', $reference)
            ->where('developer_id', $developer->id)
            ->where('status', 'paid')   // can only mark paid bookings
            ->firstOrFail();

        $data = $request->validate([
            'attended' => 'required|boolean',
            'note' => 'nullable|string|max:500',
        ]);

        $booking->update([
            'attended' => $data['attended'],
            'attended_at' => $data['attended'] ? now() : null,
            'attendance_note' => $data['note'] ?? null,
        ]);

        return response()->json([
            'message' => $data['attended'] ? 'Marked as attended' : 'Marked as not attended',
            'booking' => $booking->fresh(),
        ]);
    }

    /**
     * Dashboard version — Sanctum auth
     * POST /dashboard/bookings/{reference}/attend
     */
    public function dashboardMarkAttended(Request $request, string $reference): RedirectResponse
    {
        $developer = $request->user();

        $booking = Booking::where('reference', $reference)
            ->where('developer_id', $developer->id)
            ->where('status', 'paid')
            ->firstOrFail();

        $data = $request->validate([
            'attended' => 'required|boolean',
            'note' => 'nullable|string|max:500',
        ]);

        $booking->update([
            'attended' => $data['attended'],
            'attended_at' => $data['attended'] ? now() : null,
            'attendance_note' => $data['note'] ?? null,
        ]);

        return back()->with('success', 'Attendance updated.');
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
