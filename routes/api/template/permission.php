
<?php

use App\Http\Controllers\api\template\PermissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
    Route::get('/permissions/{id}', [PermissionController::class, "permissions"]);
});
