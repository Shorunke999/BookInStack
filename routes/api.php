<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api by default.
| SDK routes use public key auth (ValidatePublicKey middleware).
| Dashboard routes use Sanctum token auth.
|
*/

// ─── Public: No auth required ────────────────────────────────────────────────
// Route::get('/developers/banks',     [DeveloperController::class, 'banks']);

// Paystack webhook — NO auth, verified by signature instead
Route::post('/webhooks/paystack', [WebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// ─── SDK Routes: Public key auth ─────────────────────────────────────────────
Route::middleware('public.key')->group(function () {
    // Bookings
    Route::post('/bookings', [BookingController::class, 'create']);
    Route::get('/bookings', [BookingController::class, 'list']);
    Route::get('/bookings/{reference}', [BookingController::class, 'show']);
    Route::get('/booking-window/status', [BookingController::class, 'getBookingWindowStatus']);
    // api.php — SDK route (public key auth)
    Route::post('/bookings/{reference}/attend', [BookingController::class, 'markAttended']);

    // Payments
    Route::post('/payments/initialize', [PaymentController::class, 'initialize']);
    Route::get('/payments/verify/{reference}', [PaymentController::class, 'verify']);
});

// // ─── Dashboard Routes: Sanctum token auth ────────────────────────────────────
// Route::middleware('auth:sanctum')->group(function () {
//     // Developer profile
//     Route::get('/developers/me',            [DeveloperController::class, 'me']);
//     Route::post('/developers/logout',       [DeveloperController::class, 'logout']);
//     Route::get('/developers/stats',         [DeveloperController::class, 'stats']);
//     Route::post('/developers/regenerate-key', [DeveloperController::class, 'regenerateKey']);
//     Route::post('/developers/verify-account', [DeveloperController::class, 'verifyBankAccount']);

//     // Dashboard data
//     Route::get('/dashboard/bookings',  [BookingController::class, 'dashboardList']);
//     Route::get('/dashboard/payments',  [PaymentController::class, 'dashboardList']);
// });
