<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'email_notifications',
        'push_notifications',
        'sound_enabled',
        'show_online',
        'public_profile',
        'profile_visibility',
        'direct_messages',
        'show_read_topics',
        'show_typing',
        'show_recent_activity',
        'personalize_experience',
        'allow_analytics',
        'compact_discussions',
        'show_online_members',
        'remember_content_filters',
        'theme',
        'language',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'sound_enabled' => 'boolean',
        'show_online' => 'boolean',
        'public_profile' => 'boolean',
        'show_read_topics' => 'boolean',
        'show_typing' => 'boolean',
        'show_recent_activity' => 'boolean',
        'personalize_experience' => 'boolean',
        'allow_analytics' => 'boolean',
        'compact_discussions' => 'boolean',
        'show_online_members' => 'boolean',
        'remember_content_filters' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
