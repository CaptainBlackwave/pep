<?php

namespace App\Plugins\Progression\Services;

use App\Core\Models\PlayerProfile;
use App\Core\Models\User;
use App\Plugins\Progression\Models\Rank;

/**
 * Owns all experience and rank-up logic.
 *
 * Previously this logic lived inline in User::checkRank() and
 * User::addExperience(). Those methods now delegate here so the
 * Progression plugin is the single source of truth for game progression.
 *
 * Callers continue using $user->addExperience() and $user->checkRank()
 * — the User model shims are preserved for backward compatibility.
 */
class ProgressionService
{
    /**
     * Check whether the user has earned enough experience to rank up,
     * and apply the new rank if so.
     *
     * Reads/writes game stats via the User getter/setter shims so all
     * changes land in player_profiles (Phase 1).
     *
     * @return bool True if the user's rank changed.
     */
    public function checkRank(User $user): bool
    {
        // Get the user's current experience (safe fallback to 0)
        $currentXp = $user->experience ?? 0;

        // Get the rank this user should currently hold
        $correctRank = Rank::where('required_exp', '<=', $currentXp)
            ->orderBy('required_exp', 'desc')
            ->first();

        if (! $correctRank || $correctRank->id === $user->rank_id) {
            return false;
        }

        // Check rank user-limit against player_profiles (authoritative after Phase 1)
        if ($correctRank->user_limit > 0) {
            $usersAtRank = PlayerProfile::where('rank_id', $correctRank->id)->count();
            if ($usersAtRank >= $correctRank->user_limit) {
                return false;
            }
        }

        $oldRankName = $user->rank;

        // Determine rank level (1-based position ordered by required_exp)
        $rankPosition = Rank::where('required_exp', '<=', $correctRank->required_exp)->count();

        // Apply new rank via shims → player_profiles
        $user->rank_id    = $correctRank->id;
        $user->rank       = $correctRank->name;
        $user->level      = $rankPosition;
        $user->max_health = $correctRank->max_health;

        // Award rank-up rewards (only when actively promoting)
        if ($oldRankName && $oldRankName !== $correctRank->name) {
            $user->cash    += $correctRank->cash_reward;
            $user->bullets += $correctRank->bullet_reward;
            $user->health   = $correctRank->max_health; // full heal on rank-up

            $user->notifications()->create([
                'type'    => 'rank_up',
                'title'   => 'Rank Up!',
                'message' => "You have ranked up to {$correctRank->name}! "
                           . "You received \${$correctRank->cash_reward} and {$correctRank->bullet_reward} bullets.",
            ]);
        }

        $user->save();
        return true;
    }

    /**
     * Return the next rank above the user's current experience.
     * Used by the User::macro('getNextRankAttribute') registered in hooks.php.
     */
    public function getNextRank(User $user): ?Rank
    {
        return Rank::where('required_exp', '>', $user->profile?->experience ?? 0)
            ->orderBy('required_exp', 'asc')
            ->first();
    }

    /**
     * Return the experience progress percentage toward the next rank.
     * Used by the User::macro('getExpProgressAttribute') registered in hooks.php.
     */
    public function getExpProgress(User $user): float
    {
        $currentRank = Rank::getRankByExperience($user->profile?->experience ?? 0);
        $nextRank    = $this->getNextRank($user);

        if (!$nextRank || !$currentRank) {
            return 100.0;
        }

        $experience      = $user->profile?->experience ?? 0;
        $xpIntoRank      = $experience - $currentRank->required_exp;
        $xpNeededForNext = $nextRank->required_exp - $currentRank->required_exp;

        if ($xpNeededForNext <= 0) {
            return 100.0;
        }

        return min(100.0, round(($xpIntoRank / $xpNeededForNext) * 100, 2));
    }

    /**
     * Add cumulative experience to the user and trigger a rank-up check.
     *
     * @return array {xp_gained, total_xp, ranked_up, current_rank, old_rank, level, next_rank, exp_progress}
     */
    public function addExperience(User $user, int $amount): array
    {
        $oldRank = $user->rank;

        $user->experience += $amount;
        $user->save();

        $rankedUp = $this->checkRank($user);

        $nextRank    = Rank::getNextRank($user->experience);
        $currentRank = Rank::getRankByExperience($user->experience);

        $expProgress = 100.0;
        if ($nextRank && $currentRank) {
            $xpIntoRank      = $user->experience - $currentRank->required_exp;
            $xpNeededForNext = $nextRank->required_exp - $currentRank->required_exp;
            if ($xpNeededForNext > 0) {
                $expProgress = min(100.0, round(($xpIntoRank / $xpNeededForNext) * 100, 2));
            }
        }

        return [
            'xp_gained'    => $amount,
            'total_xp'     => $user->experience,
            'ranked_up'    => $rankedUp,
            'current_rank' => $user->rank,
            'old_rank'     => $oldRank,
            'level'        => $user->level,
            'next_rank'    => $nextRank?->name,
            'exp_progress' => $expProgress,
        ];
    }
}
