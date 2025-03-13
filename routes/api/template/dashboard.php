
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\app\dashboard\user\DashboardController;

Route::prefix('v1')->group(function () {
    Route::get('/dashboard/data', [DashboardController::class, 'headerData']);
    Route::get('/dashboard/info', [DashboardController::class, 'dashboardInfo']);
});
