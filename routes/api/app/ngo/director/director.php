
<?php

use App\Http\Controllers\api\app\director\DirectorController;
use Illuminate\Support\Facades\Route;






Route::get('/ngo/director/{ngo_id}', [DirectorController::class, 'ngoDirector']);


Route::prefix('v1')->group(function () {});


Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::get('/ngo/director/{ngo_id}', [DirectorController::class, 'ngoDirector']);
  Route::get('/ngo/directors/{ngo_id}', [DirectorController::class, 'ngoDirectors']);
  Route::post('/ngo/director/update', [DirectorController::class, 'update']);
  Route::post('/ngo/director/store', [DirectorController::class, 'store']);
});

// ngo user 

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'ngo:api'])->group(function () {});
