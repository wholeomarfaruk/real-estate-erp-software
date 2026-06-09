<?php

use App\Http\Controllers\Api\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Webhooks (no auth required - providers send from outside)
Route::post('/webhooks/sms', [WebhookController::class, 'sms'])->name('api.webhooks.sms');
