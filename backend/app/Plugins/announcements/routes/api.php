<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('community')->group(function () {
        Route::get('/announcements', 'CommunityAnnouncementController@index');
        Route::get('/announcements/{announcement}', 'CommunityAnnouncementController@show');
    });

    Route::middleware('auth:sanctum')->prefix('community')->group(function () {
        Route::post('/announcements', 'CommunityAnnouncementController@store');
    });
});
