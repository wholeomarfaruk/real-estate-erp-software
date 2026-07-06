<?php

use App\Http\Controllers\Api\Client\AuthController as ClientAuthController;
use App\Http\Controllers\Api\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Api\Client\PaymentHistoryController as ClientPaymentHistoryController;
use App\Http\Controllers\Api\Client\ProfileController as ClientProfileController;
use App\Http\Controllers\Api\Client\PropertySaleController as ClientPropertySaleController;
use App\Http\Controllers\Api\Client\SidebarController as ClientSidebarController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Webhooks (no auth required - providers send from outside)
Route::post('/webhooks/sms', [WebhookController::class, 'sms'])->name('api.webhooks.sms');

/*
|--------------------------------------------------------------------------
| Client App API (Phase 1 — OTP-based auth)
|--------------------------------------------------------------------------
*/
Route::prefix('client')->name('api.client.')->group(function () {
    // Public — credential + OTP flow. Throttled to slow brute-forcing.
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('login', [ClientAuthController::class, 'login'])->name('login');
        Route::post('verify-otp', [ClientAuthController::class, 'verifyOtp'])->name('verify-otp');
        Route::post('resend-otp', [ClientAuthController::class, 'resendOtp'])->name('resend-otp');
        Route::post('forgot-password', [ClientAuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('reset-password', [ClientAuthController::class, 'resetPassword'])->name('reset-password');
    });

    // Protected — requires a valid token belonging to a client account.
    Route::middleware(['auth:sanctum', 'client'])->group(function () {
        Route::post('logout', [ClientAuthController::class, 'logout'])->name('logout');
        Route::get('profile', [ClientProfileController::class, 'show'])->name('profile');
        Route::get('dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
        Route::get('sidebar', [ClientSidebarController::class, 'show'])->name('sidebar');

        // "My properties" — purchased/rented units and their sale records.
        Route::get('properties', [ClientPropertySaleController::class, 'index'])->name('properties.index');
        Route::get('my-properties-unit-wise', [ClientPropertySaleController::class, 'unitsIndex'])->name('properties.unit-wise');
        Route::get('my-properties-unit-wise/{sale}/units/{saleUnit}', [ClientPropertySaleController::class, 'unitShow'])->name('properties.units.show');
        Route::get('properties/{sale}', [ClientPropertySaleController::class, 'show'])->name('properties.show');
        Route::get('properties/{sale}/documents', [ClientPropertySaleController::class, 'documents'])->name('properties.documents');
        Route::get('properties/{sale}/payment-schedules', [ClientPropertySaleController::class, 'paymentSchedules'])->name('properties.payment-schedules');

        // "Payment History" — every paid/partial schedule across all properties.
        Route::get('payment-history', [ClientPaymentHistoryController::class, 'index'])->name('payment-history');
    });
});
