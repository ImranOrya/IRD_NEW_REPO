<?php

use App\Http\Controllers\api\auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api.key'])->group(function () {
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
});
