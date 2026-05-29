<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Developer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use League\Csv\Writer;
use SplTempFileObject;

class ExportController extends Controller
{
     private function effectiveDeveloper(Request $request): Developer
    {
        $developer = $request->user();
        // Staff see their admin's data
        return $developer->isStaff() ? $developer->owner : $developer;
    }

    public function exportBookings(Request $request)
    {
        Log::info('Export started', ['user' => $request->user()?->id]);
        $developer = $this->effectiveDeveloper($request);

        // Get the active service
        $activeService = $developer->activeService();
        if(!$activeService) {
            return redirect()->back()->with('error', 'No active service found. Please create a service first.');
        }
        $query = $activeService->bookings()
                    ->with(['developer', 'category', 'service'])
                     ->whereHas('developer', function($q) use ($developer) {
                            $q->where('id', $developer->id);
                    });

        // $query->where('service_id', $activeService->id);

        // Apply date filters
        $this->applyDateFilters($query, $request);

        // Apply status filter
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Apply booking mode filter (via service or category)
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Get bookings
        $bookings = $query->orderBy('created_at', 'desc')->get();

        // Calculate metrics
        $metrics = $this->calculateMetrics($bookings, $developer, $activeService);

        // Create CSV
        $csv = Writer::createFromFileObject(new SplTempFileObject());

        // Set CSV headers for UTF-8 with BOM
        $csv->setOutputBOM(Writer::BOM_UTF8);

        // Add metrics sheet (as comments/notes in CSV)
        if ($request->include_metrics === 'yes') {
            $this->addMetricsSection($csv, $metrics);
            $csv->insertOne([]); // Empty row separator
            $csv->insertOne(['=== BOOKINGS DATA ===']);
            $csv->insertOne([]); // Empty row separator
        }

        // Add main bookings headers
        $headers = $this->getBookingHeaders();
        $csv->insertOne($headers);

        // Add booking rows
        foreach ($bookings as $booking) {
            $csv->insertOne($this->formatBookingRow($booking));
        }

        // Generate filename
        $filename = sprintf(
            'bookings_export_%s_%s.csv',
            $activeService ? $activeService->slug : 'all',
            Carbon::now()->format('Y-m-d_His')
        );

        // Return CSV download
        return Response::make($csv->toString(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function applyDateFilters($query, Request $request)
    {
        $range = $request->date_range;
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        switch ($range) {
            case 'today':
                $query->whereDate('created_at', Carbon::today());
                break;
            case 'yesterday':
                $query->whereDate('created_at', Carbon::yesterday());
                break;
            case 'this_week':
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'last_week':
                $query->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
                break;
            case 'this_month':
                $query->whereMonth('created_at', Carbon::now()->month)
                      ->whereYear('created_at', Carbon::now()->year);
                break;
            case 'last_month':
                $lastMonth = Carbon::now()->subMonth();
                $query->whereMonth('created_at', $lastMonth->month)
                      ->whereYear('created_at', $lastMonth->year);
                break;
            case 'custom':
                if ($fromDate) {
                    $query->whereDate('created_at', '>=', Carbon::parse($fromDate));
                }
                if ($toDate) {
                    $query->whereDate('created_at', '<=', Carbon::parse($toDate));
                }
                break;
        }
    }

    private function calculateMetrics($bookings, $developer, $activeService)
    {
        $totalRevenue = $bookings->where('payment_status', 'paid')->sum('amount');
        $paidBookings = $bookings->where('payment_status', 'paid');
        $pendingBookings = $bookings->where('payment_status', 'pending');
        $cancelledBookings = $bookings->where('payment_status', 'cancelled');

        $averageOrderValue = $paidBookings->count() > 0
            ? $totalRevenue / $paidBookings->count()
            : 0;

        // Group by booking mode
        $byMode = [
            'ticket' => $bookings->filter(fn($b) => $b->service && $b->service->booking_mode === 'ticket'),
            'reservation' => $bookings->filter(fn($b) => $b->service && $b->service->booking_mode === 'reservation'),
            'appointment' => $bookings->filter(fn($b) => $b->service && $b->service->booking_mode === 'appointment'),
        ];

        // Top categories/services
        $topCategories = $bookings->groupBy('category_id')
            ->map(fn($group) => [
                'name' => $group->first()->category?->name ?? 'Uncategorized',
                'count' => $group->count(),
                'revenue' => $group->where('payment_status', 'paid')->sum('amount'),
            ])
            ->sortByDesc('revenue')
            ->take(5);

        // Attendance metrics
        $attendedBookings = $bookings->where('attended', true);
        $attendanceRate = $bookings->count() > 0
            ? ($attendedBookings->count() / $bookings->count()) * 100
            : 0;

        // Customer metrics
        $uniqueCustomers = $bookings->unique('customer_email')->count();
        $repeatCustomers = $bookings->groupBy('customer_email')
            ->filter(fn($group) => $group->count() > 1)
            ->count();

        // Time-based metrics
        $last30Days = $bookings->filter(fn($b) => $b->created_at >= Carbon::now()->subDays(30));
        $growth = $last30Days->count() - $bookings->filter(fn($b) => $b->created_at->between(Carbon::now()->subDays(60), Carbon::now()->subDays(30)))->count();

        // Risk metrics
        $flaggedBookings = $bookings->whereNotNull('flagged_at');
        $highRiskBookings = $bookings->where('risk_level', 'high');

        return [
            'business' => [
                'business_name' => $developer->business_name ?? $developer->name,
                'service_name' => $activeService?->name ?? 'All Services',
                'booking_mode' => $activeService?->booking_mode ?? 'Multiple',
                'export_date' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            'revenue' => [
                'total_revenue' => $totalRevenue,
                'total_revenue_formatted' => '₦' . number_format($totalRevenue, 2),
                'average_order_value' => $averageOrderValue,
                'average_order_formatted' => '₦' . number_format($averageOrderValue, 2),
            ],
            'volume' => [
                'total_bookings' => $bookings->count(),
                'paid_bookings' => $paidBookings->count(),
                'pending_bookings' => $pendingBookings->count(),
                'cancelled_bookings' => $cancelledBookings->count(),
                'conversion_rate' => $bookings->count() > 0 ? ($paidBookings->count() / $bookings->count()) * 100 : 0,
            ],
            'modes' => [
                'ticket_count' => $byMode['ticket']->count(),
                'ticket_revenue' => $byMode['ticket']->where('payment_status', 'paid')->sum('amount'),
                'reservation_count' => $byMode['reservation']->count(),
                'reservation_revenue' => $byMode['reservation']->where('payment_status', 'paid')->sum('amount'),
                'appointment_count' => $byMode['appointment']->count(),
                'appointment_revenue' => $byMode['appointment']->where('payment_status', 'paid')->sum('amount'),
            ],
            'attendance' => [
                'attended_bookings' => $attendedBookings->count(),
                'attendance_rate' => round($attendanceRate, 2),
                'pending_attendance' => $paidBookings->where('attended', false)->count(),
            ],
            'customers' => [
                'unique_customers' => $uniqueCustomers,
                'repeat_customers' => $repeatCustomers,
                'repeat_rate' => $uniqueCustomers > 0 ? ($repeatCustomers / $uniqueCustomers) * 100 : 0,
            ],
            'top_categories' => $topCategories,
            'growth' => [
                'last_30_days_bookings' => $last30Days->count(),
                'period_over_period_growth' => $growth,
                'growth_percentage' => $bookings->filter(fn($b) => $b->created_at->between(Carbon::now()->subDays(60), Carbon::now()->subDays(30)))->count() > 0
                    ? round(($growth / $bookings->filter(fn($b) => $b->created_at->between(Carbon::now()->subDays(60), Carbon::now()->subDays(30)))->count()) * 100, 2)
                    : 0,
            ],
            'risk' => [
                'flagged_bookings' => $flaggedBookings->count(),
                'high_risk_bookings' => $highRiskBookings->count(),
            ],
        ];
    }

    private function addMetricsSection($csv, $metrics)
    {
        $csv->insertOne(['BUSINESS METRICS SUMMARY']);
        $csv->insertOne([]);

        // Business Info
        $csv->insertOne(['BUSINESS INFORMATION']);
        $csv->insertOne(['Business Name', $metrics['business']['business_name']]);
        $csv->insertOne(['Service', $metrics['business']['service_name']]);
        $csv->insertOne(['Booking Mode', $metrics['business']['booking_mode']]);
        $csv->insertOne(['Export Date', $metrics['business']['export_date']]);
        $csv->insertOne([]);

        // Revenue Metrics
        $csv->insertOne(['REVENUE METRICS']);
        $csv->insertOne(['Total Revenue', $metrics['revenue']['total_revenue_formatted']]);
        $csv->insertOne(['Average Order Value', $metrics['revenue']['average_order_formatted']]);
        $csv->insertOne([]);

        // Volume Metrics
        $csv->insertOne(['VOLUME METRICS']);
        $csv->insertOne(['Total Bookings', $metrics['volume']['total_bookings']]);
        $csv->insertOne(['Paid Bookings', $metrics['volume']['paid_bookings']]);
        $csv->insertOne(['Pending Bookings', $metrics['volume']['pending_bookings']]);
        $csv->insertOne(['Cancelled Bookings', $metrics['volume']['cancelled_bookings']]);
        $csv->insertOne(['Conversion Rate', $metrics['volume']['conversion_rate'] . '%']);
        $csv->insertOne([]);

        // Booking Modes Breakdown
        $csv->insertOne(['BOOKING MODES BREAKDOWN']);
        $csv->insertOne(['Mode', 'Count', 'Revenue']);
        $csv->insertOne(['Ticket', $metrics['modes']['ticket_count'], '₦' . number_format($metrics['modes']['ticket_revenue'], 2)]);
        $csv->insertOne(['Reservation', $metrics['modes']['reservation_count'], '₦' . number_format($metrics['modes']['reservation_revenue'], 2)]);
        $csv->insertOne(['Appointment', $metrics['modes']['appointment_count'], '₦' . number_format($metrics['modes']['appointment_revenue'], 2)]);
        $csv->insertOne([]);

        // Attendance
        $csv->insertOne(['ATTENDANCE METRICS']);
        $csv->insertOne(['Attended Bookings', $metrics['attendance']['attended_bookings']]);
        $csv->insertOne(['Attendance Rate', $metrics['attendance']['attendance_rate'] . '%']);
        $csv->insertOne(['Pending Attendance', $metrics['attendance']['pending_attendance']]);
        $csv->insertOne([]);

        // Customer Metrics
        $csv->insertOne(['CUSTOMER METRICS']);
        $csv->insertOne(['Unique Customers', $metrics['customers']['unique_customers']]);
        $csv->insertOne(['Repeat Customers', $metrics['customers']['repeat_customers']]);
        $csv->insertOne(['Repeat Rate', round($metrics['customers']['repeat_rate'], 2) . '%']);
        $csv->insertOne([]);

        // Top Categories
        if ($metrics['top_categories']->count() > 0) {
            $csv->insertOne(['TOP PERFORMING CATEGORIES']);
            $csv->insertOne(['Category', 'Bookings', 'Revenue']);
            foreach ($metrics['top_categories'] as $category) {
                $csv->insertOne([$category['name'], $category['count'], '₦' . number_format($category['revenue'], 2)]);
            }
            $csv->insertOne([]);
        }

        // Growth Metrics
        $csv->insertOne(['GROWTH METRICS']);
        $csv->insertOne(['Last 30 Days Bookings', $metrics['growth']['last_30_days_bookings']]);
        $csv->insertOne(['Period-over-Period Growth', $metrics['growth']['period_over_period_growth']]);
        $csv->insertOne(['Growth Percentage', $metrics['growth']['growth_percentage'] . '%']);
        $csv->insertOne([]);

        // Risk Metrics
        $csv->insertOne(['RISK METRICS']);
        $csv->insertOne(['Flagged Bookings', $metrics['risk']['flagged_bookings']]);
        $csv->insertOne(['High Risk Bookings', $metrics['risk']['high_risk_bookings']]);
        $csv->insertOne([]);
    }

    private function getBookingHeaders()
    {
        return [
            'Booking Reference',
            'Status',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Service',
            'Category',
            'Booking Mode',
            'Amount (₦)',
            'Quantity',
            'Description',
            'Booking Date',
            'Paid Date',
            'Attendance Status',
            'Attended Date',
            'Attendance Note',
            'Check In Date',
            'Check Out Date',
            'Preferred Date',
            'Preferred Time',
            'Adults',
            'Children',
            'Payment Method',
            'Risk Score',
            'Risk Level',
            'Flagged',
            'IP Address',
            'Booked By',
            'Booked Via',
        ];
    }

    private function formatBookingRow($booking)
    {
        // Calculate nights for reservations
        $nights = null;
        if ($booking->service && $booking->service->booking_mode === 'reservation') {
            $nights = $booking->nights();
        }

        // Format amount with nights info for reservations
        $amount = $booking->amount;
        if ($booking->service && $booking->service->booking_mode === 'reservation' && $nights) {
            $amount = $booking->amount . ' (₦' . number_format($booking->amount / $nights, 2) . '/night × ' . $nights . ' nights)';
        } elseif ($booking->quantity > 1) {
            $amount = $booking->amount . ' (₦' . number_format($booking->amount / $booking->quantity, 2) . '/unit × ' . $booking->quantity . ')';
        } else {
            $amount = '₦' . number_format($booking->amount, 2);
        }

        return [
            $booking->reference,
            ucfirst($booking->status),
            $booking->customer_name,
            $booking->customer_email,
            $booking->customer_phone,
            $booking->service?->name ?? 'N/A',
            $booking->category?->name ?? 'N/A',
            $booking->service?->booking_mode ?? 'N/A',
            $amount,
            $booking->quantity ?? 1,
            $booking->description,
            $booking->created_at?->format('Y-m-d H:i:s'),
            $booking->paid_at?->format('Y-m-d H:i:s'),
            $booking->attended ? 'Attended' : 'Not Attended',
            $booking->attended_at?->format('Y-m-d H:i:s'),
            $booking->attendance_note,
            $booking->check_in?->format('Y-m-d'),
            $booking->check_out?->format('Y-m-d'),
            $booking->preferred_date?->format('Y-m-d'),
            $booking->preferred_time,
            $booking->adults ?? 0,
            $booking->children ?? 0,
            $booking->payment_method ?? 'N/A',
            $booking->risk_score ?? 'N/A',
            $booking->risk_level ?? 'N/A',
            $booking->flagged_at ? 'Yes' : 'No',
            $booking->ip_address ?? 'N/A',
            $booking->bookedBy?->name ?? 'N/A',
            $booking->booked_via ?? 'N/A',
        ];
    }
}
