<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Developer;
use Illuminate\Http\Request;
use App\Services\PaystackService;

class SuperAdminController extends Controller
{
    // ── Dashboard overview ────────────────────────────────────────────────────

    public function index()
    {
        $stats = [
            'total_developers' => Developer::whereNull('owner_id')->where('role','admin')->count(),
            'active_developers'=> Developer::whereNull('owner_id')->where('status','active')->count(),
            'total_bookings'   => Booking::count(),
            'paid_bookings'    => Booking::where('payment_status','paid')->count(),
            'total_revenue'    => Booking::where('payment_status','paid')->sum('amount'), // kobo
            'platform_revenue' => $this->calcPlatformRevenue(),
        ];

        $recentDevelopers = Developer::whereNull('owner_id')
            ->where('role','admin')
            ->latest()->limit(5)->get();

        $recentBookings = Booking::with('developer')
            ->where('payment_status','paid')
            ->latest()->limit(10)->get();

        return view('superadmin.dashboard', compact('stats', 'recentDevelopers', 'recentBookings'));
    }

    // ── Developers list ───────────────────────────────────────────────────────

    public function developers(Request $request)
    {
        $query = Developer::whereNull('owner_id')
            ->where('role','admin')
            ->withCount('bookings')
            ->withSum(['bookings as paid_amount' => fn($q) => $q->where('payment_status','paid')], 'amount');

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name',  'like', '%'.$request->search.'%')
                  ->orWhere('email','like', '%'.$request->search.'%')
                  ->orWhere('business_name','like','%'.$request->search.'%');
            });
        }

        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        $developers = $query->latest()->paginate(25);

        return view('superadmin.developers', compact('developers'));
    }

    // ── Show single developer ─────────────────────────────────────────────────

    public function showDeveloper(int $id)
    {
        $developer = Developer::whereNull('owner_id')
            ->where('role','admin')
            ->withCount('bookings')
            ->findOrFail($id);

        $bookings = Booking::where('developer_id', $id)
            ->where('payment_status','paid')
            ->latest()->limit(20)->get();

        $totalPaid      = Booking::where('developer_id',$id)->where('payment_status','paid')->sum('amount');
        $platformEarned = (int) round($totalPaid * ($developer->platform_fee_percent / 100));

        return view('superadmin.developer-show', compact('developer','bookings','totalPaid','platformEarned'));
    }

    // ── Update platform fee ───────────────────────────────────────────────────

    public function updateFee(Request $request, int $id)
    {
        $data = $request->validate([
            'platform_fee_percent' => 'required|numeric|min:0|max:50',
        ]);
        $developer = Developer::whereNull('owner_id')->findOrFail($id);
        $developer->update(['platform_fee_percent' => $data['platform_fee_percent']]);

        $paystackService = new PaystackService();
        $paystackService->updateSubaccountFee($developer->paystack_subaccount_code,$data['platform_fee_percent']);
        return back()->with('success', 'Platform fee updated.');
    }

    // ── Update developer status ───────────────────────────────────────────────

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => 'required|in:active,suspended,pending',
        ]);

        Developer::whereNull('owner_id')->findOrFail($id)
            ->update(['status' => $data['status']]);

        return back()->with('success', 'Status updated.');
    }

    // ── All bookings across platform ──────────────────────────────────────────

    public function bookings(Request $request)
    {
        $bookings = Booking::with('developer')
            ->where('payment_status','paid')
            ->when($request->developer_id, fn($q) => $q->where('developer_id', $request->developer_id))
            ->latest()->paginate(30);

        $developers = Developer::whereNull('owner_id')->where('status','active')
            ->orderBy('name')->get(['id','name','business_name']);

        return view('superadmin.bookings', compact('bookings','developers'));
    }

    // ── Revenue breakdown ─────────────────────────────────────────────────────

    public function revenue(Request $request)
    {
        $developers = Developer::whereNull('owner_id')
            ->where('role','admin')
            ->withSum(['bookings as paid_volume' => fn($q) => $q->where('payment_status','paid')], 'amount')
            ->orderByDesc('paid_volume')
            ->get()
            ->map(function ($dev) {
                $dev->platform_earned = (int) round(
                    ($dev->paid_volume ?? 0) * ($dev->platform_fee_percent / 100)
                );
                $dev->developer_earned = ($dev->paid_volume ?? 0) - $dev->platform_earned;
                return $dev;
            });

        $totals = [
            'volume'   => $developers->sum('paid_volume'),
            'platform' => $developers->sum('platform_earned'),
            'payout'   => $developers->sum('developer_earned'),
        ];

        return view('superadmin.revenue', compact('developers','totals'));
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function calcPlatformRevenue(): int
    {
        return Developer::whereNull('owner_id')->get()->sum(function ($dev) {
            $paid = Booking::where('developer_id', $dev->id)->where('payment_status','paid')->sum('amount');
            return (int) round($paid * ($dev->platform_fee_percent / 100));
        });
    }
}
