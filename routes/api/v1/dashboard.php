<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\DashboardController;

Route::prefix('dashboard')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/{userId}', [DashboardController::class, 'show']);
    });
});
