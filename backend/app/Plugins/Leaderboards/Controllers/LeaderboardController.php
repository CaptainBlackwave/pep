<?php

namespace App\Plugins\Leaderboards\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Core\Models\User;
use App\Plugins\Leaderboards\Services\LeaderboardService;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function adminIndex(LeaderboardService $service)
    {
        $types  = $service->getTypes();
        $boards = [];

        foreach ($types as $type) {
            $boards[$type['id']] = $service->getLeaderboard($type['id'], 10);
        }

        return response()->json([
            'types'  => $types,
            'boards' => $boards,
        ]);
    }

    public function index(Request $request)
    {
        $type = $request->get('type', 'level');

        $leaderboards = [
            'level' => $this->getLevelLeaderboard(),
            'respect' => $this->getRespectLeaderboard(),
            'cash' => $this->getCashLeaderboard(),
            'networth' => $this->getNetworthLeaderboard(),
        ];

        return response()->json([
            'leaderboards' => $leaderboards,
            'currentType' => $type,
        ]);
    }

    private function getLevelLeaderboard()
    {
        return User::select('id', 'username', 'level', 'experience', 'rank')
            ->orderBy('level', 'desc')
            ->orderBy('experience', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'id' => $user->id,
                    'username' => $user->username,
                    'level' => $user->level,
                    'experience' => $user->experience,
                    'rank_title' => $user->rank,
                ];
            });
    }

    private function getRespectLeaderboard()
    {
        return User::select('id', 'username', 'respect', 'level', 'rank')
            ->orderBy('respect', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'id' => $user->id,
                    'username' => $user->username,
                    'respect' => number_format($user->respect),
                    'level' => $user->level,
                    'rank_title' => $user->rank,
                ];
            });
    }

    private function getCashLeaderboard()
    {
        return User::select('id', 'username', 'cash', 'level', 'rank')
            ->orderBy('cash', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'id' => $user->id,
                    'username' => $user->username,
                    'cash' => '$' . number_format($user->cash),
                    'level' => $user->level,
                    'rank_title' => $user->rank,
                ];
            });
    }

    private function getNetworthLeaderboard()
    {
        return User::selectRaw('id, username, (cash + bank) as networth, level, rank')
            ->orderByRaw('(cash + bank) DESC')
            ->limit(50)
            ->get()
            ->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'id' => $user->id,
                    'username' => $user->username,
                    'networth' => '$' . number_format($user->networth),
                    'level' => $user->level,
                    'rank_title' => $user->rank,
                ];
            });
    }
}
