<?php

namespace App\Plugins\Crimes\Services;

use App\Core\Facades\Economy;
use App\Core\Services\PluginContext;
use App\Plugins\Crimes\Models\Crime;
use App\Core\Models\User;
use App\Core\Services\TimerService;
use App\Core\Services\NotificationService;
use App\Plugins\Crimes\Models\CrimeAttempt;
use Carbon\Carbon;

class CrimeService
{
    protected $timerService;
    protected $notificationService;

    public function __construct(
        TimerService $timerService,
        NotificationService $notificationService
    ) {
        $this->timerService = $timerService;
        $this->notificationService = $notificationService;
    }

    /**
     * Attempt to commit a crime — uses legacy formula from old system.
     */
    public function attemptCrime(User $player, Crime $crime): array
    {
        // Check if player has an active crime cooldown
        if ($this->timerService->hasActiveTimer($player, 'crime')) {
            $remaining = $this->timerService->getRemainingSeconds($player, 'crime');
            return [
                'success' => false,
                'message' => 'You must wait before committing another crime!',
                'cooldown' => $remaining
            ];
        }

        // Check if player is in jail
        if ($player->jail_until && $player->jail_until->isFuture()) {
            return [
                'success' => false,
                'message' => 'You are in jail!',
                'jail_time' => $player->jail_until->diffInSeconds(now())
            ];
        }

        // Check rank level requirement (level = rank position 1-10)
        if ($player->level < $crime->required_level) {
            $nextRequiredRank = \App\Core\Models\Rank::orderBy('required_exp')
                ->skip($crime->required_level - 1)
                ->first();
            $rankName = $nextRequiredRank?->name ?? "rank {$crime->required_level}";
            return [
                'success' => false,
                'message' => "You need to be at least {$rankName} to commit this crime!"
            ];
        }

        // Check energy requirement
        if ($player->energy < $crime->energy_cost) {
            return [
                'success' => false,
                'message' => "You don't have enough energy! Need {$crime->energy_cost}, you have {$player->energy}."
            ];
        }

        // Deduct energy
        $player->energy -= $crime->energy_cost;

        // Legacy formula: Random chance vs success rate
        $chance = mt_rand(1, 100);
        $jailChance = mt_rand(1, 3);

        // Calculate rewards
        $cashReward = mt_rand($crime->min_cash, $crime->max_cash);

        // LEGACY FORMULA: Failed and caught (sent to jail)
        if ($chance > $crime->success_rate && $jailChance == 1) {
            $jailTime = $crime->id * 15; // 15 seconds per crime level

            $player->update([
                'jail_until' => now()->addSeconds($jailTime),
                'last_crime_at' => now()
            ]);

            CrimeAttempt::create([
                'user_id' => $player->id,
                'crime_id' => $crime->id,
                'success' => false,
                'cash_earned' => 0,
                'respect_earned' => 0,
                'result_message' => 'Caught by police and sent to jail!',
                'attempted_at' => now()
            ]);

            return [
                'success' => false,
                'jailed' => true,
                'message' => "You failed to commit the crime and were caught! Sent to jail for {$jailTime} seconds.",
                'jail_time' => $jailTime
            ];
        }

        // LEGACY FORMULA: Failed but escaped
        if ($chance > $crime->success_rate) {
            $player->update([
                'last_crime_at' => now()
            ]);

            CrimeAttempt::create([
                'user_id' => $player->id,
                'crime_id' => $crime->id,
                'success' => false,
                'cash_earned' => 0,
                'respect_earned' => 0,
                'result_message' => 'Failed but escaped',
                'attempted_at' => now()
            ]);

            return [
                'success' => false,
                'message' => 'You failed to commit the crime but managed to escape!'
            ];
        }

        // SUCCESS — award cash, respect, and XP
        PluginContext::run('crimes', function () use ($player, $cashReward, $crime) {
            Economy::credit($player, $cashReward, "Crime reward: {$crime->name}", 'crimes');
        });
        $player->respect += $crime->respect_reward;
        $player->last_crime_at = now();
        $player->save();

        // Add cumulative XP and check for rank-up (single system)
        $progressResult = $player->addExperience($crime->experience_reward);

        // Set crime cooldown (5 seconds)
        $this->timerService->setTimer($player, 'crime', 5, [
            'crime_id' => $crime->id,
            'crime_name' => $crime->name
        ]);

        CrimeAttempt::create([
            'user_id' => $player->id,
            'crime_id' => $crime->id,
            'success' => true,
            'cash_earned' => $cashReward,
            'respect_earned' => $crime->respect_reward,
            'result_message' => 'Successfully completed!',
            'attempted_at' => now()
        ]);

        $message = "You successfully committed {$crime->name}!";
        $rewards = [
            'cash' => $cashReward,
            'respect' => $crime->respect_reward,
            'experience' => $crime->experience_reward,
            'progress' => $progressResult,
        ];

        if ($progressResult['ranked_up']) {
            $message .= " Rank up! You are now {$player->rank}!";
            $rewards['ranked_up'] = true;
            $rewards['new_rank'] = $player->rank;
        }

        return [
            'success' => true,
            'message' => $message,
            'rewards' => $rewards
        ];
    }

        /**
     * Get crime statistics for a player's profile page.
     */
    public function getPlayerStats(int $userId): array
    {
        $row = CrimeAttempt::where('user_id', $userId)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful, SUM(cash_earned) as total_earnings, SUM(respect_earned) as total_respect')
            ->first();
        return [
            'total_attempts'       => (int) ($row->total ?? 0),
            'successful'           => (int) ($row->successful ?? 0),
            'failed'               => (int) ($row->total ?? 0) - (int) ($row->successful ?? 0),
            'total_earnings'       => (int) ($row->total_earnings ?? 0),
            'total_respect_earned' => (int) ($row->total_respect ?? 0),
        ];
    }

    /**
     * Get recent crime attempts for a player's profile page.
     */
    public function getRecentAttempts(int $userId, int $limit = 10): \Illuminate\Support\Collection
    {
        return CrimeAttempt::where('user_id', $userId)
            ->with('crime:id,name')
            ->orderBy('attempted_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get available crimes for player (gated by rank level).
     */
    public function getAvailableCrimes(User $player): array
    {
        return Crime::where('active', true)
            ->where('required_level', '<=', $player->level)
            ->orderBy('required_level')
            ->get()
            ->toArray();
    }
}
