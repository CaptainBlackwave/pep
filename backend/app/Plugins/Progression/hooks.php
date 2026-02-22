<?php

/**
 * Progression plugin hooks.
 *
 * Registers:
 * 1. A class alias so any existing `use App\Core\Models\Rank` keeps compiling unchanged.
 * 2. The CheckUserRank middleware into the 'api' group so it runs on every
 *    authenticated API request.
 */

// Backward-compat alias — any stale import of App\Core\Models\Rank still resolves.
if (! class_exists(\App\Core\Models\Rank::class)) {
    class_alias(
        \App\Plugins\Progression\Models\Rank::class,
        \App\Core\Models\Rank::class
    );
}

// Wire CheckUserRank into the 'api' middleware group.
app('router')->pushMiddlewareToGroup(
    'api',
    \App\Plugins\Progression\Middleware\CheckUserRank::class
);

// Bind the progression service so Core can call app()->bound('progression.service')
// without referencing the plugin class name directly.
app()->singleton('progression.service', fn () => app(\App\Plugins\Progression\Services\ProgressionService::class));

// Register currentRank relation on PlayerProfile so $profile->currentRank works
// without PlayerProfile importing the plugin Rank class.
\App\Core\Models\PlayerProfile::resolveRelationUsing('currentRank', function ($profile) {
    return $profile->belongsTo(\App\Plugins\Progression\Models\Rank::class, 'rank_id');
});

// Register currentRank HasOneThrough on User (reads through player_profiles.rank_id).
\App\Core\Models\User::resolveRelationUsing('currentRank', function ($user) {
    return $user->hasOneThrough(
        \App\Plugins\Progression\Models\Rank::class,
        \App\Core\Models\PlayerProfile::class,
        'user_id', // FK on player_profiles → users
        'id',      // PK on ranks
        'id',      // PK on users
        'rank_id'  // FK on player_profiles → ranks
    );
});

// Register User methods that depend on the Progression plugin via macro.
// These replace the methods that were removed from User.php in the core-purity pass.
\App\Core\Models\User::macro('checkRank', function () {
    if (!app()->bound('progression.service')) {
        return false;
    }
    return app('progression.service')->checkRank($this);
});

\App\Core\Models\User::macro('addExperience', function (int $amount) {
    if (!app()->bound('progression.service')) {
        return ['xp_gained' => 0, 'ranked_up' => false];
    }
    return app('progression.service')->addExperience($this, $amount);
});

\App\Core\Models\User::macro('getNextRankAttribute', function () {
    if (!app()->bound('progression.service')) {
        return null;
    }
    return app('progression.service')->getNextRank($this);
});

\App\Core\Models\User::macro('getExpProgressAttribute', function () {
    if (!app()->bound('progression.service')) {
        return 0.0;
    }
    return app('progression.service')->getExpProgress($this);
});
