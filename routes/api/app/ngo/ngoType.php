
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\app\ngo\NgoTypeController;

Route::prefix('v1')->middleware(['api.key', "multiAuthorized:" . 'user:api,ngo:api,donor:api'])->group(function () {
  Route::get('/ngo-types', [NgoTypeController::class, 'types']);
});
