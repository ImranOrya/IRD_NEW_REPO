
<?php

use App\Enums\PermissionEnum;
use App\Enums\SubPermissionEnum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\app\representor\RepresentorController;

Route::prefix('v1')->middleware(['api.key', "multiAuthorized:" . 'user:api,ngo:api'])->group(function () {
  Route::get('/ngo/representor/{ngo_id}', [RepresentorController::class, 'ngoRepresentor'])->middleware(["userHasSubViewPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_representative->value]);
  Route::get('/ngo/representors/{ngo_id}', [RepresentorController::class, 'ngoRepresentors'])->middleware(["userHasSubViewPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_representative->value]);
  Route::post('/ngo/representor/update', [RepresentorController::class, 'update'])->middleware(["userHasSubEditPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_representative->value]);
  Route::post('/ngo/representor/store', [RepresentorController::class, 'store'])->middleware(["userHasSubAddPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_representative->value]);
  Route::get('/ngo/representors/name/{ngo_id}', [RepresentorController::class, 'ngoRepresentorsName'])->middleware(["userHasMainAddPermission:" . PermissionEnum::ngo->value]);
});
