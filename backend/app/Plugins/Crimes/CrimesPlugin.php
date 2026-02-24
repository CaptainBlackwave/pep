<?php

namespace App\Plugins\Crimes;

use App\Plugins\Plugin;
use App\Core\Contracts\PluginInterface;
use App\Plugins\Crimes\Models\Crime;
use App\Plugins\Crimes\Models\CrimeAttempt;
use Illuminate\Support\Facades\DB;

/**
 * Crimes Plugin
 *
 * Reference implementation of the new plugin architecture.
 * Allows players to commit crimes for cash and experience.
 */
class CrimesPlugin extends Plugin implements PluginInterface
{
    /**
     * Plugin constructor.
     */
    public function __construct()
    {
        parent::__construct(app_path('Plugins/Crimes'));
    }

    // ==========================================
    // PluginInterface Implementation
    // ==========================================

    /**
     * Register the plugin's services.
     */
    public function register(): void
    {
        // Register crime service
        $this->app->singleton('crimes.service', function ($app) {
            return new Services\CrimeService();
        });
    }

    /**
     * Boot the plugin's functionality.
     */
    public function boot(): void
    {
        $this->registerHooks();
    }

    // ==========================================
    // PluginLifecycleInterface Implementation
    // ==========================================

    /**
     * Called when plugin is first installed.
     */
    public function install(): void
    {
        $this->log('info', 'Crimes plugin installed');
    }

    /**
     * Called when plugin is enabled.
     */
    public function enable(): void
    {
        $this->log('info', 'Crimes plugin enabled');
    }

    /**
     * Called when plugin is disabled.
     */
    public function disable(): void
    {
        $this->log('info', 'Crimes plugin disabled');
    }

    /**
     * Called when plugin is uninstalled.
     */
    public function uninstall(): void
    {
        // Clean up plugin metadata
        \App\Core\Models\PluginMetadata::where('plugin_id', 'crimes')->delete();
        $this->log('info', 'Crimes plugin uninstalled');
    }

    /**
     * Called when upgrading versions.
     */
    public function upgrade(string $fromVersion, string $toVersion): void
    {
        $this->log('info', "Crimes upgraded from {$fromVersion} to {$toVersion}");
    }

    // ==========================================
    // Crime Methods
    // ==========================================

    /**
     * Get available crimes for a user.
     */
    public function getAvailableCrimes($user): array
    {
        return Crime::where('active', true)
            ->where('required_level', '<=', $user->level ?? 1)
            ->orderBy('difficulty')
            ->get()
            ->map(function ($crime) use ($user) {
                $successRate = $this->calculateSuccessRate($crime, $user);
                return array_merge($crime->toArray(), [
                    'success_rate' => $successRate,
                    'can_attempt' => $this->canAttempt($user, $crime),
                ]);
            })
            ->toArray();
    }

    /**
     * Attempt to commit a crime.
     */
    public function attemptCrime($user, int $crimeId): array
    {
        $crime = Crime::findOrFail($crimeId);

        // Validate attempt
        if (!$this->canAttempt($user, $crime)) {
            return ['success' => false, 'message' => 'Cannot attempt this crime'];
        }

        // Check cooldown via timer
        $timer = $user->getTimer('crime');
        if ($timer) {
            $remaining = now()->diffInSeconds($timer);
            if ($remaining > 0) {
                return [
                    'success' => false,
                    'message' => "Wait {$remaining} seconds before next crime",
                    'cooldown' => $remaining,
                ];
            }
        }

        // Check energy
        if (($user->energy ?? 0) < $crime->energy_cost) {
            return ['success' => false, 'message' => 'Not enough energy'];
        }

        // Calculate success
        $successRate = $this->calculateSuccessRate($crime, $user);
        $roll = mt_rand(1, 100) / 100;
        $success = $roll <= $successRate;

        // Begin transaction
        return DB::transaction(function () use ($user, $crime, $success) {
            $result = [];

            if ($success) {
                $cashEarned = mt_rand($crime->min_cash, $crime->max_cash);
                $expEarned = $crime->experience_reward;

                // Award via economy contract
                $economy = app(\App\Core\Contracts\EconomyInterface::class);
                $economy->credit($user, $cashEarned, "Crime: {$crime->name}", 'crimes');

                // Add experience via plugin metadata
                $currentXp = $user->getPluginMeta('crimes', 'experience', 0);
                $user->setPluginMeta('crimes', 'experience', $currentXp + $expEarned);

                // Track attempt
                CrimeAttempt::create([
                    'user_id' => $user->id,
                    'crime_id' => $crime->id,
                    'success' => true,
                    'cash_earned' => $cashEarned,
                    'exp_earned' => $expEarned,
                    'jailed' => false,
                ]);

                // Update stats
                $user->incrementPluginMeta('crimes', 'total_crimes', 1);
                $user->incrementPluginMeta('crimes', 'successful_crimes', 1);

                $result = [
                    'success' => true,
                    'message' => "Success! Earned \${$cashEarned} and {$expEarned} XP",
                    'cash_earned' => $cashEarned,
                    'exp_earned' => $expEarned,
                ];

                // Broadcast
                broadcastToPluginUser($user->id, 'crimes', 'crime_completed', [
                    'crime_id' => $crime->id,
                    'success' => true,
                    'cash_earned' => $cashEarned,
                ]);

            } else {
                // Check jail chance
                $jailChance = $crime->jail_chance ?? 0.3;
                $jailed = (mt_rand(1, 100) / 100) <= $jailChance;

                if ($jailed) {
                    $jailTime = mt_rand(60, 300);
                    $user->update(['jail_until' => now()->addSeconds($jailTime)]);

                    CrimeAttempt::create([
                        'user_id' => $user->id,
                        'crime_id' => $crime->id,
                        'success' => false,
                        'jailed' => true,
                        'jail_time' => $jailTime,
                    ]);

                    $result = [
                        'success' => false,
                        'message' => "Caught! Jailed for {$jailTime} seconds",
                        'jailed' => true,
                        'jail_time' => $jailTime,
                    ];
                } else {
                    CrimeAttempt::create([
                        'user_id' => $user->id,
                        'crime_id' => $crime->id,
                        'success' => false,
                        'jailed' => false,
                    ]);

                    $result = [
                        'success' => false,
                        'message' => 'Failed but escaped!',
                    ];
                }

                $user->incrementPluginMeta('crimes', 'total_crimes', 1);
            }

            // Deduct energy and set cooldown
            $user->update(['energy' => max(0, ($user->energy ?? 0) - $crime->energy_cost)]);
            $user->setTimer('crime', now()->addSeconds($crime->cooldown ?? 60));

            // Check achievements via hook
            \App\Facades\Hook::fire('crime.attempted', [
                'user' => $user,
                'crime' => $crime,
                'success' => $success,
            ]);

            return $result;
        });
    }

    /**
     * Check if user can attempt a crime.
     */
    protected function canAttempt($user, Crime $crime): bool
    {
        if (($user->level ?? 1) < $crime->required_level) {
            return false;
        }

        if ($user->isInJail && $user->isInJail()) {
            return false;
        }

        return true;
    }

    /**
     * Calculate success rate for a crime.
     */
    protected function calculateSuccessRate(Crime $crime, $user): float
    {
        $baseRate = $crime->success_rate / 100;

        // Level bonus
        $levelDiff = ($user->level ?? 1) - $crime->required_level;
        $levelBonus = $levelDiff * 0.02;

        // Stat bonus
        $statBonus = (($user->strength ?? 0) + ($user->speed ?? 0)) / 2000;

        $finalRate = $baseRate + $levelBonus + $statBonus;

        // Cap between 5% and 95%
        return max(0.05, min(0.95, $finalRate));
    }

    /**
     * Get crime statistics for user.
     */
    public function getStats($user): array
    {
        return [
            'total_crimes' => $user->getPluginMeta('crimes', 'total_crimes', 0),
            'successful_crimes' => $user->getPluginMeta('crimes', 'successful_crimes', 0),
            'experience' => $user->getPluginMeta('crimes', 'experience', 0),
            'times_jailed' => CrimeAttempt::where('user_id', $user->id)->where('jailed', true)->count(),
        ];
    }
}
