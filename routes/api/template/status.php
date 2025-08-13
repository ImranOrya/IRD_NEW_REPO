
<?php

use App\Enums\PermissionEnum;
use App\Enums\SubPermissionEnum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\template\StatusController;

Route::prefix('v1')->middleware(["authorized:" . 'user:api'])->group(function () {
  Route::get('/block/status/{id}', [StatusController::class, 'blockStatus']);

  // NGO
  Route::get('/statuses/ngo/{id}', [StatusController::class, 'ngoStatuses'])->middleware(["userHasSubViewPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_status->value]);
  Route::post('/statuses/ngo/modify', [StatusController::class, 'changeNgoStatus'])->middleware(["userHasSubAddPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_status->value]);

  // DONOR
  Route::post('/statuses/donor/modify', [StatusController::class, 'changeDonorStatus'])->middleware(["userHasSubAddPermission:" . PermissionEnum::donor->value . "," . SubPermissionEnum::donor_status->value]);

  // Agreement
  Route::get('/statuses/agreements/{id}', [StatusController::class, 'agreementStatuses'])->middleware(["userHasSubViewPermission:" . PermissionEnum::ngo->value . "," . SubPermissionEnum::ngo_agreement_status->value]);
});
