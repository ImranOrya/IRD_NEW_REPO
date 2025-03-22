
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\template\task\PendingTaskController;

Route::prefix('v1')->middleware(['api.key', "multiAuthorized:" . 'user:api,ngo:api'])->group(function () {
  Route::post('store/task/with/content/{id}', [PendingTaskController::class, 'storeWithContent']);
});
