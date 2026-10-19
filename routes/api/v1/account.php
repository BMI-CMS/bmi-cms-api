<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AccountController;

Route::prefix('account')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::patch('/force-prioritized', [AccountController::class, 'markAsForcePrioritized']);
        Route::patch('/contact-recording', [AccountController::class, 'markAsForcePrioritized']);
    });
});
