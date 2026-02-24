<?php

namespace App\Plugins\MiniRpg\Controllers\Api;

use App\Core\Traits\HasPluginMetadata;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * RPG API Controller
 *
 * Demonstrates plugin API controller with metadata usage.
 */
class RpgController extends Controller
{
    /**
     * Get the authenticated user's RPG stats.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = [
            'gold' => $user->getPluginMeta('mini-rpg', 'gold', 0),
            'level' => $user->getPluginMeta('mini-rpg', 'level', 1),
            'experience' => $user->getPluginMeta('mini-rpg', 'experience', 0),
            'health' => $user->getPluginMeta('mini-rpg', 'health', 100),
            'max_health' => $user->getPluginMeta('mini-rpg', 'max_health', 100),
            'attack' => $user->getPluginMeta('mini-rpg', 'attack', 10),
            'defense' => $user->getPluginMeta('mini-rpg', 'defense', 5),
            'kills' => $user->getPluginMeta('mini-rpg', 'kills', 0),
            'deaths' => $user->getPluginMeta('mini-rpg', 'deaths', 0),
        ];

        // Calculate XP needed for next level
        $stats['xp_for_next_level'] = $stats['level'] * 100;
        $stats['xp_progress'] = $stats['experience'] / $stats['xp_for_next_level'];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Work to earn gold (with cooldown).
     */
    public function work(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check cooldown using user timer
        $timerKey = 'rpg_work';
        $lastWork = $user->getTimer($timerKey);

        if ($lastWork && $lastWork->diffInSeconds(now()) < 60) {
            $remaining = 60 - $lastWork->diffInSeconds(now());
            return response()->json([
                'success' => false,
                'message' => "You must wait {$remaining} seconds before working again.",
                'cooldown' => $remaining,
            ], 429);
        }

        // Calculate earnings based on level
        $level = $user->getPluginMeta('mini-rpg', 'level', 1);
        $baseGold = 10;
        $goldEarned = $baseGold + ($level * 2);

        // Add gold
        $newGold = $user->incrementPluginMeta('mini-rpg', 'gold', $goldEarned);

        // Add small XP
        $xpGained = 5;
        $currentXp = $user->getPluginMeta('mini-rpg', 'experience', 0);
        $currentLevel = $user->getPluginMeta('mini-rpg', 'level', 1);

        $newXp = $currentXp + $xpGained;
        $leveledUp = false;
        $xpRequired = $currentLevel * 100;

        if ($newXp >= $xpRequired) {
            $newXp -= $xpRequired;
            $newLevel = $currentLevel + 1;
            $leveledUp = true;

            $user->setManyPluginMeta('mini-rpg', [
                'experience' => $newXp,
                'level' => $newLevel,
            ]);

            // Increase stats on level up
            $user->incrementPluginMeta('mini-rpg', 'max_health', 10);
            $user->incrementPluginMeta('mini-rpg', 'attack', 2);
            $user->incrementPluginMeta('mini-rpg', 'defense', 1);

            // Broadcast level up
            broadcastToPluginUser($user->id, 'mini-rpg', 'level_up', [
                'level' => $newLevel,
            ]);
        } else {
            $user->setPluginMeta('mini-rpg', 'experience', $newXp);
        }

        // Set cooldown
        $user->setTimer($timerKey, now());

        // Broadcast gold update
        broadcastToPluginUser($user->id, 'mini-rpg', 'gold_updated', [
            'gold' => $newGold,
            'change' => $goldEarned,
        ]);

        return response()->json([
            'success' => true,
            'message' => "You worked and earned {$goldEarned} gold!",
            'data' => [
                'gold_earned' => $goldEarned,
                'gold' => $newGold,
                'xp_gained' => $xpGained,
                'leveled_up' => $leveledUp,
                'new_level' => $leveledUp ? $user->getPluginMeta('mini-rpg', 'level') : null,
            ],
        ]);
    }

    /**
     * Get the RPG leaderboard.
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $type = $request->input('type', 'level');

        $validTypes = ['level', 'gold', 'kills'];
        if (!in_array($type, $validTypes)) {
            $type = 'level';
        }

        // Query plugin metadata for leaderboard
        $entries = \App\Core\Models\PluginMetadata::where('plugin_id', 'mini-rpg')
            ->where('key', $type)
            ->with('owner')
            ->orderByDesc('value')
            ->limit(10)
            ->get()
            ->map(function ($entry, $index) {
                return [
                    'rank' => $index + 1,
                    'user_id' => $entry->owner_id,
                    'username' => $entry->owner->username ?? 'Unknown',
                    'value' => $entry->value,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'entries' => $entries,
            ],
        ]);
    }
}
