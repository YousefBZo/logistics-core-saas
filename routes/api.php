<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*

|--------------------------------------------------------------------------
| API Routes - Logistics & SaaS Authentication Grid
|--------------------------------------------------------------------------
*/

// Public Guest Routes
Route::post('/auth/register-company', [AuthController::class, 'registerCompany']);
Route::post('/auth/register-merchant', [AuthController::class, 'registerMerchant']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected Sanctum Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Smoke Test to verify token and Tenant firewall functionality
    Route::get('/auth/me', function () {
        return auth()->user();
    });
});
