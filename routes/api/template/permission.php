
<?php

use App\Enums\PermissionEnum;
use App\Enums\SubPermissionEnum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\template\PermissionController;

Route::prefix('v1')->middleware(['api.key', "multiAuthorized:" . 'user:api,ngo:api'])->group(function () {
    Route::get('/role-permissions/{id}', [PermissionController::class, "rolePermissions"]);
});

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
    Route::get('/role-permissions/{id}', [PermissionController::class, "rolePermissions"]);
    Route::get('/user-permissions/{id}', [PermissionController::class, "userPermissions"])->middleware(['hasGrantPermission', "userHasMainViewPermission:" . PermissionEnum::users->value]);
    Route::post('/edit/user-permissions', [PermissionController::class, "editUserPermissions"])
        ->middleware(['hasGrantPermission', "userHasSubEditPermission:" . PermissionEnum::users->value . "," . SubPermissionEnum::user_permission->value]);
});
