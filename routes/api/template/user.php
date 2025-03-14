
<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\api\template\UserController;
use Illuminate\Support\Facades\Route;




Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
    Route::get('/users/record/count', [UserController::class, "userCount"]);
    Route::get('/users', [UserController::class, "users"])->middleware(["userHasMainViewPermission:" . PermissionEnum::users->value]);
    Route::get('/user/{id}', [UserController::class, "user"])->middleware(['accessUserCheck', "userHasMainViewPermission:" . PermissionEnum::users->value]);
    Route::post('/user/change-password', [UserController::class, 'changePassword'])->middleware(['accessUserCheck']);
    Route::delete('/user/delete-profile/{id}', [UserController::class, 'deleteProfile']);
    Route::post('/user/update-profile', [UserController::class, 'updateProfile']);
    Route::post('/user/update', [UserController::class, 'update'])->middleware(['accessUserCheck']);
    Route::post('/user/store', [UserController::class, 'store']);
    Route::delete('/user/{id}', [UserController::class, 'destroy']);
    Route::post('/user/validate/email/contact', [UserController::class, "validateEmailContact"]);
});
