<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Dashboard\BookingCategoryController;
use App\Http\Controllers\Dashboard\PaymentLinkController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\StaffController;
use App\Http\Controllers\DeveloperDomainController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\AnchorWebhookController;
use App\Http\Controllers\Dashboard\ApiKeyController;
use App\Http\Controllers\Dashboard\ServiceController;
use App\Http\Controllers\Dashboard\BookingController as DashboardBookingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Dashboard routes use standard Laravel session auth (auth middleware).
| API routes (for the SDK) remain in routes/api.php with public key auth.
|
*/
 Route::get('/bookings/create', [DashboardBookingController::class, 'create'])->name('bookings.create');
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
Route::middleware(['auth','onboarded'])->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ── Available to ALL (admin + staff) ──────────────────

    Route::get('/bookings', [DashboardController::class, 'bookings'])->name('dashboard.bookings');
    Route::get('/payments', [DashboardController::class, 'payments'])->name('dashboard.payments');
    Route::get('/bookings/{reference}', [DashboardController::class, 'showBooking'])
    ->name('bookings.show');
    Route::post('/bookings/{reference}/attend', [BookingController::class, 'dashboardMarkAttended'])
        ->name('bookings.attend');

    // Booking
    // Route::get('/bookings/create',[DashboardBookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings',                     [DashboardBookingController::class, 'store'])->name('bookings.store');//->middleware('fraud.detect');;
    Route::post('/bookings/{reference}/refresh-va', [DashboardBookingController::class, 'refreshVirtualAccount'])->name('bookings.refresh-va');

    // Payment links (dashboard)
    Route::get('/dashboard/payments/{token}',[DashboardBookingController::class, 'dashboardShow'])->name('payment.link.dashboard');

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
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/',                  [OnboardingController::class, 'index'])->name('index');
        Route::post('/type',             [OnboardingController::class, 'chooseType'])->name('type');
        Route::get('/individual',        [OnboardingController::class, 'individualForm'])->name('individual');
        Route::post('/individual',       [OnboardingController::class, 'submitIndividual'])->name('individual.submit');
        Route::get('/business',          [OnboardingController::class, 'businessForm'])->name('business');
        Route::post('/business',         [OnboardingController::class, 'submitBusiness'])->name('business.submit');
        Route::get('/pending',           [OnboardingController::class, 'pending'])->name('pending');
        Route::get('/onboarding/resolve-account', [OnboardingController::class, 'resolveAccount'])
        ->name('onboarding.resolve-account');
    });
            Route::post  ('services/{service}/activate', [ServiceController::class, 'activate']) ->name('services.activate');
    // ── Admin only ────────────────────────────────────────
    Route::middleware('admin')->group(function () {
            // ── API Keys — replace old single-route with controller ──────────────────
        Route::get ('api-keys',                                   [ApiKeyController::class, 'index'])->name('api-keys');
        Route::post('api-keys/regenerate',                        [ApiKeyController::class, 'regenerateDeveloperKey'])->name('api-keys.regenerate');
        Route::post('api-keys/services/{service}/regenerate',     [ApiKeyController::class, 'regenerateServiceKey']) ->name('api-keys.service.regenerate');


        Route::get('/integration', [DashboardController::class, 'integration'])->name('dashboard.integration');
        Route::get('/booking-settings', [DashboardController::class, 'bookingSettings'])->name('dashboard.booking-settings');
        Route::post('/booking-settings/save', [DashboardController::class, 'saveBookingSettings'])->name('dashboard.booking-settings.save');
        Route::post('/widget-apperance/save',[DashboardController::class, 'saveWidgetAppearance'])->name('dashboard.widget-appearance.save');
        Route::post('/dashboard/sms-number', [DashboardController::class, 'updateSmsNumber'])
            ->name('dashboard.sms.update');
        Route::post('/api-keys/regenerate', [DashboardController::class, 'regenerateKey'])->name('api-keys.regenerate');


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
                    'bank_nip_code'  => 'required|string',
                ]);

                try {
                    $account = app(\App\Services\AnchorService::class)
                        ->verifyAccount($data['bank_nip_code'], $data['account_number']);

                    return response()->json([
                        'account' => [
                            'account_name'   => $account['attributes']['accountName'],
                            'account_number' => $account['attributes']['accountNumber'],
                        ]
                    ]);
                } catch (\Exception $e) {
                    return response()->json(['message' => 'Account not found'], 422);
                }

        })->name('api.verify-account');

           // Service CRUD
        Route::get   ('services',                   [ServiceController::class, 'index'])   ->name('services.index');
        Route::get   ('services/create',            [ServiceController::class, 'create'])  ->name('services.create');
        Route::post  ('services',                   [ServiceController::class, 'store'])   ->name('services.store');
        Route::get   ('services/{service}/edit',    [ServiceController::class, 'edit'])    ->name('services.edit');
        Route::put   ('services/{service}',         [ServiceController::class, 'update'])  ->name('services.update');
        Route::delete('services/{service}',         [ServiceController::class, 'destroy']) ->name('services.destroy');

        // Switch active service context

        Route::patch ('services/{service}/toggle',   [ServiceController::class, 'toggle'])   ->name('services.toggle');
        Route::post  ('services/reorder',            [ServiceController::class, 'reorder'])  ->name('services.reorder');

        // Categories — now nested under services
        Route::post  ('services/{service}/categories',                      [BookingCategoryController::class, 'store'])   ->name('services.categories.store');
        Route::put   ('services/{service}/categories/{category}',           [BookingCategoryController::class, 'update'])  ->name('services.categories.update');
        Route::patch ('services/{service}/categories/{category}/toggle',    [BookingCategoryController::class, 'toggle'])  ->name('services.categories.toggle');
        Route::delete('services/{service}/categories/{category}',           [BookingCategoryController::class, 'destroy']) ->name('services.categories.destroy');
        Route::post  ('services/{service}/categories/reorder',              [BookingCategoryController::class, 'reorder']) ->name('services.categories.reorder');

    });
});
 // ── Superadmin ────────────────────────────────────────────────────────────────
Route::middleware(['auth','superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/',                              [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'index'])      ->name('dashboard');
    Route::get('/developers',                    [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'developers']) ->name('developers');
    Route::patch('/developers/{id}/fee',         [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'updateFee']) ->name('developers.fee');
    Route::patch('/developers/{id}/status',      [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'updateStatus']) ->name('developers.status');
    Route::get('/developers/{id}',               [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'showDeveloper']) ->name('developers.show');
    Route::get('/bookings',                      [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'bookings'])   ->name('bookings');
    Route::get('/revenue',                       [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'revenue'])    ->name('revenue');
});
// ─── Redirect root to dashboard ──────────────────────────────────────────────
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('home');

// ─── Public payment link pages (no auth) ─────────────────────────────────────
//Route::get  ('/pay/{token}',            [PaymentLinkController::class, 'publicShow'])->name('pay.show');
//Route::post ('/pay/{token}/initialize', [PaymentLinkController::class, 'publicPay'])->name('pay.initialize');
Route::get('/pay/{token}', [DashboardBookingController::class, 'publicShow'])->name('payment.link');
Route::post('/pay/{token}/continue',[DashboardBookingController::class, 'continue'])->name('payment.link.continue');

Route::post('/webhooks/anchor', [AnchorWebhookController::class, 'handle'])
    ->name('webhooks.anchor');
