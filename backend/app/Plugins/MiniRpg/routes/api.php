<?php

/**
 * Mini RPG API Routes
 *
 * Demonstrates plugin API route registration.
 */

use Illuminate\Support\Facades\Route;
use App\Plugins\MiniRpg\Controllers\Api\RpgController;
use App\Plugins\MiniRpg\Controllers\Api\CombatController;

/*
|--------------------------------------------------------------------------
| RPG API Routes
|--------------------------------------------------------------------------
*/

// Get user's RPG stats
Route::get('/stats', [RpgController::class, 'stats'])->name('mini-rpg.stats');

// Work to earn gold
Route::post('/work', [RpgController::class, 'work'])->name('mini-rpg.work');

// Get leaderboard
Route::get('/leaderboard', [RpgController::class, 'leaderboard'])->name('mini-rpg.leaderboard');

/*
|--------------------------------------------------------------------------
| Combat Routes
|--------------------------------------------------------------------------
*/

// Get combat arena status
Route::get('/combat', [CombatController::class, 'index'])->name('mini-rpg.combat.index');

// Attack another player
Route::post('/combat/attack', [CombatController::class, 'attack'])->name('mini-rpg.combat.attack');

// Get combat history
Route::get('/combat/history', [CombatController::class, 'history'])->name('mini-rpg.combat.history');
