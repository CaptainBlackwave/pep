<?php

namespace App\Plugins\Forum;

use App\Plugins\Plugin;
use App\Core\Contracts\PluginInterface;

class ForumPlugin extends Plugin implements PluginInterface
{
    public function __construct() { parent::__construct(app_path('Plugins/Forum')); }
    public function register(): void {}
    public function boot(): void { $this->registerHooks(); }
    public function install(): void { $this->log('info', 'Forum installed'); }
    public function enable(): void { $this->log('info', 'Forum enabled'); }
    public function disable(): void { $this->log('info', 'Forum disabled'); }
    public function uninstall(): void { \App\Core\Models\PluginMetadata::where('plugin_id', 'forum')->delete(); }
    public function upgrade(string $f, string $t): void {}

    public function getStats($user): array
    {
        return [
            'posts' => $user->getPluginMeta('forum', 'post_count', 0),
            'threads' => $user->getPluginMeta('forum', 'thread_count', 0),
        ];
    }
}
