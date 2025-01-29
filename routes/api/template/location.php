
<?php

use App\Http\Controllers\api\template\LocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api.key'])->group(function () {

    Route::get('/countries', [LocationController::class, "countries"]);
    Route::get('/provinces', [LocationController::class, "provinces"]);
    Route::get('/districts', [LocationController::class, 'districts']);
});
