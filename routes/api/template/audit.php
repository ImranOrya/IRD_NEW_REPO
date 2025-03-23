<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\api\template\AuditLogController;
use Illuminate\Support\Facades\Route;





Route::get('/audits/{page}', [AuditLogController::class, "audits"]);

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
    Route::get('/audits/{page}', [AuditLogController::class, "audits"])->middleware(["hasViewPermission:" . PermissionEnum::audit->value]);


    Route::get('/audits/user/type', [AuditLogController::class, "userType"]);
    Route::get('/audits/user/list', [AuditLogController::class, "userList"]);
    Route::get('/audits/table/list', [AuditLogController::class, "tableList"]);
    Route::get('/audits/column/list', [AuditLogController::class, "columnList"]);
});
