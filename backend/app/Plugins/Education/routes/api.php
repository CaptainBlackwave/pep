<?php

use App\Plugins\Education\Controllers\PlayerEducationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Education API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('education')->group(function () {
    Route::get('/courses', [PlayerEducationController::class, 'availableCourses']);
    Route::get('/courses/{course}', [PlayerEducationController::class, 'showCourse']);
    Route::post('/enroll', [PlayerEducationController::class, 'enroll']);
    Route::post('/cancel', [PlayerEducationController::class, 'cancel']);
    Route::get('/progress', [PlayerEducationController::class, 'progress']);
    Route::get('/history', [PlayerEducationController::class, 'history']);
});

// Public read-only
Route::prefix('education')->group(function () {
    Route::get('/catalog', [PlayerEducationController::class, 'catalog']);
});
