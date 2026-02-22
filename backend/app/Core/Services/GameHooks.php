<?php

namespace App\Core\Services;

use Illuminate\Support\Facades\Log;

class GameHooks
{
    protected static array $listeners = [];

    /**
     * Register a listener for a hook.
     * In non-production: logs a warning if the hook name is not registered in HookRegistry,
     * which typically indicates a typo or an unregistered custom hook.
     */
    public static function listen(string $hook, callable $callback): void
    {
        if (!app()->isProduction() && !HookRegistry::isDefined($hook)) {
            Log::debug("GameHooks: listener registered for undefined hook '{$hook}'. Register it with HookRegistry::define() or GameHooks::define().");
        }

        self::$listeners[$hook][] = $callback;
    }

    /**
     * Apply all listeners for a hook to a value.
     */
    public static function apply(string $hook, $value)
    {
        // Non-production: validate payload schema and warn on deprecated hooks
        if (!app()->isProduction() && HookRegistry::isDefined($hook)) {
            $definition = HookRegistry::get($hook);

            if (($definition['stability'] ?? 'stable') === 'deprecated') {
                Log::warning("GameHooks: deprecated hook '{$hook}' was applied. Migrate to the replacement.");
            }

            $errors = HookRegistry::validatePayload($hook, $value);
            if (!empty($errors)) {
                Log::warning("GameHooks: payload validation failed for hook '{$hook}'", ['errors' => $errors]);
            }
        }

        if (!isset(self::$listeners[$hook])) {
            return $value;
        }

        foreach (self::$listeners[$hook] as $callback) {
            try {
                $value = $callback($value);
            } catch (\Throwable $e) {
                // Isolate plugin hook failures — log and continue with last good value
                Log::error("GameHooks: hook '{$hook}' callback threw an exception", [
                    'exception' => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ]);
            }
        }

        return $value;
    }

    /**
     * Define a new hook — delegates to HookRegistry for schema storage.
     */
    public static function define(string $hook, array $schema = [], string $version = '1.0', string $stability = 'stable'): void
    {
        HookRegistry::define($hook, $schema, $version, $stability);
    }
}
