<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\auth\UserAuthController;

Route::prefix('v1')->middleware(['api.key'])->group(function () {
    Route::post('/auth-user', [UserAuthController::class, 'login']);
});

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
    Route::post('/auth-logout', [UserAuthController::class, 'logout']);
    Route::get('/auth-user', [UserAuthController::class, 'user']);
    Route::post('/profile/change-password', [UserAuthController::class, 'changePassword']);
});
