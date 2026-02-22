<?php

namespace App\Plugins\Progression\Models;

use App\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Rank model — owns the ranks table and all experience-threshold logic.
 *
 * Moved from App\Core\Models\Rank as part of the Core/Plugin separation.
 * App\Core\Models\Rank is a thin shim that extends this class for
 * backward compatibility with existing imports.
 */
class Rank extends Model
{
    protected $fillable = [
        'name',
        'required_exp',
        'max_health',
        'cash_reward',
        'bullet_reward',
        'user_limit',
    ];

    protected $casts = [
        'required_exp'  => 'integer',
        'max_health'    => 'integer',
        'cash_reward'   => 'integer',
        'bullet_reward' => 'integer',
        'user_limit'    => 'integer',
    ];

    /**
     * Get users at this rank.
     * After Phase 6 the authoritative `rank_id` lives on `player_profiles`.
     * Use a hasManyThrough to fetch `User` models via `player_profiles`.
     */
    public function users()
    {
        return $this->hasManyThrough(
            User::class,
            \App\Core\Models\PlayerProfile::class,
            'rank_id',   // Foreign key on player_profiles referencing this rank
            'id',        // Foreign key on users (primary key) referenced by player_profiles.user_id
            'id',        // Local key on ranks
            'user_id'    // Local key on player_profiles referencing users
        );
    }

    /**
     * Get the next rank above the given experience value.
     */
    public static function getNextRank(int $currentExp): ?static
    {
        return static::where('required_exp', '>', $currentExp)
            ->orderBy('required_exp', 'asc')
            ->first();
    }

    /**
     * Get the appropriate rank for a given total experience amount.
     */
    public static function getRankByExperience(int $exp): ?static
    {
        return static::where('required_exp', '<=', $exp)
            ->orderBy('required_exp', 'desc')
            ->first();
    }
}
