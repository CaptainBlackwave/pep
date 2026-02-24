<?php

namespace App\Plugins\Chat;

use App\Plugins\Plugin;
use App\Core\Contracts\PluginInterface;

class ChatPlugin extends Plugin implements PluginInterface
{
    public function __construct() { parent::__construct(app_path('Plugins/Chat')); }
    public function register(): void {}
    public function boot(): void { $this->registerHooks(); }
    public function install(): void { $this->log('info', 'Chat installed'); }
    public function enable(): void { $this->log('info', 'Chat enabled'); }
    public function disable(): void { $this->log('info', 'Chat disabled'); }
    public function uninstall(): void { \App\Core\Models\PluginMetadata::where('plugin_id', 'chat')->delete(); }
    public function upgrade(string $f, string $t): void {}

    public function getStats($user): array
    {
        return [
            'messages' => $user->getPluginMeta('chat', 'message_count', 0),
            'channels' => $user->getPluginMeta('chat', 'channels', []),
        ];
    }
}
