
<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\api\app\checklist\CheckListController;
use Illuminate\Support\Facades\Route;



Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::get('internal/check-list', [CheckListController::class, 'internalCheckList']);
  Route::get('external/check-list', [CheckListController::class, 'externalCheckList']);
  Route::delete('delete/check-list/{id}', [CheckListController::class, 'destroy']);
  Route::post('update/check-list', [CheckListController::class, 'update']);
});
