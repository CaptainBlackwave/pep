<?php

use App\Plugins\Announcements\Controllers\AdminAnnouncementsController;
use Illuminate\Support\Facades\Route;

Route::get('announcement-types', [AdminAnnouncementsController::class, 'types']);
Route::apiResource('content/announcements', AdminAnnouncementsController::class);
