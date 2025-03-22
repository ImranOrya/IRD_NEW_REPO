
<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\api\template\UserController;
use Illuminate\Support\Facades\Route;
use App\Enums\SubPermissionEnum;




Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
    Route::get('/users/record/count', [UserController::class, "userCount"])->middleware(["userHasMainViewPermission:" . PermissionEnum::users->value]);
    Route::get('/users', [UserController::class, "users"])->middleware(["userHasMainViewPermission:" . PermissionEnum::users->value]);
    Route::get('/user/{id}', [UserController::class, "user"])->middleware(['accessUserCheck', "userHasMainViewPermission:" . PermissionEnum::users->value]);
    Route::delete('/user/delete/profile-picture/{id}', [UserController::class, 'deleteProfilePicture'])->middleware(['accessUserCheck', "userHasMainDeletePermission:" . PermissionEnum::users->value]);
    Route::post('/user/update/profile-picture', [UserController::class, 'updateProfilePicture'])->middleware(['accessUserCheck', "userHasMainEditPermission:" . PermissionEnum::users->value]);
    Route::post('/user/update/information', [UserController::class, 'updateInformation'])->middleware(['accessUserCheck', "userHasSubEditPermission:" . PermissionEnum::users->value . "," . SubPermissionEnum::user_information->value]);
    Route::post('/user/store', [UserController::class, 'store'])->middleware(["userHasMainAddPermission:" . PermissionEnum::users->value]);
    Route::delete('/user/{id}', [UserController::class, 'destroy'])->middleware(["userHasMainDeletePermission:" . PermissionEnum::users->value]);
    Route::post('/user/validate/email/contact', [UserController::class, "validateEmailContact"]);
    Route::post('/user/accpunt/change-password', [UserController::class, 'changePassword'])->middleware(['accessUserCheck']);
});
