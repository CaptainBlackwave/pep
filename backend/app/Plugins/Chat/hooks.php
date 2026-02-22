<?php

/**
 * Chat Module Hooks
 */

// Register Chat relations on User from this plugin rather than embedding
// plugin class names in the Core User model.
\App\Core\Models\User::resolveRelationUsing('sentMessages', fn ($u) =>
    $u->hasMany(\App\Plugins\Chat\Models\PrivateMessage::class, 'sender_id'));
\App\Core\Models\User::resolveRelationUsing('receivedMessages', fn ($u) =>
    $u->hasMany(\App\Plugins\Chat\Models\PrivateMessage::class, 'recipient_id'));
\App\Core\Models\User::resolveRelationUsing('chatChannels', fn ($u) =>
    $u->belongsToMany(\App\Plugins\Chat\Models\ChatChannel::class, 'channel_members', 'user_id', 'channel_id')
      ->withPivot(['role', 'is_muted', 'last_read_at'])->withTimestamps());
\App\Core\Models\User::resolveRelationUsing('chatMessages', fn ($u) =>
    $u->hasMany(\App\Plugins\Chat\Models\ChatMessage::class, 'user_id'));
\App\Core\Models\User::resolveRelationUsing('channelMemberships', fn ($u) =>
    $u->hasMany(\App\Plugins\Chat\Models\ChannelMember::class, 'user_id'));
\App\Core\Models\User::resolveRelationUsing('sentDirectMessages', fn ($u) =>
    $u->hasMany(\App\Plugins\Chat\Models\DirectMessage::class, 'from_user_id'));
\App\Core\Models\User::resolveRelationUsing('receivedDirectMessages', fn ($u) =>
    $u->hasMany(\App\Plugins\Chat\Models\DirectMessage::class, 'to_user_id'));
\App\Core\Models\User::resolveRelationUsing('messageReactions', fn ($u) =>
    $u->hasMany(\App\Plugins\Chat\Models\MessageReaction::class, 'user_id'));
\App\Core\Models\User::resolveRelationUsing('createdChannels', fn ($u) =>
    $u->hasMany(\App\Plugins\Chat\Models\ChatChannel::class, 'created_by'));

return [
    'OnMessageSent' => function($data) {
        // Could trigger notifications, achievements
        return $data;
    },
    
    'OnMessageDeleted' => function($data) {
        return $data;
    },
    
    'OnChannelCreated' => function($data) {
        return $data;
    },
    
    'OnReaction' => function($data) {
        return $data;
    },
    
    // Filter/modify message content (profanity filter, etc.)
    'filterMessageContent' => function($data) {
        return $data;
    },
    
    'moduleLoad' => function() {
        // Initialization
    },
];
