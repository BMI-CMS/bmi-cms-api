<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LucasIntegrationController;

Route::prefix('integrations')->group(function () {
    Route::prefix('lucas')->group(function () {
        Route::get('/request-restructuring', [LucasIntegrationController::class, 'forRestructuring']);
        Route::get('/early-endorsed-account', [LucasIntegrationController::class, 'earlyEndorsed']);
        Route::get('/notice-and-demand-account', [LucasIntegrationController::class, 'noticeAndDemand']);
    });

    Route::prefix('lms')->group(function () {});
});
