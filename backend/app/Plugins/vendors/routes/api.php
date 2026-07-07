<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('community')->group(function () {
        Route::get('/vendors', 'CommunityVendorController@index');
        Route::get('/vendors/{vendor}', 'CommunityVendorController@show');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/vendor-access/request', 'VendorAccessRequestController@store');
    });

    Route::middleware('auth:sanctum')->prefix('community')->group(function () {

        Route::middleware('tier:paid')->group(function () {
            Route::get('/vendor-profile', 'CommunityVendorController@myVendorProfile');
            Route::post('/vendor-profile/image', 'CommunityVendorController@uploadVendorImage');
            Route::post('/vendor-profile', 'CommunityVendorController@storeVendorProfile');
            Route::patch('/vendor-profile', 'CommunityVendorController@updateVendorProfile');

            Route::post('/vendor-profile/products', 'CommunityVendorController@storeVendorProduct');
            Route::post('/vendor-profile/products/{product}', 'CommunityVendorController@updateVendorProduct');
            Route::patch('/vendor-profile/products/{product}', 'CommunityVendorController@updateVendorProduct');
            Route::delete('/vendor-profile/products/{product}', 'CommunityVendorController@destroyVendorProduct');

            Route::post('/vendor-profile/documents', 'CommunityVendorController@storeVendorDocument');
            Route::delete('/vendor-profile/documents/{document}', 'CommunityVendorController@destroyVendorDocument');

            Route::post('/vendors/{vendor}/claim', 'CommunityVendorController@claimVendor');
            Route::post('/vendors/{vendor}/reviews', 'CommunityVendorController@storeReview');
            Route::post('/vendor-reviews/{review}/helpful', 'CommunityVendorController@markReviewHelpful');
            Route::post('/vendor-reviews/{review}/respond', 'CommunityVendorController@respondToReview');
        });
    });
});
