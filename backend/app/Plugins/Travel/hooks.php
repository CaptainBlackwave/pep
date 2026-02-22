<?php

use App\Facades\Hook;

// Register a class alias so any code still using App\Core\Models\Location resolves
// to the real Travel model when this plugin is loaded.
if (! class_exists(\App\Core\Models\Location::class)) {
    class_alias(
        \App\Plugins\Travel\Models\Location::class,
        \App\Core\Models\Location::class
    );
}

// Register location relations on User and PlayerProfile from this plugin rather than
// embedding plugin class names in the Core models.
\App\Core\Models\User::resolveRelationUsing('location', function ($user) {
    return $user->hasOneThrough(
        \App\Plugins\Travel\Models\Location::class,
        \App\Core\Models\PlayerProfile::class,
        'user_id',     // FK on player_profiles → users
        'id',          // PK on locations
        'id',          // PK on users
        'location_id'  // FK on player_profiles → locations
    );
});

\App\Core\Models\PlayerProfile::resolveRelationUsing('currentLocation', function ($profile) {
    return $profile->belongsTo(\App\Plugins\Travel\Models\Location::class, 'location_id');
});

// Player travel event
Hook::register('OnPlayerTravel', function ($data) {
    // Log travel
    activity()
        ->causedBy($data['player'])
        ->withProperties([
            'from' => $data['from']->name,
            'to' => $data['to']->name,
            'cost' => $data['cost']
        ])
        ->log('player_traveled');
});

// Location enter event
Hook::register('OnLocationEnter', function ($data) {
    // Update location-specific state
    cache()->increment('location_' . $data['location']->id . '_players');
    
    // Trigger location-specific events
    event(new \App\Events\PlayerEnteredLocation($data['player'], $data['location']));
});

// Location leave event
Hook::register('OnLocationLeave', function ($data) {
    // Update location-specific state
    cache()->decrement('location_' . $data['location']->id . '_players');
});
