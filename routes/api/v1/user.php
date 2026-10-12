<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\UserController;

Route::prefix('user')->group(function () {
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::post('/attestation', [UserController::class, 'attestation']);
    });
});
