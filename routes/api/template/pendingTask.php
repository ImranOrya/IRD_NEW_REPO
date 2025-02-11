
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\template\task\PendingTaskController;

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::post('store/ngo/register-task/{id}', [PendingTaskController::class, 'storeNgoRegisterTask']);
});
