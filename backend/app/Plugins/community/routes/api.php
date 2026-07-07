<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware('auth:sanctum')->prefix('community')->group(function () {
        Route::get('/members', 'CommunityMemberController@index')->middleware('tier:paid');
        Route::get('/members/{member}', 'CommunityMemberController@show')->middleware('tier:paid');
        Route::get('/messages', 'CommunityMessageController@index')->middleware('tier:paid');
        Route::get('/messages/{thread}', 'CommunityMessageController@show')->middleware('tier:paid');
        Route::get('/notifications', 'CommunityNotificationController@index');
        Route::get('/notifications/{notification}', 'CommunityNotificationController@show');
        Route::post('/notifications/{notification}/read', 'CommunityNotificationController@markAsRead');
        Route::post('/notifications/read-all', 'CommunityNotificationController@markAllAsRead');
        Route::delete('/notifications/read/clear', 'CommunityNotificationController@deleteRead');
        Route::delete('/notifications/{notification}', 'CommunityNotificationController@delete');
    });
});
