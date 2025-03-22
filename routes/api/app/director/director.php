
<?php

use App\Enums\PermissionEnum;
use App\Enums\SubPermissionEnum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\app\director\DirectorController;


Route::prefix('v1')->middleware(['api.key', "multiAuthorized:" . 'user:api,ngo:api'])->group(function () {
  Route::get('/ngo/director/{ngo_id}', [DirectorController::class, 'ngoDirector'])->middleware(["userHasSubViewPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_director_information->value]);
  Route::get('/ngo/directors/{ngo_id}', [DirectorController::class, 'ngoDirectors'])->middleware(["userHasSubViewPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_director_information->value]);
  Route::get('/ngo/directors/name/{ngo_id}', [DirectorController::class, 'ngoDirectorsName'])->middleware(["userHasSubViewPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_director_information->value]);
  Route::post('/ngo/director/update', [DirectorController::class, 'update'])->middleware(["userHasSubEditPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_director_information->value]);
  Route::post('/ngo/director/store', [DirectorController::class, 'store'])->middleware(["userHasSubAddPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_director_information->value]);
});
