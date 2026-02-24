<?php

namespace App\Plugins\MiniRpg;

use App\Plugins\Plugin;
use App\Core\Contracts\PluginInterface;
use App\Core\Traits\HasPluginMetadata;
use Illuminate\Support\Facades\Route;

/**
 * Mini RPG Plugin
 *
 * A complete reference implementation demonstrating the plugin architecture:
 * - PluginInterface implementation
 * - HasPluginMetadata trait usage
 * - Hook registration
 * - WebSocket broadcasting
 * - Frontend component slots
 */
class MiniRpgPlugin extends Plugin implements PluginInterface
{
    /**
     * Plugin constructor.
     */
    public function __construct()
    {
        parent::__construct(app_path('Plugins/MiniRpg'));
    }

    // ==========================================
    // PluginInterface Implementation
    // ==========================================

    /**
     * Register the plugin's services.
     * Called during Laravel's "register" phase.
     */
    public function register(): void
    {
        // Register any services or bindings
        $this->app->singleton('minirpg.stats', function ($app) {
            return new Services\RpgStatsService();
        });

        $this->app->singleton('minirpg.combat', function ($app) {
            return new Services\CombatService();
        });
    }

    /**
     * Boot the plugin's functionality.
     * Called during Laravel's "boot" phase.
     */
    public function boot(): void
    {
        // Register hooks from hooks.php
        $this->registerHooks();

        // Register any event listeners
        $this->registerEventListeners();
    }

    // ==========================================
    // PluginLifecycleInterface Implementation
    // ==========================================

    /**
     * Called when plugin is first installed.
     */
    public function install(): void
    {
        $this->log('info', 'Mini RPG plugin installed');
    }

    /**
     * Called when plugin is enabled.
     */
    public function enable(): void
    {
        $this->log('info', 'Mini RPG plugin enabled');
    }

    /**
     * Called when plugin is disabled.
     */
    public function disable(): void
    {
        $this->log('info', 'Mini RPG plugin disabled');
    }

    /**
     * Called when plugin is uninstalled.
     */
    public function uninstall(): void
    {
        // Clean up plugin metadata for all users
        // This demonstrates the metadata pattern
        $this->log('info', 'Mini RPG plugin uninstalled');
    }

    /**
     * Called when upgrading versions.
     */
    public function upgrade(string $fromVersion, string $toVersion): void
    {
        $this->log('info', "Mini RPG upgraded from {$fromVersion} to {$toVersion}");
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        // Listen for user creation and initialize RPG data
        \App\Core\Models\User::created(function ($user) {
            $this->initializeUserRpgData($user);
        });
    }

    /**
     * Initialize RPG data for a new user using metadata trait.
     */
    protected function initializeUserRpgData($user): void
    {
        // Use the HasPluginMetadata trait methods
        $user->setManyPluginMeta('mini-rpg', [
            'gold' => 100,           // Starting gold
            'level' => 1,            // Starting level
            'experience' => 0,       // Starting XP
            'health' => 100,         // Max health
            'max_health' => 100,     // Health cap
            'attack' => 10,          // Base attack
            'defense' => 5,          // Base defense
            'kills' => 0,            // Combat kills
            'deaths' => 0,           // Combat deaths
        ]);

        $this->log('info', 'Initialized RPG data for new user', ['user_id' => $user->id]);
    }

    /**
     * Get RPG data for a user.
     */
    public function getUserRpgData($user): array
    {
        return $user->getAllPluginMeta('mini-rpg');
    }

    /**
     * Add gold to a user.
     */
    public function addGold($user, int $amount): int
    {
        $newGold = $user->incrementPluginMeta('mini-rpg', 'gold', $amount);

        // Broadcast the update via WebSocket
        broadcastToPluginUser($user->id, 'mini-rpg', 'gold_updated', [
            'gold' => $newGold,
            'change' => $amount,
        ]);

        return $newGold;
    }

    /**
     * Remove gold from a user.
     */
    public function removeGold($user, int $amount): int
    {
        $currentGold = $user->getPluginMeta('mini-rpg', 'gold', 0);
        $newGold = max(0, $currentGold - $amount);

        $user->setPluginMeta('mini-rpg', 'gold', $newGold);

        broadcastToPluginUser($user->id, 'mini-rpg', 'gold_updated', [
            'gold' => $newGold,
            'change' => -$amount,
        ]);

        return $newGold;
    }

    /**
     * Add experience to a user and handle leveling.
     */
    public function addExperience($user, int $amount): array
    {
        $currentXp = $user->getPluginMeta('mini-rpg', 'experience', 0);
        $currentLevel = $user->getPluginMeta('mini-rpg', 'level', 1);

        $newXp = $currentXp + $amount;
        $newLevel = $currentLevel;
        $leveledUp = false;

        // Simple leveling formula: level * 100 XP required
        $xpRequired = $currentLevel * 100;

        while ($newXp >= $xpRequired) {
            $newXp -= $xpRequired;
            $newLevel++;
            $leveledUp = true;
            $xpRequired = $newLevel * 100;
        }

        $user->setManyPluginMeta('mini-rpg', [
            'experience' => $newXp,
            'level' => $newLevel,
        ]);

        if ($leveledUp) {
            // Increase stats on level up
            $user->incrementPluginMeta('mini-rpg', 'max_health', 10);
            $user->incrementPluginMeta('mini-rpg', 'attack', 2);
            $user->incrementPluginMeta('mini-rpg', 'defense', 1);

            broadcastToPluginUser($user->id, 'mini-rpg', 'level_up', [
                'level' => $newLevel,
                'attack' => $user->getPluginMeta('mini-rpg', 'attack'),
                'defense' => $user->getPluginMeta('mini-rpg', 'defense'),
                'max_health' => $user->getPluginMeta('mini-rpg', 'max_health'),
            ]);
        }

        return [
            'experience' => $newXp,
            'level' => $newLevel,
            'leveled_up' => $leveledUp,
        ];
    }
}
