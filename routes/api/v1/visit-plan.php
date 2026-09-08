<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\VisitPlanController;

Route::prefix('visit-plan')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/index', [VisitPlanController::class, 'index']);
    });
});
