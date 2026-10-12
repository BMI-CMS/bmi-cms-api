<?php

use App\Http\Controllers\Api\V1\CustomerDetailsController;
use Illuminate\Support\Facades\Route;

Route::prefix('customers')->group(function () {
    Route::get('/{id}', [CustomerDetailsController::class, 'show']);
    Route::put('/{id}/contact', [CustomerDetailsController::class, 'updateContact']);
    Route::get('/{id}/financial-profile', [CustomerDetailsController::class, 'financialProfile']);
    Route::get('/{id}/history', [CustomerDetailsController::class, 'interactionHistory']);
});

Route::prefix('customer')->group(function () {
    Route::get('/{id}', [CustomerDetailsController::class, 'show']);
    Route::put('/{id}/contact', [CustomerDetailsController::class, 'updateContact']);
    Route::get('/{id}/financial-profile', [CustomerDetailsController::class, 'financialProfile']);
    Route::get('/{id}/history', [CustomerDetailsController::class, 'interactionHistory']);
});
