
<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\api\template\AboutController;
use Illuminate\Support\Facades\Route;



Route::prefix('v1')->group(function () {
  Route::get('/office', [AboutController::class, "office"]);
  Route::get('/staff/director', [AboutController::class, "director"]);
  Route::get('/staff/manager', [AboutController::class, "manager"]);
  Route::get('/staff/technicalSupports', [AboutController::class, "technicalSupports"]);
});
Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::post('/staff/store', [AboutController::class, "staffStore"]);
  Route::post('/office/store', [AboutController::class, "officeStore"]);
  Route::post('/office/update', [AboutController::class, "officeUpdate"]);
  Route::get('/staff/{id}', [AboutController::class, "staff"]);
  Route::post('/staff/update', [AboutController::class, 'update']);
  Route::delete('/staff/{id}', [AboutController::class, 'destroy']);
});
