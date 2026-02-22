<?php

use App\Plugins\Achievements\Controllers\AdminAchievementsController;
use Illuminate\Support\Facades\Route;

Route::get('achievement-stats', [AdminAchievementsController::class, 'stats']);
Route::apiResource('achievements', AdminAchievementsController::class);
