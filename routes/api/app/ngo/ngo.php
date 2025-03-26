
<?php

use App\Enums\PermissionEnum;
use App\Enums\SubPermissionEnum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\app\ngo\ViewsNgoController;
use App\Http\Controllers\api\app\ngo\EditesNgoController;
use App\Http\Controllers\api\app\ngo\StoresNgoController;
use App\Http\Controllers\api\app\ngo\DeletesNgoController;
use App\Http\Controllers\api\app\ngo\ExtendNgoController;

Route::prefix('v1')->group(function () {
  Route::get('public/ngos', [ViewsNgoController::class, 'publicNgos']);
});
Route::prefix('v1')->middleware(['api.key', "multiAuthorized:" . 'user:api,ngo:api'])->group(function () {
  Route::get('/ngo/status/{id}', [ViewsNgoController::class, 'currentStatus']);
  Route::get('/ngo/details/{id}', [ViewsNgoController::class, 'ngoDetail'])->middleware(["userHasSubViewPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_information->value]);
  Route::post('/ngo/update-info', [EditesNgoController::class, 'updateInfo'])->middleware(["userHasSubEditPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_information->value]);
  Route::get('/ngo/statuses/{id}', [ViewsNgoController::class, 'statuses'])->middleware(["userHasSubViewPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_status->value]);
  Route::post('/ngo/more-information/updated', [EditesNgoController::class, 'UpdateMoreInformation'])->middleware(["userHasSubEditPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_more_information->value]);
  Route::get('/ngo/more-information/{id}', [ViewsNgoController::class, 'moreInformation'])->middleware(["userHasSubViewPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_more_information->value]);
  Route::get('/ngo/start/register/form/{id}', [ViewsNgoController::class, 'startRegisterForm']);
  Route::get('/ngo/start/extend/form/{id}', [ViewsNgoController::class, 'startExtendForm']);
  Route::post('/ngo/register/form/complete', [StoresNgoController::class, 'registerFormCompleted']);
  Route::post('/ngo/extend/form/complete', [ExtendNgoController::class, 'extendNgoAgreement']);
  Route::post('/ngo/store/signed/register/form', [StoresNgoController::class, 'StoreSignedRegisterForm']);
  Route::get('/ngo/header-info/{id}', [ViewsNgoController::class, 'headerInfo']);
});
Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  // change ngo status route
  Route::post('/ngo/change-status', [EditesNgoController::class, 'changeStatus'])->middleware(["userHasSubAddPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_status->value]);
  Route::get('/ngos/record/count', [ViewsNgoController::class, "ngoCount"])->middleware(["userHasMainViewPermission:" . PermissionEnum::ngo->value]);
  Route::get('/ngos', [ViewsNgoController::class, 'ngos'])->middleware(["userHasMainViewPermission:" . PermissionEnum::ngo->value]);
  Route::post('/ngo/store', [StoresNgoController::class, 'store'])->middleware(["userHasMainAddPermission:" . PermissionEnum::ngo->value]);

  // Uknown
  Route::get('/ngos/pending-task/{id}', [ViewsNgoController::class, 'pendingTask']);
  // Pending Task
  Route::post('/destroy/ngo/task/content/{id}', [DeletesNgoController::class, 'destroyPendingTask']);
});
