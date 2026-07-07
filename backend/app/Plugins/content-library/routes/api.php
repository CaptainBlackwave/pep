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
    });
});
