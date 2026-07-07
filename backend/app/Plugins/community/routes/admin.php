<?php

use Illuminate\Support\Facades\Route;

Route::prefix('users')->controller('UserManagementController')->group(function () {
    Route::get('/', 'index');
    Route::get('/statistics', 'statistics');
    Route::post('/', 'store');
    Route::get('/{user}', 'show');
    Route::patch('/{user}', 'update');
    Route::delete('/{user}', 'destroy');
    Route::post('/{user}/ban', 'ban');
    Route::post('/{user}/unban', 'unban');
    Route::patch('/{user}/vendor-access', 'updateVendorAccess');
    Route::post('/{user}/vendor-profile', 'grantVendorProfile');
});

Route::prefix('community/notifications')->controller('CommunityNotificationAdminController')->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::patch('/{notification}', 'update');
    Route::delete('/{notification}', 'destroy');
});
