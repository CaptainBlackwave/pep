<?php

namespace App\Core\Middleware;

use App\Core\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class CheckTier
{
    public function handle(Request $request, Closure $next, string ...$requiredTiers): mixed
    {
        if (Setting::where('key', 'membership_enabled')->value('value') !== '1') {
            return $next($request);
        }

        $user = $request->user();

        if ($user && ($user->hasRole('admin') || $user->hasRole('moderator'))) {
            return $next($request);
        }

        if ($user && $user->hasRole('vendor')) {
            return $next($request);
        }

        if ($user && $user->tier === 'paid') {
            if ($user->subscription_ends_at && $user->subscription_ends_at->isFuture()) {
                return $next($request);
            }
            $user->update(['tier' => 'free']);
        }

        if (in_array('paid', $requiredTiers) && (!$user || $user->tier !== 'paid')) {
            abort(403, 'This content requires a paid membership.');
        }

        if (in_array('free', $requiredTiers) && !$user) {
            abort(401, 'Please log in to access this content.');
        }

        return $next($request);
    }
}
