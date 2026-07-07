<?php

use Illuminate\Support\Facades\Route;

Route::prefix('community/content')->controller('CommunityContentAdminController')->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::patch('/{content}', 'update');
    Route::delete('/{content}', 'destroy');
});
