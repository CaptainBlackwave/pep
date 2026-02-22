<?php

use App\Core\Contracts\InventoryInterface;
use App\Facades\Hook;

// Self-register the Inventory service binding so Core never imports this class directly.
// Feature::available('inventory') returns true when this plugin is enabled.
if (! app()->bound('inventory')) {
    app()->singleton('inventory', fn ($app) => $app->make(\App\Plugins\Inventory\Services\InventoryService::class));
    app()->alias('inventory', InventoryInterface::class);
}

// Item purchase event
Hook::register('OnItemBought', function ($data) {
    // Log item purchase
    activity()
        ->causedBy($data['player'])
        ->withProperties([
            'item' => $data['item']->name,
            'quantity' => $data['quantity'],
            'cost' => $data['cost']
        ])
        ->log('item_bought');
});

// Item sale event
Hook::register('OnItemSold', function ($data) {
    // Log item sale
    activity()
        ->causedBy($data['player'])
        ->withProperties([
            'item' => $data['item']->name,
            'quantity' => $data['quantity'],
            'earnings' => $data['earnings']
        ])
        ->log('item_sold');
});

// Item equipped event
Hook::register('OnItemEquipped', function ($data) {
    // Log equipment change
    activity()
        ->causedBy($data['player'])
        ->withProperties(['item' => $data['item']->name])
        ->log('item_equipped');
    
    // Update player stats cache
    cache()->forget('player_stats_' . $data['player']->id);
});

// Item unequipped event
Hook::register('OnItemUnequipped', function ($data) {
    // Log equipment change
    activity()
        ->causedBy($data['player'])
        ->withProperties(['item' => $data['item']->name])
        ->log('item_unequipped');
    
    // Update player stats cache
    cache()->forget('player_stats_' . $data['player']->id);
});

// Item used event
Hook::register('OnItemUsed', function ($data) {
    // Log item usage
    activity()
        ->causedBy($data['player'])
        ->withProperties(['item' => $data['item']->name])
        ->log('item_used');
});

// Register inventory relations on User from this plugin rather than embedding
// plugin class names in the Core User model.
\App\Core\Models\User::resolveRelationUsing('inventory', function ($user) {
    return $user->hasMany(\App\Plugins\Inventory\Models\UserInventory::class);
});

\App\Core\Models\User::resolveRelationUsing('items', function ($user) {
    return $user->hasMany(\App\Plugins\Inventory\Models\UserInventory::class);
});

\App\Core\Models\User::resolveRelationUsing('equipment', function ($user) {
    return $user->hasMany(\App\Plugins\Inventory\Models\UserEquipment::class);
});
