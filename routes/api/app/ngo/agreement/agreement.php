
<?php

use App\Http\Controllers\api\app\agreement\AgreementController;
use Illuminate\Support\Facades\Route;







Route::get('/ngo/agreement/documents/{agreement_id}', [AgreementController::class, 'agreementDocument']);

Route::prefix('v1')->group(function () {});

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
  Route::get('/ngo/agreement/{ngo_id}', [AgreementController::class, 'agreement']);
});

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'ngo:api'])->group(function () {});
