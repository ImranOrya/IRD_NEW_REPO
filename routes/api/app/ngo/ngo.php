
<?php

use App\Enums\PermissionEnum;
use App\Enums\SubPermissionEnum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\app\file\FileController;
use App\Http\Controllers\api\app\ngo\ViewsNgoController;
use App\Http\Controllers\api\app\ngo\EditesNgoController;
use App\Http\Controllers\api\app\ngo\StoresNgoController;
use App\Http\Controllers\api\app\ngo\DeletesNgoController;






Route::prefix('v1')->group(function () {
  Route::get('public/ngos', [ViewsNgoController::class, 'publicNgos']);
});
Route::prefix('v1')->middleware(['api.key', "doubleAuthorized:" . 'user:api,ngo:api'])->group(function () {
  // Route::get('/role-permissions/{id}', [PermissionController::class, "rolePermissions"]);
});
Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::get('/ngo/more-information/{id}', [ViewsNgoController::class, 'moreInformation']);
  Route::get('/ngo/agreement-documents', [ViewsNgoController::class, 'agreementDocuments']);
  Route::get('/ngo/statuses/{id}', [ViewsNgoController::class, 'statuses']);
  Route::get('/ngo/header-info/{id}', [ViewsNgoController::class, 'headerInfo']);

  Route::post('/ngo/update-profile', [EditesNgoController::class, 'updateProfile']);
  Route::post('/ngo/more-information/updated', [EditesNgoController::class, 'UpdateMoreInformation']);
  Route::get('/ngos/pending-task/{id}', [ViewsNgoController::class, 'pendingTask']);
  // change ngo status route
  Route::post('/ngo/change-status', [EditesNgoController::class, 'changeStatus']);
  Route::post('/ngo/update-info', [EditesNgoController::class, 'updateInfo']);
  Route::delete('/ngo/delete-profile/{id}', [DeletesNgoController::class, 'deleteProfile']);
  Route::get('/ngoInit/{id}', [ViewsNgoController::class, 'startRegisterForm']);
  Route::post('/ngos/personalDetail/destory/{id}', [DeletesNgoController::class, 'destroyPersonalDetail']);
  Route::post('/ngo/register/form/complete', [StoresNgoController::class, 'registerFormCompleted']);
  Route::get('/ngos/record/count', [ViewsNgoController::class, "ngoCount"])->middleware(["userHasMainViewPermission:" . PermissionEnum::ngo->value]);
  Route::get('/ngos', [ViewsNgoController::class, 'ngos'])->middleware(["userHasMainViewPermission:" . PermissionEnum::ngo->value]);
  Route::get('/ngo/{id}', [ViewsNgoController::class, 'ngo'])->middleware(["userHasMainViewPermission:" . PermissionEnum::ngo->value]);
  Route::post('/ngo/store', [StoresNgoController::class, 'store'])->middleware(["userHasMainAddPermission:" . PermissionEnum::ngo->value]);

  // use for step 1 data retrive
  Route::get('/ngo/details/{id}', [ViewsNgoController::class, 'ngoDetail']);

  // upload checklist file 
  Route::post('/ngo/checklist/file/upload', [FileController::class, 'uploadFile']);
});
