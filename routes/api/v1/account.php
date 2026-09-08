<?php

use Illuminate\Support\Facades\Route;

Route::prefix('account')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {});
});
