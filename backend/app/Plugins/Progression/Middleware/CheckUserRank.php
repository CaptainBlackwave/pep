<?php

namespace App\Plugins\Progression\Middleware;

use App\Plugins\Progression\Services\ProgressionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on every authenticated API request to automatically promote players
 * whose experience has crossed a rank threshold since their last request.
 *
 * Also refreshes the last_active timestamp so "online players" queries stay
 * accurate without requiring explicit pings.
 *
 * Moved from App\Core\Middleware\CheckUserRank as part of Phase 2.
 * App\Core\Middleware\CheckUserRank is a thin shim extending this class.
 */
class CheckUserRank
{
    public function __construct(private ProgressionService $progressionService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            // Auto-promote if experience threshold crossed
            $this->progressionService->checkRank($user);

            // Refresh last_active (only writes to users table identity columns — fine)
            $user->update(['last_active' => now()]);
        }

        return $next($request);
    }
}
