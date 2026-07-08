<?php

namespace App\Core\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $routeMiddleware = [
        'tier' => \App\Core\Middleware\CheckTier::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            // ...existing middleware...
        ],
        'api' => [
            // ...existing middleware...
        ],
        'installer' => [
            \App\Core\Http\Middleware\InstallerLocked::class,
        ],
    ];

    // ...existing code...
}
