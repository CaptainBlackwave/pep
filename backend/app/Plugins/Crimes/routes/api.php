<?php

/**
 * Crimes API Routes
 */

use Illuminate\Support\Facades\Route;
use App\Plugins\Crimes\Controllers\Api\CrimesController;

// Get all available crimes
Route::get('/', [CrimesController::class, 'index'])->name('crimes.index');

// Get a specific crime
Route::get('/{id}', [CrimesController::class, 'show'])->name('crimes.show');

// Attempt to commit a crime
Route::post('/{id}/attempt', [CrimesController::class, 'attempt'])->name('crimes.attempt');

// Get user's crime stats
Route::get('/stats', [CrimesController::class, 'stats'])->name('crimes.stats');

// Get crime history
Route::get('/history', [CrimesController::class, 'history'])->name('crimes.history');
