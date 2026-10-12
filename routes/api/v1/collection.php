<?php

use App\Http\Controllers\Api\V1\CollectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('collections')->group(function () {
    Route::get('/', [CollectionController::class, 'index']);
    Route::get('/{id}', [CollectionController::class, 'show']);
    Route::post('/{id}/action', [CollectionController::class, 'recordAction']);
    Route::post('/{id}/receipt', [CollectionController::class, 'recordReceipt']);
});

Route::prefix('collection')->group(function () {
    Route::get('/', [CollectionController::class, 'index']);
    Route::get('/{id}', [CollectionController::class, 'show']);
    Route::post('/{id}/action', [CollectionController::class, 'recordAction']);
    Route::post('/{id}/receipt', [CollectionController::class, 'recordReceipt']);
});
