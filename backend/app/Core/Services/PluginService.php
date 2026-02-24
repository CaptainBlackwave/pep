<?php

namespace App\Core\Services;

use App\Core\Models\Plugin;
use App\Core\Models\User;
use Illuminate\Support\Collection;

class PluginService
{
    public function getEnabledPlugins(): Collection
    {
        return Plugin::where('enabled', true)
            ->orderBy('order')
            ->get();
    }

    public function getPluginsForPlayer(User $player): Collection
    {
        return Plugin::where('enabled', true)
            ->where('required_level', '<=', $player->level ?? 1)
            ->orderBy('order')
            ->get();
    }

    /**
     * Get navigation items for dashboard
     */
    public function getNavigationItems(User $player): Collection
    {
        return $this->getPluginsForPlayer($player)
            ->filter(fn($plugin) => $this->hasNavigationConfig($plugin))
            ->map(function ($plugin) {
                // Get navigation config - first check navigation_config, then fall back to settings.menu
                $config = $this->getNavigationConfig($plugin);

                return [
                    'name' => $plugin->name,
                    'display_name' => $plugin->display_name,
                    'description' => $plugin->description,
                    'icon' => $plugin->icon,
                    'route_name' => $plugin->route_name,
                    'route_url' => $plugin->route_name ? route($plugin->route_name) : null,
                    'color' => $config['color'] ?? 'bg-gray-600',
                    'order' => $config['order'] ?? $plugin->order ?? 100,
                    'section' => $config['section'] ?? 'main',
                    'icon_svg' => $config['icon_svg'] ?? null,
                ];
            })
            ->groupBy('section');
    }

    /**
     * Check if a plugin has navigation configuration
     */
    protected function hasNavigationConfig($plugin): bool
    {
        // Check direct navigation_config field
        if (!empty($plugin->navigation_config)) {
            return true;
        }

        // Check if plugin has settings with menu configuration
        $settings = $plugin->settings ?? [];
        if (is_string($settings)) {
            $settings = json_decode($settings, true);
        }

        if (!empty($settings['menu']['enabled'])) {
            return true;
        }

        return false;
    }

    /**
     * Get navigation configuration from plugin
     */
    protected function getNavigationConfig($plugin): array
    {
        // First check direct navigation_config field
        if (!empty($plugin->navigation_config)) {
            return is_array($plugin->navigation_config)
                ? $plugin->navigation_config
                : json_decode($plugin->navigation_config, true);
        }

        // Fall back to settings.menu
        $settings = $plugin->settings ?? [];
        if (is_string($settings)) {
            $settings = json_decode($settings, true);
        }

        $menuSettings = $settings['menu'] ?? [];

        if ($menuSettings) {
            return [
                'section' => $menuSettings['section'] ?? 'main',
                'order' => $menuSettings['order'] ?? $plugin->order ?? 100,
                'color' => $settings['color'] ?? 'bg-gray-600',
                'icon' => $settings['icon'] ?? null,
                'enabled' => $menuSettings['enabled'] ?? true,
            ];
        }

        return [];
    }

    public function isPluginEnabled(string $pluginName): bool
    {
        $plugin = Plugin::where('name', $pluginName)->first();
        return $plugin ? $plugin->enabled : false;
    }

    public function canPlayerAccessPlugin(User $player, string $pluginName): bool
    {
        $plugin = Plugin::where('name', $pluginName)->first();

        if (!$plugin || !$plugin->enabled) {
            return false;
        }

        return $player->level >= $plugin->required_level;
    }

    public function togglePlugin(string $pluginName): bool
    {
        $plugin = Plugin::where('name', $pluginName)->first();

        if ($plugin) {
            $plugin->enabled = !$plugin->enabled;
            $plugin->save();
            return $plugin->enabled;
        }

        return false;
    }

    public function updatePluginSettings(string $pluginName, array $settings): bool
    {
        $plugin = Plugin::where('name', $pluginName)->first();

        if ($plugin) {
            $plugin->settings = array_merge($plugin->settings ?? [], $settings);
            $plugin->save();
            return true;
        }

        return false;
    }

    public function reorderPlugins(array $order): void
    {
        foreach ($order as $pluginName => $position) {
            Plugin::where('name', $pluginName)->update(['order' => $position]);
        }
    }

    // Backwards compatibility aliases
    public function getEnabledModules(): Collection
    {
        return $this->getEnabledPlugins();
    }

    public function isModuleEnabled(string $name): bool
    {
        return $this->isPluginEnabled($name);
    }

    public function canPlayerAccessModule(User $player, string $name): bool
    {
        return $this->canPlayerAccessPlugin($player, $name);
    }
}
