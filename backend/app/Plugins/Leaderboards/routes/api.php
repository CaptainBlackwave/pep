<?php

use App\Plugins\Leaderboards\Controllers\LeaderboardApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Leaderboards API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('leaderboards')->group(function () {
    Route::get('/', [LeaderboardApiController::class, 'index']);
    Route::get('/types', [LeaderboardApiController::class, 'types']);
    Route::get('/{type}', [LeaderboardApiController::class, 'show']);
    Route::get('/{type}/rank', [LeaderboardApiController::class, 'myRank']);
});
