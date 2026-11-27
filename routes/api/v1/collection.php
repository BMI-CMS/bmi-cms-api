<?php

use App\Http\Controllers\Api\V1\CollectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('collections')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [CollectionController::class, 'index']);
        Route::post('/process-cutoff', [CollectionController::class, 'processCutoff']);
        Route::get('/cutoff-history', [CollectionController::class, 'cutoffHistory']);
        Route::post('/deposit', [CollectionController::class, 'deposit']);
    });
    Route::post('/test', [CollectionController::class, 'testLang']);
});
