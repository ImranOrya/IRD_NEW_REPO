
<?php

use App\Enums\PermissionEnum;
use App\Enums\SubPermissionEnum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\template\approval\ApprovalController;


Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::get('pending/user/approvals', [ApprovalController::class, 'pendingUserApproval'])->middleware(["userHasSubViewPermission:" . PermissionEnum::approval->value . "," . SubPermissionEnum::pending_approval->value]);
  Route::get('approved/user/approvals', [ApprovalController::class, 'approvedUserApproval'])->middleware(["userHasSubViewPermission:" . PermissionEnum::approval->value . "," . SubPermissionEnum::approved_approval->value]);
  Route::get('rejected/user/approvals', [ApprovalController::class, 'rejectedUserApproval'])->middleware(["userHasSubViewPermission:" . PermissionEnum::approval->value . "," . SubPermissionEnum::rejected_approval->value]);
  Route::get('pending/ngo/approvals', [ApprovalController::class, 'pendingNgoApproval'])->middleware(["userHasSubViewPermission:" . PermissionEnum::approval->value . "," . SubPermissionEnum::pending_approval->value]);
  Route::get('approved/ngo/approvals', [ApprovalController::class, 'approvedNgoApproval'])->middleware(["userHasSubViewPermission:" . PermissionEnum::approval->value . "," . SubPermissionEnum::approved_approval->value]);
  Route::get('rejected/ngo/approvals', [ApprovalController::class, 'rejectedNgoApproval'])->middleware(["userHasSubViewPermission:" . PermissionEnum::approval->value . "," . SubPermissionEnum::rejected_approval->value]);
  Route::get('pending/donor/approvals', [ApprovalController::class, 'pendingDonorApproval'])->middleware(["userHasSubViewPermission:" . PermissionEnum::approval->value . "," . SubPermissionEnum::pending_approval->value]);
  Route::get('approved/donor/approvals', [ApprovalController::class, 'approvedDonorApproval'])->middleware(["userHasSubViewPermission:" . PermissionEnum::approval->value . "," . SubPermissionEnum::approved_approval->value]);
  Route::get('rejected/donor/approvals', [ApprovalController::class, 'rejectedDonorApproval'])->middleware(["userHasSubViewPermission:" . PermissionEnum::approval->value . "," . SubPermissionEnum::rejected_approval->value]);
  // 
  Route::get('approval/{id}', [ApprovalController::class, 'approval'])->middleware(["userHasSubViewPermission:" . PermissionEnum::approval->value . "," . SubPermissionEnum::pending_approval->value]);
  Route::post('approval/submit', [ApprovalController::class, 'approvalSubmit'])->middleware(["userHasSubEditPermission:" . PermissionEnum::approval->value . "," . SubPermissionEnum::pending_approval->value]);
});
