<?php

/**
 * Combat Module Hooks
 *
 * Registers hooks for the Combat module and self-registers the combat service binding.
 */

use App\Core\Contracts\CombatInterface;
use App\Facades\Hook;

// Self-register the Combat service binding so Core never imports this class directly.
// Feature::available('combat') returns true when this plugin is enabled.
if (! app()->bound('combat')) {
    app()->singleton('combat', fn ($app) => $app->make(\App\Plugins\Combat\Services\CombatService::class));
    app()->alias('combat', CombatInterface::class);
}

// Add combat to navigation menu
Hook::register('customMenus', function ($user) {
    if (!$user) return [];
    
    return [
        'combat' => [
            'title' => 'Actions',
            'items' => [
                [
                    'url' => route('combat.index'),
                    'text' => 'Combat',
                    'icon' => '⚔️',
                    'timer' => $user->getTimer('combat'),
                    'badge' => null,
                    'sort' => 600,
                ],
            ],
        ],
    ];
}, 60);

// Track combat events
Hook::register('afterCombat', function ($data) {
    $attacker = $data['attacker'];
    $defender = $data['defender'];
    $result = $data['result'];
    
    // Fire event for mission tracking
    event(new \App\Events\Module\OnCombat(
        $attacker,
        $defender,
        $result['winner'],
        $result['killed'] ?? false,
        $result['cash_stolen'] ?? 0
    ));
    
    // If someone was killed, trigger kill event
    if ($result['killed'] && $result['winner'] === 'attacker') {
        event(new \App\Events\Module\OnPlayerKilled(
            $attacker,
            $defender
        ));
    }
}, 10);

// Modify combat target data
Hook::register('alterCombatTarget', function ($data) {
    // Can be used by other modules to modify target display
    return $data;
}, 10);

// Modify combat power calculation
Hook::register('modifyCombatPower', function ($data) {
    // Can be used by other modules to affect combat power
    return $data;
}, 10);

// Register combat metrics for MetricsRegistry (used by CacheService and SystemController)
\App\Core\Services\MetricsRegistry::register('total_combats', fn () =>
    \App\Plugins\Combat\Models\CombatLog::count());
\App\Core\Services\MetricsRegistry::register('combats_today', fn () =>
    \App\Plugins\Combat\Models\CombatLog::whereDate('created_at', today())->count());
