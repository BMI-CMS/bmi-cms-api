<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\DashboardController;

Route::prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'getSummary']);
    // Route::middleware('auth:sanctum')->group(function () {
    //     Route::get('/', [DashboardController::class, 'getSummary']);
    // });
});
