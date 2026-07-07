<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('community')->group(function () {
        Route::get('/discussion-categories', 'CommunityDiscussionController@categories');
        Route::get('/discussions', 'CommunityDiscussionController@index');
        Route::get('/discussions/{discussion}', 'CommunityDiscussionController@show')
            ->middleware(\App\Core\Middleware\CheckDailyLimit::class);
    });

    Route::middleware('auth:sanctum')->prefix('community')->group(function () {
        Route::post('/discussions', 'CommunityDiscussionController@store');
        Route::patch('/discussions/{discussion}', 'CommunityDiscussionController@update');
        Route::delete('/discussions/{discussion}', 'CommunityDiscussionController@destroy');
        Route::post('/discussions/{discussion}/replies', 'CommunityDiscussionController@reply');
        Route::post('/discussions/{discussion}/vote', 'CommunityDiscussionController@voteOnDiscussion');
        Route::post('/discussions/{discussion}/report', 'CommunityDiscussionController@reportDiscussion');
        Route::post('/discussion-replies/{reply}/vote', 'CommunityDiscussionController@voteOnReply');
        Route::post('/discussion-replies/{reply}/report', 'CommunityDiscussionController@reportReply');
        Route::delete('/discussion-replies/{reply}', 'CommunityDiscussionController@destroyReply');
    });
});
