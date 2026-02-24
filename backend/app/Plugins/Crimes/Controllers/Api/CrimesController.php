<?php

namespace App\Plugins\Crimes\Controllers\Api;

use App\Plugins\Crimes\CrimesPlugin;
use App\Plugins\Crimes\Models\Crime;
use App\Plugins\Crimes\Models\CrimeAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Crimes API Controller
 */
class CrimesController extends Controller
{
    protected CrimesPlugin $plugin;

    public function __construct(CrimesPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Get all available crimes.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $crimes = $this->plugin->getAvailableCrimes($user);

        return response()->json([
            'success' => true,
            'data' => $crimes,
        ]);
    }

    /**
     * Get a specific crime.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $crime = Crime::findOrFail($id);

        $successRate = $this->plugin->calculateSuccessRate($crime, $user);

        return response()->json([
            'success' => true,
            'data' => array_merge($crime->toArray(), [
                'success_rate' => $successRate,
                'can_attempt' => $this->plugin->canAttempt($user, $crime),
            ]),
        ]);
    }

    /**
     * Attempt to commit a crime.
     */
    public function attempt(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $result = $this->plugin->attemptCrime($user, $id);

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result,
        ], $statusCode);
    }

    /**
     * Get user's crime statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->plugin->getStats($user);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get user's crime history.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $attempts = CrimeAttempt::where('user_id', $user->id)
            ->with('crime:id,name,difficulty')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $attempts,
        ]);
    }
}
