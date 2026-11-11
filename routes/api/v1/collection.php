<?php

use App\Http\Controllers\Api\V1\CollectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('collections')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [CollectionController::class, 'index']);
    });
});
