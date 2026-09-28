<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\DashboardController;

Route::prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'accountSummary']);
    Route::get('/assigned-accounts', [DashboardController::class, 'assignedAccounts']);
    Route::get('/assigned-accounts-psgc', [DashboardController::class, 'assignedAccountsByPSGC']);
    Route::middleware('auth:sanctum')->group(function () {
        // Route::get('/', [DashboardController::class, 'accountSummary']);
        // Route::get('/assigned-accounts', [DashboardController::class, 'assignedAccounts']);
        // Route::get('/assigned-accounts-psgc', [DashboardController::class, 'assignedAccountsByPSGC']);
    });
});
