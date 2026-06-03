<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Shipments\StoreShipmentController;
use App\Http\Controllers\Api\Staff\StoreStaffController;
use Illuminate\Support\Facades\Route;

/*

|--------------------------------------------------------------------------
| API Routes - Logistics & SaaS Authentication Grid
|--------------------------------------------------------------------------
*/

Route::middleware(['throttle:auth'])->group(function () {
    Route::middleware('idempotency')->group(function () {
        Route::post('/auth/register-company', [AuthController::class, 'registerCompany']);
        Route::post('/auth/register-merchant', [AuthController::class, 'registerMerchant']);
    });

    Route::post('/auth/login', [AuthController::class, 'login']);
});

// Protected Sanctum Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', MeController::class);
});

Route::middleware(['auth:sanctum', 'idempotency'])->group(function () {
    Route::middleware('permission:MANAGE_TENANT')->group(function () {
        Route::post('/staff', StoreStaffController::class);
    });

    Route::middleware('permission:CREATE_SHIPMENT')->group(function () {
        Route::post('/shipments', StoreShipmentController::class);
    });
});
