<?php

namespace App\Core\Middleware;

use App\Core\Models\Setting;
use App\Core\Models\UserDailyLimit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckDailyLimit
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if ($user && Setting::where('key', 'membership_enabled')->value('value') === '1') {
            $isExempt = $user->hasRole('admin')
                || $user->hasRole('moderator')
                || $user->hasRole('vendor')
                || $user->tier === 'paid';

            if (!$isExempt) {
                $today = now()->toDateString();
                $limit = UserDailyLimit::firstOrCreate(
                    ['user_id' => $user->id, 'date' => $today],
                    ['discussion_reads' => 0, 'replies_posted' => 0]
                );

                if ($limit->discussion_reads >= 5) {
                    throw new HttpException(429, 'You\'ve reached the daily limit. Upgrade for unlimited access.');
                }

                $limit->increment('discussion_reads');
            }
        }

        return $next($request);
    }
}
