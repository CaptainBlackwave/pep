<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('community')->group(function () {
        Route::get('/lab-results', 'CommunityLabResultController@index')->middleware('tier:paid');
        Route::get('/lab-results/{result}', 'CommunityLabResultController@show')->middleware('tier:paid');
    });

    Route::middleware('auth:sanctum')->prefix('community')->group(function () {
        Route::middleware('tier:paid')->group(function () {
            Route::post('/lab-results', 'CommunityLabResultController@store');
        });
    });
});
