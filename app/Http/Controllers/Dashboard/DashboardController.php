<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Developer;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private PaystackService $paystack) {}

    private function effectiveDeveloper(Request $request): Developer
    {
        $developer = $request->user();
        // Staff see their admin's data
        return $developer->isStaff() ? $developer->owner : $developer;
    }
    // ─── Overview ────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        if(!Auth::check())
        {
            return redirect()->route('login');
        }
        $developer = $this->effectiveDeveloper($request);

        //redirect super admin
        if($developer->isSuperAdmin())
        {
            return redirect()->route('superadmin.dashboard');
        }
        
        $bookin_mode = $developer->booking_mode;
        $stats = [

             'total_revenue'   => $developer->payments()->where('status', 'success')->sum('developer_amount') / 100,
            'monthly_revenue' => $developer->payments()->where('status', 'success')
                                    ->whereMonth('created_at', now()->month)->sum('developer_amount') / 100,
            'total_bookings'  => $developer->bookings()->where('booking_mode', $bookin_mode)->count(),
            'paid_bookings'   => $developer->bookings()->where('booking_mode', $bookin_mode)->where('status', 'paid')->count(),
            'pending_bookings'=> $developer->bookings()->where('booking_mode', $bookin_mode)->where('status', 'pending')->count(),
            'attended'        => $developer->bookings()->where('booking_mode', $bookin_mode)->where('attended', true)->count(),

        ];

        $recentBookings = $developer->bookings()
            ->where('booking_mode', $bookin_mode)
            ->latest()
            ->take(6)
            ->get();
         $modeConfig = $developer->modeConfig();
        return view('dashboard.index', compact('stats', 'recentBookings', 'modeConfig'));
    }

    // ─── Bookings ─────────────────────────────────────────────────────────────────
    public function bookings(Request $request): View
    {
        $developer = $request->user();
        $bookin_mode = $developer->booking_mode;
        $query = $developer->bookings()->where('booking_mode', $bookin_mode)->with('category')->latest();
        $modeConfig = $developer->modeConfig();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $bookings = $query->paginate(20);
         $categories = $developer->bookingCategories()
                        ->forMode($developer->booking_mode)
                        ->active()
                        ->orderBy('sort_order')
                        ->get();
        return view('dashboard.bookings', compact('bookings', 'developer', 'modeConfig', 'categories'));
    }

    // ─── Payments ─────────────────────────────────────────────────────────────────
    public function payments(Request $request): View
    {
        $developer = $request->user();

        $payments = $developer->payments()
            ->with('booking:id,reference,category_id', 'booking.category:id,name')
            ->where('status', 'success')
            ->latest()
            ->paginate(20);
        $stats = [
            'total_settled'       => $developer->payments()->where('status', 'success')->sum('developer_amount') / 100,
            'total_fees'          => $developer->payments()->where('status', 'success')->sum('platform_fee') / 100,
            'total_transactions'  => $developer->payments()->where('status', 'success')->count(),
            'this_month'          => $developer->payments()->where('status', 'success')
                                        ->whereMonth('created_at', now()->month)->sum('developer_amount') / 100,
        ];
         return view('dashboard.payments', compact('payments', 'stats', 'developer'));
    }

    public function banks(): JsonResponse
    {
        try {
            $banks = $this->paystack->listBanks();
            return response()->json(['banks' => $banks]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Could not fetch banks'], 503);
        }
    }


    // ─── API Keys ─────────────────────────────────────────────────────────────────
    public function apiKeys(Request $request): View
    {
        $developer = $request->user();
        $banks     = [];

        if (! $developer->nin_verified) {
            try {
                $banks = $this->paystack->listBanks();
            } catch (\Exception $e) {
                // Non-fatal — form still renders, select will just be empty
            }
        }

        return view('dashboard.api-keys', compact('developer', 'banks'));
    }

    /**
     * POST /dashboard/api-keys/regenerate
     */
    public function regenerateKey(Request $request): RedirectResponse
    {
        $developer = $request->user();

        if (! $developer->nin_verified) {
            return back()->with('error', 'NIN verification required before managing keys.');
        }

        $newKey = 'pk_live_' . \Illuminate\Support\Str::random(40);
        $developer->update(['public_key' => $newKey]);

        return back()->with('success', 'API key regenerated. Update your integrations immediately.');
    }

    // ─── Integration ──────────────────────────────────────────────────────────────
    public function integration(Request $request): View
    {
        $developer = $request->user();
        $modeConfig = $developer->modeConfig();
        $categories = $developer->bookingCategories()
                        ->forMode($developer->booking_mode)
                        ->active()
                        ->orderBy('sort_order')
                        ->get();

        return view('dashboard.integration', compact('developer', 'modeConfig', 'categories'));
    }

    // ─── Booking Window Settings ─────────────────────────────────────────────────
    public function bookingSettings(Request $request): View
    {
        $developer  = $this->effectiveDeveloper($request);
        $modeConfig = $developer->modeConfig();
        $categories = $developer->bookingCategories()
                        ->forMode($developer->booking_mode)
                        ->orderBy('sort_order')
                        ->get();

        return view('dashboard.booking-settings', compact('developer', 'modeConfig', 'categories'));
    }


     public function saveBookingSettings(Request $request): RedirectResponse
    {
        $developer = $this->effectiveDeveloper($request);
 
        $request->validate([
            'booking_mode'          => 'required|in:appointment,ticket,reservation',
            'reservation_unit'      => 'nullable|in:night,day',
            'enable_negotiate'      => 'boolean',
            'whatsapp_number'       => 'nullable|string|max:20',
            'enable_booking_window' => 'boolean',
            'window_days'           => 'nullable|array',
            'window_days.*'         => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'open_time'             => 'nullable|date_format:H:i',
            'close_time'            => 'nullable|date_format:H:i|after:open_time',
        ]);
 
        $developer->update([
            'booking_mode'          => $request->booking_mode,
            'reservation_unit'      => $request->input('reservation_unit', 'night'),
            'enable_negotiate'      => $request->boolean('enable_negotiate'),
            'whatsapp_number'       => $request->input('whatsapp_number'),
            'enable_booking_window' => $request->boolean('enable_booking_window'),
            'booking_window'        => [
                'days'       => $request->input('window_days', []),
                'open_time'  => $request->input('open_time',  '09:00'),
                'close_time' => $request->input('close_time', '17:00'),
            ],
        ]);
 
        return back()->with('success', 'Booking settings saved.');
    }

    
    // ─── Widget Appearance ────────────────────────────────────────────────────────
 
    public function saveWidgetAppearance(Request $request): RedirectResponse
    {
        $developer = $this->effectiveDeveloper($request);
 
        $request->validate([
            'bg_type'       => 'required|in:none,color,image',
            'bg_color'      => 'nullable|string|max:20',
            'bg_image_url'  => 'nullable|url|max:500',
            'accent_color'  => 'nullable|string|max:20',
            'border_radius' => 'nullable|integer|min:0|max:28',
            'show_branding' => 'boolean',
        ]);
 
        $developer->update([
            'widget_config' => [
                'bg_type'       => $request->input('bg_type', 'none'),
                'bg_color'      => $request->input('bg_color',     '#f5f3ff'),
                'bg_image_url'  => $request->input('bg_image_url', ''),
                'accent_color'  => $request->input('accent_color', '#4f46e5'),
                'border_radius' => (int) $request->input('border_radius', 14),
                'show_branding' => $request->boolean('show_branding'),
            ],
        ]);
 
        return back()->with('success', 'Widget appearance saved.');
    }
    // ─── Staff ───────────────────────────────────────────────────────────────────

    public function staff(Request $request): View
    {
        $developer   = $this->effectiveDeveloper($request);
        $staffList   = $developer->staff()->latest()->get();

        return view('dashboard.staff.index', compact('developer', 'staffList'));
}
}
