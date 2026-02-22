<?php

use App\Plugins\DailyRewards\DailyRewardsModule;

// Register the dailyReward relation on User from this plugin rather than embedding
// the plugin class name in the Core User model.
\App\Core\Models\User::resolveRelationUsing('dailyReward', function ($user) {
    return $user->hasOne(\App\Plugins\DailyRewards\Models\DailyReward::class);
});

return [
    /**
     * Triggered when a player claims their daily reward
     */
    'OnDailyRewardClaimed' => function ($data) {
        // Log daily reward claim
        \Log::info('Daily reward claimed', [
            'player' => $data['player']->username,
            'streak' => $data['streak'],
            'rewards' => $data['rewards'],
        ]);
        
        return $data;
    },
    
    /**
     * Triggered when a player reaches a streak milestone
     */
    'OnStreakMilestone' => function ($data) {
        // Log milestone achievement
        \Log::info('Streak milestone reached', [
            'player' => $data['player']->username,
            'milestone' => $data['milestone'],
            'bonus_rewards' => $data['bonus'],
        ]);
        
        // Could trigger special notifications or achievements
        
        return $data;
    },
    
    /**
     * Alter daily reward before awarding
     */
    'alterDailyReward' => function ($data) {
        // Can be used to modify rewards based on special events
        return $data;
    },
];

// Register daily rewards service binding so Core controllers can resolve it without importing the class.
if (! app()->bound('daily_rewards.service')) {
    app()->singleton('daily_rewards.service', fn () => app(\App\Plugins\DailyRewards\Services\DailyRewardService::class));
}
