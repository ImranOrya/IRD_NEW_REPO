
<?php

use App\Http\Controllers\api\template\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'user:api'])->group(function () {
    Route::post('/user/profile/picture-update', [ProfileController::class, 'updateUserPicture']);
    Route::post('/user/profile/info/update', [ProfileController::class, 'updateUserProfileInfo']);
});
Route::prefix('v1')->middleware(['api.key', "authorized:" . 'ngo:api'])->group(function () {
    Route::post('/ngo/profile/picture-update', [ProfileController::class, 'updateNgoPicture']);
    Route::post('/ngo/profile/info/update', [ProfileController::class, 'updateNgoProfileInfo']);
    Route::get('/ngo/profile/info/{id}', [ProfileController::class, 'ngoProfileInfo']);
});

Route::prefix('v1')->middleware(['api.key', "authorized:" . 'donor:api'])->group(function () {
    Route::post('/donor/profile/update', [ProfileController::class, 'updateDonorProfileInfo']);
    Route::post('/donor/profile/picture-update', [ProfileController::class, 'updateDonorPicture']);
});
Route::prefix('v1')->middleware(['api.key', "multiAuthorized:" . 'user:api,ngo:api,donor:api'])->group(function () {
    Route::delete('/delete/profile-picture', [ProfileController::class, 'deleteProfilePicture']);
});
