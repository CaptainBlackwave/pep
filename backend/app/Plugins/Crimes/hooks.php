<?php

/**
 * Crimes Module Hooks
 * 
 * Registers hooks for the Crimes module
 * Similar to V2's crimes.hooks.php
 */

use App\Facades\Hook;

// Add crimes to navigation menu
Hook::register('customMenus', function ($user) {
    if (!$user) return [];
    
    return [
        'crimes' => [
            'title' => 'Actions',
            'items' => [
                [
                    'url' => route('crimes.index'),
                    'text' => 'Crimes',
                    'icon' => '🔫',
                    'timer' => $user->getTimer('crime'),
                    'badge' => null,
                    'sort' => 100,
                ],
            ],
        ],
    ];
}, 10);

// Track crime statistics for missions
Hook::register('afterCrimeAttempt', function ($data) {
    $user = $data['user'];
    $crime = $data['crime'];
    $success = $data['success'];
    
    // Update mission progress if applicable
    if ($success) {
        // This would integrate with missions module
        event(new \App\Events\Module\OnCrimeCommit(
            $user,
            $crime->name,
            $success,
            $data['result']['cash_earned'] ?? 0,
            $data['result']['exp_earned'] ?? 0
        ));
    }
}, 10);

// Modify crime data before display
Hook::register('alterCrimeData', function ($data) {
    // Add any custom crime data modifications here
    return $data;
}, 10);

// Modify success rate based on equipment, buffs, etc.
Hook::register('modifyCrimeSuccessRate', function ($data) {
    $user = $data['user'];
    $finalRate = $data['final_rate'];
    
    // Example: Bonus from gang membership
    if ($user->gang_id) {
        $finalRate += 0.05; // 5% bonus
    }
    
    $data['final_rate'] = $finalRate;
    return $data;
}, 10);

// Contribute crime stats to the admin dashboard
Hook::register('admin.dashboard.widgets', function ($widgets) {
    $widgets['crimes'] = [
        'crimesToday'  => \DB::getSchemaBuilder()->hasTable('crime_attempts')
            ? \DB::table('crime_attempts')->whereDate('created_at', today())->count()
            : 0,
        'crimesGrowth' => (function () {
            if (! \DB::getSchemaBuilder()->hasTable('crime_attempts')) return 0;
            $today     = \DB::table('crime_attempts')->whereDate('created_at', today())->count();
            $yesterday = \DB::table('crime_attempts')->whereDate('created_at', today()->subDay())->count();
            return $yesterday > 0 ? round((($today - $yesterday) / $yesterday) * 100) : 0;
        })(),
        'crimeChart'   => (function () {
            if (! \DB::getSchemaBuilder()->hasTable('crime_attempts')) {
                return ['labels' => ['No data'], 'data' => [0]];
            }
            $types = \DB::table('crime_attempts')
                ->join('crimes', 'crime_attempts.crime_id', '=', 'crimes.id')
                ->select('crimes.name', \DB::raw('count(*) as total'))
                ->where('crime_attempts.created_at', '>=', \Carbon\Carbon::now()->subDays(30))
                ->groupBy('crimes.name')
                ->orderByDesc('total')
                ->limit(6)
                ->get();
            $labels = $types->pluck('name')->toArray();
            $data   = $types->pluck('total')->toArray();
            if (empty($labels)) {
                $labels = ['Petty Theft', 'Grand Theft', 'Assault', 'Drug Deal', 'Robbery', 'Other'];
                $data   = [0, 0, 0, 0, 0, 0];
            }
            return ['labels' => $labels, 'data' => $data];
        })(),
    ];
    return $widgets;
});

// Module load hook
Hook::register('moduleLoad', function ($data) {
    if ($data['module'] === 'crimes') {
        // Check if user is in jail
        if ($data['user']->isInJail()) {
            return [
                'redirect' => route('jail.index'),
                'message' => 'You cannot commit crimes while in jail',
            ];
        }
    }
    return $data;
}, 10);

// Register the crime_attempts relation on User from this plugin rather than embedding
// the plugin class name in the Core User model.
\App\Core\Models\User::resolveRelationUsing('crime_attempts', function ($user) {
    return $user->hasMany(\App\Plugins\Crimes\Models\CrimeAttempt::class);
});

// Register crimes service binding so Core controllers can resolve it without importing the class.
if (! app()->bound('crimes.service')) {
    app()->singleton('crimes.service', fn () => app(\App\Plugins\Crimes\Services\CrimeService::class));
}

// Register crime metrics for MetricsRegistry (used by CacheService and SystemController)
\App\Core\Services\MetricsRegistry::register('total_crimes', fn () =>
    \DB::table('crime_attempts')->count());
\App\Core\Services\MetricsRegistry::register('crimes_today', fn () =>
    \DB::table('crime_attempts')->whereDate('created_at', today())->count());
\App\Core\Services\MetricsRegistry::register('crimes_yesterday', fn () =>
    \DB::table('crime_attempts')->whereDate('created_at', today()->subDay())->count());
\App\Core\Services\MetricsRegistry::register('crimes_chart', fn () => (function () {
    $types = \DB::table('crime_attempts')
        ->join('crimes', 'crime_attempts.crime_id', '=', 'crimes.id')
        ->select('crimes.name', \DB::raw('count(*) as total'))
        ->where('crime_attempts.created_at', '>=', \Carbon\Carbon::now()->subDays(30))
        ->groupBy('crimes.name')->orderByDesc('total')->limit(6)->get();
    return $types->isEmpty()
        ? ['labels' => [], 'data' => []]
        : ['labels' => $types->pluck('name')->all(), 'data' => $types->pluck('total')->all()];
})());
