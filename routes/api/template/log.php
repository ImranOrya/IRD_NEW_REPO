
<?php

use App\Enums\PermissionEnum;
use App\Enums\SubPermissionEnum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\template\LogController;
use App\Http\Controllers\api\template\log\UserLoginLogController;




Route::prefix('v1')->middleware("authorized:" . 'user:api')->group(function () {
    Route::get('/file-logs', [LogController::class, "fileLogs"]);
    Route::get('/database-logs', [LogController::class, "databaseLogs"])->middleware(["userHasMainViewPermission:" . PermissionEnum::logs->value]);
    Route::post('/logs/clear', [LogController::class, "clear"])->middleware(["userHasMainDeletePermission:" . PermissionEnum::logs->value]);
    Route::get('/user-activities', [UserLoginLogController::class, "logs"])->middleware(["userHasSubViewPermission:" . PermissionEnum::activity->value . "," . SubPermissionEnum::user_activity->value]);
});
