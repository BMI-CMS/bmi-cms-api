<?php

use App\Http\Controllers\Api\V1\FirstEncounterController;
use Illuminate\Support\Facades\Route;

Route::prefix('first-encounter')->group(function () {
    Route::get('/', [FirstEncounterController::class, 'index']);
    Route::get('/{id}', [FirstEncounterController::class, 'show']);
    Route::post('/{id}/evaluation', [FirstEncounterController::class, 'submitEvaluation']);
    Route::post('/{id}/visit', [FirstEncounterController::class, 'logVisit']);
});
