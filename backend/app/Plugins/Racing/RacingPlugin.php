<?php
namespace App\Plugins\Racing;
use App\Plugins\Plugin;
use App\Core\Contracts\PluginInterface;
class RacingPlugin extends Plugin implements PluginInterface {
    public function __construct() { parent::__construct(app_path('Plugins/Racing')); }
    public function register(): void {}
    public function boot(): void { $this->registerHooks(); }
    public function install(): void {}
    public function enable(): void {}
    public function disable(): void {}
    public function uninstall(): void { \App\Core\Models\PluginMetadata::where('plugin_id', 'racing')->delete(); }
    public function upgrade(string $f, string $t): void {}
}
