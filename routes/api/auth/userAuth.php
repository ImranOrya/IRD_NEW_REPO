<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\auth\UserAuthController;

Route::prefix('v1')->middleware(['api.key'])->group(function () {
    Route::post('/auth-user', [UserAuthController::class, 'login']);

    Route::get('/auth/forgot-password', [UserAuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [UserAuthController::class, 'resetPassword']);
});

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
    Route::post('/auth-logout', [UserAuthController::class, 'logout']);
    Route::get('/auth-user', [UserAuthController::class, 'user']);
    Route::post('/auth/user/change-password', [UserAuthController::class, 'changePassword']);
    Route::post('/auth/user/change-permissions', [UserAuthController::class, 'changePermissions']);
    Route::delete('/auth/user/delete/{id}', [UserAuthController::class, "delete"]);
});
