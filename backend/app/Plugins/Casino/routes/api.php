<?php

use Illuminate\Support\Facades\Route;
use App\Plugins\Casino\Controllers\CasinoPlayerController;

Route::middleware(['auth:sanctum'])->prefix('casino')->group(function () {
    Route::get('/games', [CasinoPlayerController::class, 'games']);
    Route::post('/play/slots', [CasinoPlayerController::class, 'playSlots']);
    Route::post('/play/roulette', [CasinoPlayerController::class, 'playRoulette']);
    Route::post('/play/dice', [CasinoPlayerController::class, 'playDice']);
    Route::get('/stats', [CasinoPlayerController::class, 'stats']);
    Route::get('/history', [CasinoPlayerController::class, 'history']);
    Route::post('/lottery/buy', [CasinoPlayerController::class, 'buyLotteryTicket']);
});
