
<?php

use App\Enums\PermissionEnum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\app\ngo\NgoTypeController;


Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {

  Route::get('/ngo-types', [NgoTypeController::class, 'types'])->middleware(["hasViewPermission:" . PermissionEnum::settings->value]);
});
