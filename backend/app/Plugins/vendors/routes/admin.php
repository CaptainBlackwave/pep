<?php

use Illuminate\Support\Facades\Route;

Route::prefix('community/vendor-reviews')->controller('CommunityVendorReviewAdminController')->group(function () {
    Route::get('/', 'index');
    Route::patch('/{review}', 'update');
    Route::delete('/{review}', 'destroy');
});

Route::prefix('community/vendors')->controller('CommunityVendorAdminController')->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::patch('/{vendor}', 'update');
    Route::delete('/{vendor}', 'destroy');
});

Route::prefix('community/vendor-products')->controller('CommunityVendorProductAdminController')->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::patch('/{product}', 'update');
    Route::delete('/{product}', 'destroy');
});

Route::prefix('community/vendor-claims')->controller('CommunityVendorClaimAdminController')->group(function () {
    Route::get('/', 'index');
    Route::patch('/{claim}', 'update');
});

Route::get('/vendor-access/requests', 'VendorAccessRequestController@index');
Route::patch('/vendor-access/requests/{vendorAccessRequest}', 'VendorAccessRequestController@update');
