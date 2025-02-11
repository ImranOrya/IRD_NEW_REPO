
<?php

use App\Http\Controllers\api\template\StatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::get('/block/statuse/types', [StatusController::class, 'blockStatusesType']);
});
