<?php

use Illuminate\Support\Facades\Route;

Route::prefix('community/announcements')->controller('CommunityAnnouncementAdminController')->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::patch('/{announcement}', 'update');
    Route::delete('/{announcement}', 'destroy');
});
