<?php

namespace App\Plugins\Employment\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Plugins\Employment\EmploymentModule;
use App\Plugins\Employment\Models\Company;
use App\Plugins\Employment\Models\EmploymentPosition;
use App\Plugins\Employment\Models\UserEmployment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmploymentApiController extends Controller
{
    protected EmploymentModule $employmentModule;

    public function __construct(EmploymentModule $employmentModule)
    {
        $this->employmentModule = $employmentModule;
    }

    /**
     * Get all available positions with companies
     */
    public function index(): JsonResponse
    {
        $positions = EmploymentPosition::where('is_active', true)
            ->with('company')
            ->orderBy('level_requirement')
            ->get();

        return response()->json([
            'positions' => $positions,
        ]);
    }

    /**
     * Get current employment status
     */
    public function currentJob(): JsonResponse
    {
        $user = Auth::user();
        $stats = $this->employmentModule->getStats($user);

        $employment = $this->employmentModule->getCurrentEmployment($user);

        return response()->json([
            'employment' => $employment?->load(['company', 'position']),
            'has_worked_today' => $stats['has_worked_today'] ?? false,
        ]);
    }

    /**
     * Apply for a position
     */
    public function apply(Request $request): JsonResponse
    {
        $request->validate(['position_id' => 'required|integer']);
        $user = Auth::user();
        $pos = EmploymentPosition::findOrFail($request->position_id);

        $result = $this->employmentModule->applyForJob($user, $pos);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Work at current job
     */
    public function work(): JsonResponse
    {
        $user = Auth::user();
        $result = $this->employmentModule->work($user);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Quit current job
     */
    public function quit(): JsonResponse
    {
        $user = Auth::user();
        $result = $this->employmentModule->quitJob($user);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get employment history
     */
    public function history(): JsonResponse
    {
        $user = Auth::user();

        $history = UserEmployment::where('user_id', $user->id)
            ->with(['company', 'position'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
