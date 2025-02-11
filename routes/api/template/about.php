
<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\api\template\AboutController;
use Illuminate\Support\Facades\Route;




Route::prefix('v1')->group(function () {
  Route::get('/office', [AboutController::class, "office"]);
  Route::get('/staff/public/office', [AboutController::class, "publicOffice"]);
  Route::get('/staff/public/director', [AboutController::class, "publicDirector"]);
  Route::get('/staff/public/manager', [AboutController::class, "publicManager"]);
  Route::get('/staff/public/technicalSupports', [AboutController::class, "publicTechnicalSupports"]);
  Route::get('/public/sliders', [AboutController::class, 'publicSliders']);
});
Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {


  Route::get('/staff/director', [AboutController::class, "director"]);
  Route::get('/staff/manager', [AboutController::class, "manager"]);
  Route::get('/staff/technicalSupports', [AboutController::class, "technicalSupports"]);
  Route::post('/staff/store', [AboutController::class, "staffStore"]);
  Route::post('/office/store', [AboutController::class, "officeStore"]);
  Route::post('/office/update', [AboutController::class, "officeUpdate"]);
  Route::get('/staff/{id}', [AboutController::class, "staff"]);
  Route::post('/staff/update', [AboutController::class, 'update']);
  Route::delete('/staff/{id}', [AboutController::class, 'destroy']);
  Route::post('/slider/store', [AboutController::class, 'sliderStore']);
  Route::get('/sliders', [AboutController::class, 'sliders']);
  Route::POST('/slider/change/status', [AboutController::class, 'changeStatusSlider']);
});
