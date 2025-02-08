
<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\api\app\ngo\NgoPdfController;
use Illuminate\Support\Facades\Route;





Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::get('/ngo/generate/registeration/{id}', [NgoPdfController::class, 'generateForm']);
});


// ngo user 
Route::prefix('v1')->middleware(['api.key', "authorized:" . 'ngo:api'])->group(function () {
  Route::get('/ngo/generate/registeration/{id}', [NgoPdfController::class, 'generateForm']);
});
