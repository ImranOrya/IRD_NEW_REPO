
<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\api\app\director\DirectorController;
use App\Http\Controllers\api\app\ngo\DeletesNgoController;
use App\Http\Controllers\api\app\ngo\StoresNgoController;
use App\Http\Controllers\api\app\ngo\ViewsNgoController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
  Route::get('public/ngos/{page}', [ViewsNgoController::class, 'ngosPublic']);
  Route::get('ngos/storePersonalDetial/{id}', [ViewsNgoController::class, 'storePersonalDetial']);
  Route::get('ngos/personalDetail/{id}', [ViewsNgoController::class, 'personalDetial']);
});

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::get('/ngoInit/{id}', [ViewsNgoController::class, 'ngoInit']);
  Route::post('ngos/personalDetail/destory/{id}', [DeletesNgoController::class, 'destroyPersonalDetail']);
  Route::post('ngos/storePersonalDetial/{id}', [StoresNgoController::class, 'storePersonalDetial']);
  Route::get('/ngos/record/count', [ViewsNgoController::class, "ngoCount"])->middleware(["hasViewPermission:" . PermissionEnum::ngo->value]);
  Route::get('/ngos/{page}', [ViewsNgoController::class, 'ngos'])->middleware(["hasViewPermission:" . PermissionEnum::ngo->value]);
  Route::get('/ngo/{id}', [ViewsNgoController::class, 'ngo'])->middleware(["hasViewPermission:" . PermissionEnum::ngo->value]);
  Route::post('/ngo/store', [StoresNgoController::class, 'store'])->middleware(["hasAddPermission:" . PermissionEnum::ngo->value]);
});

// ngo user 

// Route::get('/ngo/details/{id}', [NgoController::class, 'ngoDetail']);
Route::get('/director/details/{ngo_id}', [DirectorController::class, 'directorDetails']);

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'ngo:api'])->group(function () {
  Route::get('/ngo/{id}', [ViewsNgoController::class, 'ngo']);
});
