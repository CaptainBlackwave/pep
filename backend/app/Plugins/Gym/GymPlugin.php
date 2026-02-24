<?php

namespace App\Plugins\Gym;

use App\Plugins\Plugin;
use App\Core\Contracts\PluginInterface;

/**
 * Gym Plugin
 *
 * Train character attributes (strength, speed, defense).
 */
class GymPlugin extends Plugin implements PluginInterface
{
    protected array $statTypes = ['strength', 'speed', 'defense'];

    public function __construct()
    {
        parent::__construct(app_path('Plugins/Gym'));
    }

    public function register(): void
    {
        $this->app->singleton('gym.service', fn() => new Services\GymService());
    }

    public function boot(): void
    {
        $this->registerHooks();
    }

    public function install(): void
    {
        $this->log('info', 'Gym plugin installed');
    }

    public function enable(): void
    {
        $this->log('info', 'Gym plugin enabled');
    }

    public function disable(): void
    {
        $this->log('info', 'Gym plugin disabled');
    }

    public function uninstall(): void
    {
        \App\Core\Models\PluginMetadata::where('plugin_id', 'gym')->delete();
        $this->log('info', 'Gym plugin uninstalled');
    }

    public function upgrade(string $from, string $to): void
    {
        $this->log('info', "Gym upgraded from {$from} to {$to}");
    }

    /**
     * Get available training options.
     */
    public function getTrainingOptions($user): array
    {
        $energyCost = 10;
        $baseGain = 1;

        return array_map(function ($stat) use ($user, $energyCost, $baseGain) {
            $current = $user->getPluginMeta('gym', $stat, $user->$stat ?? 0);
            $levelBonus = floor(($user->level ?? 1) / 5);
            $gain = $baseGain + $levelBonus;

            return [
                'stat' => $stat,
                'current' => $current,
                'gain' => $gain,
                'energy_cost' => $energyCost,
                'can_train' => ($user->energy ?? 0) >= $energyCost,
            ];
        }, $this->statTypes);
    }

    /**
     * Train a stat.
     */
    public function train($user, string $stat): array
    {
        if (!in_array($stat, $this->statTypes)) {
            return ['success' => false, 'message' => 'Invalid stat'];
        }

        $energyCost = 10;
        if (($user->energy ?? 0) < $energyCost) {
            return ['success' => false, 'message' => 'Not enough energy'];
        }

        // Check cooldown
        $timer = $user->getTimer('gym');
        if ($timer && now()->diffInSeconds($timer) < 30) {
            return ['success' => false, 'message' => 'Training cooldown active'];
        }

        $baseGain = 1;
        $levelBonus = floor(($user->level ?? 1) / 5);
        $gain = $baseGain + $levelBonus;

        $current = $user->getPluginMeta('gym', $stat, $user->$stat ?? 0);
        $newValue = $current + $gain;
        $user->setPluginMeta('gym', $stat, $newValue);

        // Deduct energy
        $user->update(['energy' => max(0, ($user->energy ?? 0) - $energyCost)]);
        $user->setTimer('gym', now());

        // Track total training
        $user->incrementPluginMeta('gym', 'total_training', 1);

        // Broadcast
        broadcastToPluginUser($user->id, 'gym', 'stat_trained', [
            'stat' => $stat,
            'value' => $newValue,
            'gain' => $gain,
        ]);

        return [
            'success' => true,
            'message' => "Trained {$stat} +{$gain}",
            'stat' => $stat,
            'new_value' => $newValue,
            'gain' => $gain,
        ];
    }

    /**
     * Get user's gym stats.
     */
    public function getStats($user): array
    {
        return [
            'strength' => $user->getPluginMeta('gym', 'strength', $user->strength ?? 0),
            'speed' => $user->getPluginMeta('gym', 'speed', $user->speed ?? 0),
            'defense' => $user->getPluginMeta('gym', 'defense', $user->defense ?? 0),
            'total_training' => $user->getPluginMeta('gym', 'total_training', 0),
        ];
    }
}
