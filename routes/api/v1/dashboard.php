<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\DashboardController;

Route::prefix('dashboard')->group(function () {
    Route::get('/{userId}', [DashboardController::class, 'accountSummary']);
    Route::get('/assigned-accounts/{userId}/{period}', [DashboardController::class, 'assignedAccounts']);
    Route::get('/assigned-accounts-psgc/{userId}', [DashboardController::class, 'assignedAccountsByPSGC']);
    Route::middleware('auth:sanctum')->group(function () {
        // Route::get('/{userId}', [DashboardController::class, 'accountSummary']);
        // Route::get('/assigned-accounts/{userId}/{period}', [DashboardController::class, 'assignedAccounts']);
        // Route::get('/assigned-accounts-psgc/{userId}', [DashboardController::class, 'assignedAccountsByPSGC']);
    });
});
