<?php

/**
 * Achievements Module Hooks
 */

// Register the achievements relation on User from this plugin rather than embedding
// the plugin class name in the Core User model.
\App\Core\Models\User::resolveRelationUsing('achievements', function ($user) {
    return $user->belongsToMany(\App\Plugins\Achievements\Models\Achievement::class, 'user_achievements')
        ->withPivot('progress', 'earned_at')
        ->withTimestamps();
});

return [
    // Called when an achievement is earned
    'OnAchievementEarned' => function($data) {
        // Log achievement, send notification, etc.
        return $data;
    },
    
    // Called when progress is updated
    'OnProgressUpdate' => function($data) {
        return $data;
    },
    
    // Modify achievement rewards before giving
    'alterAchievementRewards' => function($data) {
        // Could apply membership bonuses, etc.
        return $data;
    },
    
    // Called when module loads
    'moduleLoad' => function() {
        // Initialization
    },
];
