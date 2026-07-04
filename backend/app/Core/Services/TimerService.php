<?php

namespace App\Core\Services;

use Illuminate\Support\Facades\Cache;

class TimerService
{
    protected const CACHE_PREFIX = 'timer_';

    protected function cacheKey($player, string $key): string
    {
        $userId = is_object($player) ? $player->id : $player;
        return static::CACHE_PREFIX . $userId . '_' . $key;
    }

    public function setTimer($player, string $key, int $seconds): void
    {
        $expiresAt = now()->addSeconds($seconds);
        Cache::put($this->cacheKey($player, $key), $expiresAt, $seconds);
    }

    public function hasActiveTimer($player, string $key): bool
    {
        $expiresAt = Cache::get($this->cacheKey($player, $key));
        return $expiresAt !== null && now()->lessThan($expiresAt);
    }

    public function getRemainingSeconds($player, string $key): int
    {
        $expiresAt = Cache::get($this->cacheKey($player, $key));
        if ($expiresAt === null) {
            return 0;
        }
        $remaining = now()->diffInRealSeconds($expiresAt, false);
        return max(0, (int) ceil($remaining));
    }

    public function getActiveTimers($player): array
    {
        $userId = is_object($player) ? $player->id : $player;
        $prefix = static::CACHE_PREFIX . $userId . '_';
        $timers = [];

        // Cache doesn't support prefix scanning natively, so we use the
        // database cache store's tag approach or just return empty if not available.
        // For local dev, we rely on the individual timer checks.
        return $timers;
    }

    public function clearTimer($player, string $key): void
    {
        Cache::forget($this->cacheKey($player, $key));
    }
}
