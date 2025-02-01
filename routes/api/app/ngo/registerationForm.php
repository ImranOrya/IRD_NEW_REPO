
<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\api\app\ngo\NgoPdfController;
use Illuminate\Support\Facades\Route;



Route::get('/ngo/generate/registeration/{id}', [NgoPdfController::class, 'generateForm']);


Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {});


// ngo user 
Route::prefix('v1')->middleware(['api.key', "authorized:" . 'ngo:api'])->group(function () {});
