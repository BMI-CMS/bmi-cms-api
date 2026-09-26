<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AccountController;

Route::prefix('account')->group(function () {
    Route::get('/{userId}', [AccountController::class, 'show']);
    Route::middleware('auth:sanctum')->group(function () {
        // Route::get('/{userId}', [AccountController::class, 'show']);
    });
});
