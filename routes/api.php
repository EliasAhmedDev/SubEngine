<?php

/**
 * API route definitions.
 * Registers API endpoints for plans, subscriptions and payments.
 */

use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::get('/plans', [PlanController::class, 'index']);

Route::get('/plans/{plan:slug}', [PlanController::class, 'show']);

Route::get('/payments/success', function () {
    return response()->json([
        'message' => 'Payment completed.',
    ]);
});

Route::get('/payments/cancel', function () {
    return response()->json([
        'message' => 'Checkout canceled.',
    ]);
});

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/subscription', [SubscriptionController::class, 'show']);

    Route::post('/subscription', [SubscriptionController::class, 'store']);

    Route::patch('/subscription', [SubscriptionController::class, 'update']);
});
