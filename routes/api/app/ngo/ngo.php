
<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\api\app\ngo\NgoController;
use App\Http\Controllers\api\app\ngo\NgoPublicController;
use Illuminate\Support\Facades\Route;




Route::prefix('v1')->group(function () {
    Route::get('public/ngos/{page}', [NgoPublicController::class, 'ngos']);
});




Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::get('/ngos/record/count', [NgoController::class, "ngoCount"])->middleware(["hasViewPermission:" . PermissionEnum::ngo->value]);
  Route::get('/ngos/{page}', [NgoController::class, 'ngos'])->middleware(["hasViewPermission:" . PermissionEnum::ngo->value]);
  Route::get('/ngo/{id}', [NgoController::class, 'ngo'])->middleware(["hasViewPermission:" . PermissionEnum::ngo->value]);
  Route::post('/ngo/store', [NgoController::class, 'store'])->middleware(["hasAddPermission:" . PermissionEnum::ngo->value]);
});


// ngo user 
Route::prefix('v1')->middleware(['api.key', "authorized:" . 'ngo:api'])->group(function () {});
