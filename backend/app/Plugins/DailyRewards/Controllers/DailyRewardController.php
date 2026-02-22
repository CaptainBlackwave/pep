<?php

namespace App\Plugins\DailyRewards\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Plugins\DailyRewards\Models\DailyReward;
use Illuminate\Http\Request;
use App\Core\Exceptions\GameException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class DailyRewardController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'total_claimants'   => DailyReward::count(),
            'claimed_today'     => DailyReward::whereDate('last_claimed_at', today())->count(),
            'top_streaks'       => DailyReward::with('user:id,username')
                ->orderByDesc('streak')
                ->limit(10)
                ->get(['id', 'user_id', 'streak', 'last_claimed_at']),
            'streak_breakdown'  => DailyReward::selectRaw('
                    SUM(CASE WHEN streak >= 30 THEN 1 ELSE 0 END) as month_plus,
                    SUM(CASE WHEN streak >= 7  AND streak < 30 THEN 1 ELSE 0 END) as week_plus,
                    SUM(CASE WHEN streak >= 3  AND streak < 7  THEN 1 ELSE 0 END) as few_days,
                    SUM(CASE WHEN streak < 3 THEN 1 ELSE 0 END) as new_players
                ')->first(),
        ];

        return response()->json($stats);
    }

    /**
     * Claim the daily reward for the authenticated user
     */
    public function claim(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        try {
            $service = app('daily_rewards.service');
            $result = $service->claimReward($user);

            // Trigger hook for listeners
            if (app()->bound('hook')) {
                app('hook')->dispatch('OnDailyRewardClaimed', [
                    'player' => $user,
                    'streak' => $result['streak'] ?? null,
                    'rewards' => $result['rewards'] ?? null,
                ]);
            }

            return response()->json(['success' => true, 'data' => $result]);
        } catch (GameException $e) {
            Log::warning('DailyRewards claim failed: ' . $e->getMessage(), ['user' => $user?->id]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('DailyRewards claim error: ' . $e->getMessage(), ['user' => $user?->id]);
            return response()->json(['success' => false, 'message' => 'Failed to claim daily reward'], 500);
        }
    }

    // No store/show/update/destroy — this is a player-controlled table
}
