
<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\api\app\ngo\DeletesNgoController;
use App\Http\Controllers\api\app\ngo\StoresNgoController;
use App\Http\Controllers\api\app\ngo\ViewsNgoController;
use Illuminate\Support\Facades\Route;





Route::get('/ngo/more/information/{id}', [ViewsNgoController::class, 'ngoMoreInformation']);
Route::get('/ngo/checklist/documents/{id}', [ViewsNgoController::class, 'ngoCheckListDocument']);


Route::prefix('v1')->group(function () {
  Route::get('public/ngos/{page}', [ViewsNgoController::class, 'publicNgos']);
  Route::get('ngos/storePersonalDetial/{id}', [ViewsNgoController::class, 'storePersonalDetial']);
  Route::get('ngos/personalDetail/{id}', [ViewsNgoController::class, 'personalDetial']);
});

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::get('/ngoInit/{id}', [ViewsNgoController::class, 'ngoInit']);
  Route::post('ngos/personalDetail/destory/{id}', [DeletesNgoController::class, 'destroyPersonalDetail']);
  Route::post('ngos/storePersonalDetial/{id}', [StoresNgoController::class, 'storePersonalDetial']);
  Route::post('ngo/store/personal/detail-final', [StoresNgoController::class, 'storePersonalDetialFinal']);
  Route::get('/ngos/record/count', [ViewsNgoController::class, "ngoCount"])->middleware(["hasViewPermission:" . PermissionEnum::ngo->value]);
  Route::get('/ngos/{page}', [ViewsNgoController::class, 'ngos'])->middleware(["hasViewPermission:" . PermissionEnum::ngo->value]);
  Route::get('/ngo/{id}', [ViewsNgoController::class, 'ngo'])->middleware(["hasViewPermission:" . PermissionEnum::ngo->value]);
  Route::post('/ngo/store', [StoresNgoController::class, 'store'])->middleware(["hasAddPermission:" . PermissionEnum::ngo->value]);

  // use for step 1 data retrive
  Route::get('/ngo/details/{id}', [ViewsNgoController::class, 'ngoDetail']);
});

// ngo user 


Route::prefix('v1')->middleware(['api.key', "authorized:" . 'ngo:api'])->group(function () {});
