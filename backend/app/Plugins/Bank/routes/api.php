<?php

/**
 * Bank API Routes
 */

use Illuminate\Support\Facades\Route;
use App\Plugins\Bank\Controllers\Api\BankController;

// Get bank balance and stats
Route::get('/', [BankController::class, 'index'])->name('bank.index');

// Deposit
Route::post('/deposit', [BankController::class, 'deposit'])->name('bank.deposit');

// Withdraw
Route::post('/withdraw', [BankController::class, 'withdraw'])->name('bank.withdraw');

// Transfer
Route::post('/transfer', [BankController::class, 'transfer'])->name('bank.transfer');

// Transaction history
Route::get('/history', [BankController::class, 'history'])->name('bank.history');
