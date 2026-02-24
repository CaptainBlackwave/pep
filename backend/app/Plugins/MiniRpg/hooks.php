<?php

/**
 * Mini RPG Plugin Hooks
 *
 * Demonstrates how to register hooks for plugin integration.
 */

use App\Facades\Hook;

// Initialize RPG data when a new user is created
Hook::register('user.created', function ($user) {
    // Set default RPG stats using the metadata trait
    $user->setManyPluginMeta('mini-rpg', [
        'gold' => 100,
        'level' => 1,
        'experience' => 0,
        'health' => 100,
        'max_health' => 100,
        'attack' => 10,
        'defense' => 5,
        'kills' => 0,
        'deaths' => 0,
    ]);
}, 10);

// Add RPG stats to the user profile display
Hook::register('user.profile.widgets', function ($widgets) {
    $widgets['rpg_stats'] = [
        'title' => 'RPG Stats',
        'component' => 'RpgStatsWidget.vue',
        'order' => 20,
    ];
    return $widgets;
}, 10);

// Add RPG navigation menu items
Hook::register('customMenus', function ($user) {
    if (!$user) return [];

    return [
        'rpg' => [
            'title' => 'RPG',
            'items' => [
                [
                    'url' => route('mini-rpg.dashboard'),
                    'text' => 'Dashboard',
                    'icon' => '⚔️',
                    'badge' => $user->getPluginMeta('mini-rpg', 'level', 1),
                    'sort' => 50,
                ],
                [
                    'url' => route('mini-rpg.combat'),
                    'text' => 'Combat',
                    'icon' => '🗡️',
                    'sort' => 51,
                ],
                [
                    'url' => route('mini-rpg.shop'),
                    'text' => 'Shop',
                    'icon' => '🛒',
                    'sort' => 52,
                ],
            ],
        ],
    ];
}, 10);

// Modify stats display to include RPG data
Hook::register('stats.display', function ($stats) {
    $user = auth()->user();

    if ($user) {
        $stats['rpg'] = [
            'gold' => $user->getPluginMeta('mini-rpg', 'gold', 0),
            'level' => $user->getPluginMeta('mini-rpg', 'level', 1),
            'experience' => $user->getPluginMeta('mini-rpg', 'experience', 0),
            'attack' => $user->getPluginMeta('mini-rpg', 'attack', 10),
            'defense' => $user->getPluginMeta('mini-rpg', 'defense', 5),
        ];
    }

    return $stats;
}, 10);

// Contribute to the admin dashboard widgets
Hook::register('admin.dashboard.widgets', function ($widgets) {
    $widgets['rpg'] = [
        'total_players' => \App\Core\Models\User::count(),
        'total_gold' => \App\Core\Models\PluginMetadata::where('plugin_id', 'mini-rpg')
            ->where('key', 'gold')
            ->sum('value'),
        'avg_level' => \App\Core\Models\PluginMetadata::where('plugin_id', 'mini-rpg')
            ->where('key', 'level')
            ->avg('value') ?? 1,
    ];
    return $widgets;
}, 10);
