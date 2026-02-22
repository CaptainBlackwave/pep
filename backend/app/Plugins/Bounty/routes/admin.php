<?php

use App\Plugins\Bounty\Controllers\BountyController;
use Illuminate\Support\Facades\Route;

Route::get('bounty-statuses', fn() => response()->json(['active', 'claimed', 'expired']));
Route::apiResource('bounties', BountyController::class);
