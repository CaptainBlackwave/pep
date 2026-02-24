<?php

namespace App\Plugins\Jail;

use App\Plugins\Plugin;
use App\Core\Contracts\PluginInterface;
use App\Core\Models\User;

/**
 * Jail Plugin
 */
class JailPlugin extends Plugin implements PluginInterface
{
    public function __construct()
    {
        parent::__construct(app_path('Plugins/Jail'));
    }

    public function register(): void {}
    public function boot(): void { $this->registerHooks(); }
    public function install(): void { $this->log('info', 'Jail installed'); }
    public function enable(): void { $this->log('info', 'Jail enabled'); }
    public function disable(): void { $this->log('info', 'Jail disabled'); }
    public function uninstall(): void { \App\Core\Models\PluginMetadata::where('plugin_id', 'jail')->delete(); }
    public function upgrade(string $f, string $t): void {}

    /**
     * Get all jailed users.
     */
    public function getJailedUsers(): array
    {
        return User::where('jail_until', '>', now())
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'username' => $u->username,
                'remaining' => now()->diffInSeconds($u->jail_until),
                'bail' => $this->calculateBail($u),
            ])
            ->toArray();
    }

    /**
     * Attempt to bust someone out of jail.
     */
    public function bustOut($user, int $targetId): array
    {
        $target = User::findOrFail($targetId);

        if (!$target->jail_until || $target->jail_until <= now()) {
            return ['success' => false, 'message' => 'User not in jail'];
        }

        $successRate = 0.3 + (($user->speed ?? 0) / 1000);
        $success = (mt_rand(1, 100) / 100) <= $successRate;

        if ($success) {
            $target->update(['jail_until' => null]);
            broadcastToPluginUser($target->id, 'jail', 'released', ['reason' => 'busted']);

            return ['success' => true, 'message' => "Busted {$target->username} out!"];
        }

        // Failed bust - user goes to jail too
        $user->update(['jail_until' => now()->addMinutes(5)]);

        return ['success' => false, 'message' => 'Caught! You are now in jail.'];
    }

    /**
     * Pay bail for yourself or another.
     */
    public function payBail($user, int $targetId = null): array
    {
        $target = $targetId ? User::findOrFail($targetId) : $user;

        if (!$target->jail_until || $target->jail_until <= now()) {
            return ['success' => false, 'message' => 'Not in jail'];
        }

        $bail = $this->calculateBail($target);

        if (($user->cash ?? 0) < $bail) {
            return ['success' => false, 'message' => 'Not enough cash'];
        }

        $user->decrement('cash', $bail);
        $target->update(['jail_until' => null]);

        broadcastToPluginUser($target->id, 'jail', 'released', ['reason' => 'bail']);

        return ['success' => true, 'message' => "Paid \${$bail} bail"];
    }

    protected function calculateBail($user): int
    {
        $remaining = now()->diffInSeconds($user->jail_until);
        return max(100, $remaining * 2);
    }
}
