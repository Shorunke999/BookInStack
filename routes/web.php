<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Dashboard\BookingCategoryController;
use App\Http\Controllers\Dashboard\PaymentLinkController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\NinController;
use App\Http\Controllers\Dashboard\StaffController;
use App\Http\Controllers\DeveloperDomainController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Dashboard routes use standard Laravel session auth (auth middleware).
| API routes (for the SDK) remain in routes/api.php with public key auth.
|
*/

// ── Public legal pages ────────────────────────────────────────────────────────
Route::get('/privacy', fn() => view('legal.privacy'))->name('privacy');
Route::get('/terms',   fn() => view('legal.terms'))->name('terms');

// ─── Guest routes ────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

        // Forgot / reset password
    Route::get('/forgot-password',       [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password',      [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password',       [AuthController::class, 'resetPassword'])->name('password.update');


// ─── Email verification (auth not required — link comes via email) ────────────
Route::get('/email/verify',                 [AuthController::class, 'verificationNotice'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}',     [AuthController::class, 'verificationVerify'])->middleware(['signed'])->name('verification.verify');
Route::post('/email/verification-notification', [AuthController::class, 'verificationSend'])->middleware(['throttle:6,1'])->name('verification.send');

});
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
// ─── Authenticated dashboard routes ──────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ── Available to ALL (admin + staff) ──────────────────

    Route::get('/bookings', [DashboardController::class, 'bookings'])->name('dashboard.bookings');
    Route::get('/payments', [DashboardController::class, 'payments'])->name('dashboard.payments');
    Route::get('/bookings/{reference}', [DashboardController::class, 'showBooking'])
    ->name('bookings.show');
    Route::post('/bookings/{reference}/attend', [BookingController::class, 'dashboardMarkAttended'])
        ->name('bookings.attend');


    // Payment links (dashboard)
    Route::get   ('/payment-links',                [PaymentLinkController::class, 'index'])->name('payment-links.index');
    Route::get   ('/payment-links/create',         [PaymentLinkController::class, 'create'])->name('payment-links.create');
    Route::post  ('/payment-links',                [PaymentLinkController::class, 'store'])->name('payment-links.store');
    Route::get   ('/payment-links/{token}',        [PaymentLinkController::class, 'showDashboard'])->name('payment-links.show-dashboard');
    Route::post  ('/payment-links/{token}/cancel', [PaymentLinkController::class, 'cancel'])->name('payment-links.cancel');

     // QR Scanner — mobile only, all roles
    Route::get('/scan', function () {
        $developer = auth()->user()->effectiveDeveloper();
        return view('dashboard.scan');
    })->name('scan');

    Route::get('/scan/lookup/{reference}', function (string $reference) {
        $developer = auth()->user()->effectiveDeveloper();
        $booking   = \App\Models\Booking::where('reference', $reference)
            ->where('developer_id', $developer->id)
            ->with('category')
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        return response()->json(['booking' => [
            'reference'     => $booking->reference,
            'customer_name' => $booking->customer_name,
            'description'   => $booking->category?->name ?? $booking->description,
            'status'        => $booking->status,
            'attended'      => $booking->attended,
            'attended_at'   => $booking->attended_at?->format('d M, H:i'),
            'adults'        => $booking->adults   ?? 1,
            'children'      => $booking->children ?? 0,
        ]]);
    })->name('scan.lookup');

    // ── Admin only ────────────────────────────────────────
    Route::middleware('admin')->group(function () {
        Route::get('/api-keys', [DashboardController::class, 'apiKeys'])->name('dashboard.api-keys');
        Route::get('/integration', [DashboardController::class, 'integration'])->name('dashboard.integration');
        Route::get('/booking-settings', [DashboardController::class, 'bookingSettings'])->name('dashboard.booking-settings');
        Route::post('/booking-settings/save', [DashboardController::class, 'saveBookingSettings'])->name('dashboard.booking-settings.save');
        Route::post('/widget-apperance/save',[DashboardController::class, 'saveWidgetAppearance'])->name('dashboard.widget-appearance.save');
        Route::post('/dashboard/sms-number', [DashboardController::class, 'updateSmsNumber'])
        ->name('dashboard.sms.update');
        Route::post('/api-keys/regenerate', [DashboardController::class, 'regenerateKey'])->name('api-keys.regenerate');
        Route::post('/bvn/verify', [\App\Http\Controllers\Dashboard\VerificationController::class, 'verify'])
            ->name('bvn.verify');

        // Categories — CRUD lives within the settings page (same URL, different action)
        Route::post  ('/settings/categories',            [BookingCategoryController::class, 'store'])->name('categories.store');
        Route::put   ('/settings/categories/{category}', [BookingCategoryController::class, 'update'])->name('categories.update');
        Route::patch ('/settings/categories/{category}/toggle', [BookingCategoryController::class, 'toggle'])->name('categories.toggle');
        Route::delete('/settings/categories/{category}', [BookingCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post  ('/settings/categories/reorder',    [BookingCategoryController::class, 'reorder'])->name('categories.reorder');

        Route::post('/domains', [DeveloperDomainController::class, 'store'])
            ->name('dashboard.domains.store');

        Route::delete('/domains', [DeveloperDomainController::class, 'delete'])
            ->name('dashboard.domains.delete');

        // Staff management
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{id}/edit', [StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{id}', [StaffController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{id}', [StaffController::class, 'destroy'])->name('staff.destroy');
        Route::post  ('/staff/{id}/resend-credentials', [StaffController::class, 'resendCredentials'])->name('staff.resend-credentials');
        Route::post('/staff/{id}/suspend', [StaffController::class, 'suspend'])->name('staff.suspend');
        // AJAX endpoint used by the API Keys page for live bank account lookup
        Route::post('/ajax/verify-account', function (\Illuminate\Http\Request $request) {
            $data = $request->validate([
                'account_number' => 'required|string|digits:10',
                'bank_code' => 'required|string',
            ]);

            try {
                $account = app(\App\Services\PaystackService::class)
                    ->resolveAccount($data['account_number'], $data['bank_code']);

                return response()->json(['account' => $account]);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Account not found'], 422);
            }
        })->name('api.verify-account');
    });

    // ── Superadmin ────────────────────────────────────────────────────────────────
Route::middleware(['superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/',                              [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'index'])      ->name('dashboard');
    Route::get('/developers',                    [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'developers']) ->name('developers');
    Route::patch('/developers/{id}/fee',         [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'updateFee']) ->name('developers.fee');
    Route::patch('/developers/{id}/status',      [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'updateStatus']) ->name('developers.status');
    Route::get('/developers/{id}',               [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'showDeveloper']) ->name('developers.show');
    Route::get('/bookings',                      [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'bookings'])   ->name('bookings');
    Route::get('/revenue',                       [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'revenue'])    ->name('revenue');
});

});

// ─── Redirect root to dashboard ──────────────────────────────────────────────
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('home');
// ─── Public payment link pages (no auth) ─────────────────────────────────────
Route::get  ('/pay/{token}',            [PaymentLinkController::class, 'publicShow'])->name('pay.show');
Route::post ('/pay/{token}/initialize', [PaymentLinkController::class, 'publicPay'])->name('pay.initialize');
