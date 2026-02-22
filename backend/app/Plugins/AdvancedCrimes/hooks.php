<?php
// Example plugin hook registration for admin sidebar
use App\Core\Services\GameHooks;

GameHooks::listen('admin.sidebar', function ($sections) {
    $sections[] = [
        'id' => 'advanced-crimes',
        'label' => 'Advanced Crimes',
        'icon' => 'BeakerIcon',
        'order' => 50,
        'children' => [
            [
                'route' => '/admin/plugins/advanced-crimes',
                'label' => 'Advanced Crimes',
                'icon' => 'BeakerIcon',
                'permission' => 'plugins.advanced-crimes.manage',
            ]
        ]
    ];
    return $sections;
});
