<?php

namespace App\Plugins\Detective\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Core\Models\User;
use App\Plugins\Detective\Services\DetectiveService;
use Illuminate\Http\Request;

class DetectiveController extends Controller
{
    public function __construct(
        protected DetectiveService $detectiveService
    ) {}

    public function index(Request $request)
    {
        $player = $request->user();
        $reports = $player ? $this->detectiveService->getMyReports($player) : collect();

        return response()->json([
            'player' => $player,
            'reports' => $reports,
            'cost' => DetectiveService::DETECTIVE_COST,
            'investigationTime' => DetectiveService::INVESTIGATION_TIME / 60,
        ]);
    }

    public function hire(Request $request)
    {
        $request->validate([
            'target_id' => 'required|exists:users,id',
        ]);

        $player = $request->user();
        $target = User::findOrFail($request->target_id);

        try {
            $result = $this->detectiveService->hireDetective($player, $target);
            return response()->json(['success' => true, 'message' => $result['message'], 'result' => $result]);
        } catch (\Throwable $e) {
            return $this->handleGameException($e, 422);
        }
    }
}
