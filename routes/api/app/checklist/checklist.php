<?php

use App\Enums\PermissionEnum;
use App\Enums\SubPermissionEnum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\app\checklist\CheckListController;



Route::prefix('v1')->middleware(['api.key', "multiAuthorized:" . 'user:api,ngo:api'])->group(function () {
  Route::get('checklists', [CheckListController::class, 'checklists']);
  Route::get('ngo/checklist/types', [CheckListController::class, 'checklistTypes']);
  Route::get('project/register/checklist', [CheckListController::class, 'projectRegister']);
  Route::get('ngo/register/checklist', [CheckListController::class, 'ngoRegister']);
  Route::get('ngo/register/abroad/director-checklist', [CheckListController::class, 'ngoRegisterAbroadDirector']);
  Route::get('ngo/extend/checklist', [CheckListController::class, 'ngoExtend']);
  Route::get('ngo/extend/abroad/director-checklist', [CheckListController::class, 'ngoExtendAbroadDirector']);
  Route::get('ngo-checklist/{id}', [CheckListController::class, 'checklist']);
  Route::get('ngo/common-checklist/{id}', [CheckListController::class, 'commonChecklist']);
  Route::get('ngo/register/signed/form/checklist', [CheckListController::class, 'missingRegisterSignedForm']);
  Route::get('ngo/validation/checklist/{id}', [CheckListController::class, 'validationChecklist']);
});

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::post('checklist/store', [CheckListController::class, 'store'])->middleware(["userHasSubAddPermission:" . PermissionEnum::settings->value . "," . SubPermissionEnum::setting_checklist->value]);
  Route::delete('checklist/{id}', [CheckListController::class, 'destroy'])->middleware(["userHasSubDeletePermission:" . PermissionEnum::settings->value . "," . SubPermissionEnum::setting_checklist->value]);;
  Route::post('checklist/update', [CheckListController::class, 'update'])->middleware(["userHasSubEditPermission:" . PermissionEnum::settings->value . "," . SubPermissionEnum::setting_checklist->value]);;
});
