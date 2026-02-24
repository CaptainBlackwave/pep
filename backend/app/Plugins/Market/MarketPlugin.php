<?php

namespace App\Plugins\Market;

use App\Plugins\Plugin;
use App\Core\Contracts\PluginInterface;

class MarketPlugin extends Plugin implements PluginInterface
{
    public function __construct() { parent::__construct(app_path('Plugins/Market')); }
    public function register(): void {}
    public function boot(): void { $this->registerHooks(); }
    public function install(): void { $this->log('info', 'Market installed'); }
    public function enable(): void { $this->log('info', 'Market enabled'); }
    public function disable(): void { $this->log('info', 'Market disabled'); }
    public function uninstall(): void { \App\Core\Models\PluginMetadata::where('plugin_id', 'market')->delete(); }
    public function upgrade(string $f, string $t): void {}

    public function getStats($user): array
    {
        return [
            'listings' => $user->getPluginMeta('market', 'listings', 0),
            'purchases' => $user->getPluginMeta('market', 'purchases', 0),
            'total_sold' => $user->getPluginMeta('market', 'total_sold', 0),
        ];
    }
}
