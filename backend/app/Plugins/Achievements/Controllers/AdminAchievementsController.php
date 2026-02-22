<?php

namespace App\Plugins\Achievements\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Plugins\Achievements\Models\Achievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAchievementsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Achievement::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        return response()->json(
            $query->orderBy('sort_order')->orderBy('name')->paginate((int) $request->get('per_page', 25))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'required|string|max:100',
            'requirement' => 'required|integer|min:1',
            'reward_cash' => 'nullable|integer|min:0',
            'reward_xp'   => 'nullable|integer|min:0',
            'icon'        => 'nullable|string|max:50',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $achievement = Achievement::create($validated);

        return response()->json($achievement, 201);
    }

    public function update(Request $request, Achievement $achievement): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'sometimes|string|max:100',
            'requirement' => 'sometimes|integer|min:1',
            'reward_cash' => 'nullable|integer|min:0',
            'reward_xp'   => 'nullable|integer|min:0',
            'icon'        => 'nullable|string|max:50',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $achievement->update($validated);

        return response()->json($achievement);
    }

    public function destroy(Achievement $achievement): JsonResponse
    {
        $achievement->delete();

        return response()->json(null, 204);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'total'        => Achievement::count(),
            'earned_total' => DB::table('user_achievements')->whereNotNull('earned_at')->count(),
            'in_progress'  => DB::table('user_achievements')->whereNull('earned_at')->count(),
        ]);
    }
}
