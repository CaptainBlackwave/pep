<?php

namespace App\Core\Services;

/**
 * Lightweight feature-availability helper.
 *
 * Allows Core code to check whether an optional plugin service is
 * available without importing any plugin class directly.
 *
 * Usage:
 *   if (Feature::available('inventory')) {
 *       app('inventory')->give($user, $itemId);
 *   }
 *
 * Plugin service providers (hooks.php) register their bindings so that
 * Feature::available() returns true when the plugin is enabled.
 */
class Feature
{
    /**
     * Check whether a named service is bound in the container.
     * Returns true only when the plugin is enabled and has registered its binding.
     */
    public static function available(string $name): bool
    {
        return app()->bound($name);
    }

    /**
     * Resolve a named service if available, or return null.
     * Avoids hard coupling to concrete plugin classes.
     */
    public static function resolve(string $name): mixed
    {
        return static::available($name) ? app($name) : null;
    }
}
