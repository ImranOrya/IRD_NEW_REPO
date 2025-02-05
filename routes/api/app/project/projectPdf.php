
<?php

use App\Http\Controllers\api\app\project\ProjectPdfController;
use App\Http\Controllers\api\app\ProjectController;
use Illuminate\Support\Facades\Route;




Route::prefix('v1')->group(function () {

  Route::get('project/mou/generate/{id}', [ProjectPdfController::class, 'generateForm']);
});
