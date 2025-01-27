
<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\api\app\file\FileController;
use Illuminate\Support\Facades\Route;



Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::post('news/file/upload', [FileController::class, 'newsFileUpload']);
});

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::post('file/upload', [FileController::class, 'uploadFile'])->withoutMiddleware('throttle');
});
