<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Dashboard\BookingCategoryController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\NINController;
use App\Http\Controllers\Dashboard\StaffController;
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
    Route::post('/bookings/{reference}/attend', [BookingController::class, 'dashboardMarkAttended'])
        ->name('bookings.attend');

    // ── Admin only ────────────────────────────────────────
    Route::middleware('admin')->group(function () {
        Route::get('/api-keys', [DashboardController::class, 'apiKeys'])->name('dashboard.api-keys');
        Route::get('/integration', [DashboardController::class, 'integration'])->name('dashboard.integration');
        Route::get('/booking-settings', [DashboardController::class, 'bookingSettings'])->name('dashboard.booking-settings');
        Route::post('/booking-settings/save', [DashboardController::class, 'saveBookingSettings'])->name('dashboard.booking-settings.save');
        Route::post('/api-keys/regenerate', [DashboardController::class, 'regenerateKey'])->name('api-keys.regenerate');
       Route::post('/nin/verify', [NINController::class, 'verify'])->name('nin.verify');


        // Categories — CRUD lives within the settings page (same URL, different action)
        Route::post  ('/settings/categories',            [BookingCategoryController::class, 'store'])->name('categories.store');
        Route::put   ('/settings/categories/{category}', [BookingCategoryController::class, 'update'])->name('categories.update');
        Route::patch ('/settings/categories/{category}/toggle', [BookingCategoryController::class, 'toggle'])->name('categories.toggle');
        Route::delete('/settings/categories/{category}', [BookingCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post  ('/settings/categories/reorder',    [BookingCategoryController::class, 'reorder'])->name('categories.reorder');


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

});

// ─── Redirect root to dashboard ──────────────────────────────────────────────
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('home');
