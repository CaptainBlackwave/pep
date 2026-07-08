<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('community')->group(function () {
        Route::get('/research-library', 'CommunityContentController@researchIndex');
        Route::get('/research-library/{content}', 'CommunityContentController@researchShow');
        Route::get('/guides', 'CommunityContentController@guideIndex');
        Route::get('/guides/{content}', 'CommunityContentController@guideShow');
        Route::get('/faqs', 'CommunityContentController@faqIndex');
        Route::get('/faqs/{content}', 'CommunityContentController@faqShow');

        Route::middleware('auth:sanctum')->prefix('content')->controller('CommunityContentContributorController')->group(function () {
            Route::get('/permissions', 'permissions');
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{content}', 'show');
            Route::patch('/{content}', 'update');
            Route::delete('/{content}', 'destroy');
        });
    });
});
