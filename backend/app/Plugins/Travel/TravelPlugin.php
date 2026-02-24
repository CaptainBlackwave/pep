<?php

namespace App\Plugins\Travel;

use App\Plugins\Plugin;
use App\Core\Contracts\PluginInterface;

/**
 * Travel Plugin
 */
class TravelPlugin extends Plugin implements PluginInterface
{
    protected array $locations = [
        ['id' => 'new_york', 'name' => 'New York', 'cost' => 0],
        ['id' => 'london', 'name' => 'London', 'cost' => 500],
        ['id' => 'tokyo', 'name' => 'Tokyo', 'cost' => 1000],
        ['id' => 'moscow', 'name' => 'Moscow', 'cost' => 750],
        ['id' => 'sydney', 'name' => 'Sydney', 'cost' => 1500],
    ];

    public function __construct() { parent::__construct(app_path('Plugins/Travel')); }
    public function register(): void {}
    public function boot(): void { $this->registerHooks(); }
    public function install(): void { $this->log('info', 'Travel installed'); }
    public function enable(): void { $this->log('info', 'Travel enabled'); }
    public function disable(): void { $this->log('info', 'Travel disabled'); }
    public function uninstall(): void { \App\Core\Models\PluginMetadata::where('plugin_id', 'travel')->delete(); }
    public function upgrade(string $f, string $t): void {}

    public function getLocations(): array { return $this->locations; }

    public function travel($user, string $locationId): array
    {
        $location = collect($this->locations)->firstWhere('id', $locationId);
        if (!$location) { return ['success' => false, 'message' => 'Invalid location']; }

        $current = $user->getPluginMeta('travel', 'location', 'new_york');
        if ($current === $locationId) { return ['success' => false, 'message' => 'Already there']; }

        if (($user->cash ?? 0) < $location['cost']) { return ['success' => false, 'message' => 'Not enough cash']; }

        $user->decrement('cash', $location['cost']);
        $user->setPluginMeta('travel', 'location', $locationId);
        $user->setPluginMeta('travel', 'travel_count', $user->getPluginMeta('travel', 'travel_count', 0) + 1);

        broadcastToPluginUser($user->id, 'travel', 'arrived', ['location' => $location]);

        return ['success' => true, 'message' => "Traveled to {$location['name']}", 'location' => $location];
    }

    public function getCurrentLocation($user): array
    {
        $id = $user->getPluginMeta('travel', 'location', 'new_york');
        return collect($this->locations)->firstWhere('id', $id) ?? $this->locations[0];
    }
}
