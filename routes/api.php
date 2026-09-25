<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::prefix('v1')->middleware('api.key')->group(function () {
// require __DIR__ . '/api/v1/account.php';
// require __DIR__ . '/api/v1/auth.php';
// require __DIR__ . '/api/v1/collection.php';
// require __DIR__ . '/api/v1/customer.php';
// require __DIR__ . '/api/v1/first-encounter.php';
// require __DIR__ . '/api/v1/user.php';
// });


Route::prefix('v1')->group(function () {
    require __DIR__ . '/api/v1/account.php';
    require __DIR__ . '/api/v1/auth.php';
    require __DIR__ . '/api/v1/collection.php';
    require __DIR__ . '/api/v1/dashboard.php';
    require __DIR__ . '/api/v1/customer.php';
    require __DIR__ . '/api/v1/first-encounter.php';
    require __DIR__ . '/api/v1/user.php';
});
