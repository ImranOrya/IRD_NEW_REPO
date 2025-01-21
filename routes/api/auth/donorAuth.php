<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\auth\NgoAuthController;

Route::prefix('v1')->middleware(['api.key'])->group(function () {
    Route::post('/auth/ngo/login', [NgoAuthController::class, 'login']);
});

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'donor:api'])->group(function () {
    Route::get('/auth-ngo', [NgoAuthController::class, 'ngo']);
});