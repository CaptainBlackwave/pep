<?php

use App\Plugins\Employment\Controllers\EmploymentApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Employment API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('employment')->group(function () {
    Route::get('/positions', [EmploymentApiController::class, 'index']);
    Route::get('/current', [EmploymentApiController::class, 'currentJob']);
    Route::post('/apply', [EmploymentApiController::class, 'apply']);
    Route::post('/work', [EmploymentApiController::class, 'work']);
    Route::post('/quit', [EmploymentApiController::class, 'quit']);
});
