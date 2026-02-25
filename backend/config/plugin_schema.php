<?php
// config/plugin_schema.php

return [
    /*
    |--------------------------------------------------------------------------
    | Required Plugin Fields
    |--------------------------------------------------------------------------
    |
    | These fields must be present in every plugin.json file.
    | The plugin will fail validation if any are missing.
    |
    */
    'required' => [
        'name',
        'slug',
        'version',
        'author',
        'description',
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional Plugin Fields
    |--------------------------------------------------------------------------
    |
    | These fields are optional but recommended for full functionality.
    |
    */
    'optional' => [
        'requires',
        'settings',
        'hooks',
        'routes',
        'permissions',
        'frontend',
        'license_required',
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Contract Schema
    |--------------------------------------------------------------------------
    |
    | Defines the structure for frontend integration.
    | Plugins can specify routes, components, and UI slots.
    |
    */
    'frontend' => [
        'routes' => [
            'path' => 'required|string',
            'name' => 'nullable|string',
            'component' => 'required|string',
            'meta' => 'nullable|array',
        ],
        'slots' => [
            'dashboard-widget',
            'sidebar-panel',
            'profile-tab',
            'inventory-slot',
            'navigation-item',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings Schema
    |--------------------------------------------------------------------------
    |
    | Defines the structure for plugin settings in plugin.json.
    |
    */
    'settings' => [
        'icon' => 'nullable|string',
        'color' => 'nullable|string',
        'route' => 'nullable|string',
        'menu' => [
            'enabled' => 'boolean',
            'order' => 'integer',
            'section' => 'string',
            'parent' => 'nullable|string',
        ],
    ],
];
