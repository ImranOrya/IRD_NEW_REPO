
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\app\dashboard\user\DashboardController;

Route::prefix('v1')->group(function () {
    // Chart: AreaChartSix , the ngo count by type last six month admin user 
    Route::get('/dashboard/ngotype/chart/sixmonth', [DashboardController::class, 'ngoCountByTypesLastSixMonths']);

    // Dashboard header card return value  ngo_status count
    Route::get('/dashboard/header/data', [DashboardController::class, 'headerData']);


    // for  pie chart  ,ngo count by type inter,domastic , intergormental 
    Route::get('/dashboard/ngo/type/data', [DashboardController::class, 'ngoCountByNgoType']);
});
