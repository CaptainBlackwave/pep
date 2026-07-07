<?php

use Illuminate\Support\Facades\Route;

Route::prefix('community/discussions')->controller('CommunityDiscussionModerationController')->group(function () {
    Route::get('/', 'index');
    Route::patch('/{discussion}', 'update');
    Route::delete('/{discussion}', 'destroy');
});

Route::apiResource('community/categories', 'CommunityCategoryAdminController')
    ->parameters(['categories' => 'category']);
