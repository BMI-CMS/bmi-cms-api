<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LucasIntegrationController;
use App\Http\Controllers\Api\V1\CollectionController;

Route::prefix('integrations')->group(function () {
    Route::middleware('hmac.signature:lucas')->group(function () {
        Route::prefix('lucas')->group(function () {
            Route::get('/request-restructuring', [LucasIntegrationController::class, 'forRestructuring']);
            Route::get('/early-endorsed-account', [LucasIntegrationController::class, 'earlyEndorsed']);
            Route::post('/disapproved-early-endorsed-account', [LucasIntegrationController::class, 'updateEarlyEndorsedApprovalStatus']);
            Route::get('/notice-and-demand-account', [LucasIntegrationController::class, 'noticeAndDemand']);
            Route::patch('/ch-authorization', [LucasIntegrationController::class, 'updateEarlyEndorsedApprovalStatus']);
        });
    });

    Route::post('/lucas/ch-authorization', [CollectionController::class, 'testLang']);
    Route::patch('/lucas/deposit-status', [LucasIntegrationController::class, 'updateDepositStatus']);
    Route::prefix('lms')->group(function () {});
});
