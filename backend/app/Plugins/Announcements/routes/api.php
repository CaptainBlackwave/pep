<?php

use Illuminate\Support\Facades\Route;
use App\Plugins\Announcements\Controllers\AnnouncementsController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Player-facing announcements
    Route::get('/announcements', [AnnouncementsController::class, 'index']);
    Route::post('/announcements/{announcement}/view', [AnnouncementsController::class, 'markViewed']);
});
