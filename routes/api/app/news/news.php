
<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\api\app\news\NewsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
  Route::get('/public/newses/{page}', [NewsController::class, "publicNewses"]);
  Route::get('/public/news/{id}', [NewsController::class, "publicNews"]);
});
Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::get('/user/newses/{page}', [NewsController::class, "authNewses"]);
  Route::get('/user/news/{id}', [NewsController::class, "authNews"]);
  Route::post('news/store', [NewsController::class, 'store']);
  Route::post('/news/update', [NewsController::class, "update"]);
  Route::delete('/news/{id}', [NewsController::class, "destroy"]);
});
