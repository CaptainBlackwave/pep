<?php

namespace App\Core\Services;

use Closure;
use Illuminate\Support\Facades\Log;

class GameHooks
{
    protected static array $listeners = [];

    public static function listen(string $hookName, Closure $callback, int $priority = 10): void
    {
        if (!isset(static::$listeners[$hookName])) {
            static::$listeners[$hookName] = [];
        }
        static::$listeners[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];

        usort(static::$listeners[$hookName], fn($a, $b) => $b['priority'] <=> $a['priority']);
    }

    public static function apply(string $hookName, mixed $data = null): mixed
    {
        if (!isset(static::$listeners[$hookName])) {
            return $data;
        }

        $result = $data;

        foreach (static::$listeners[$hookName] as $listener) {
            try {
                $callback = $listener['callback'];
                $result = $callback($result);
            } catch (\Throwable $e) {
                Log::error("GameHooks: Hook '{$hookName}' listener failed: " . $e->getMessage(), [
                    'exception' => $e,
                ]);
            }
        }

        return $result;
    }

    public static function hasListeners(string $hookName): bool
    {
        return isset(static::$listeners[$hookName]) && count(static::$listeners[$hookName]) > 0;
    }

    public static function clearListeners(?string $hookName = null): void
    {
        if ($hookName === null) {
            static::$listeners = [];
        } else {
            unset(static::$listeners[$hookName]);
        }
    }
}
