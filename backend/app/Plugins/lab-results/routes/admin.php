<?php

use Illuminate\Support\Facades\Route;

Route::prefix('community/lab-results')->controller('CommunityLabResultAdminController')->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::patch('/{result}', 'update');
    Route::delete('/{result}', 'destroy');
});
