<?php

namespace App\Plugins\Gym\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Plugins\Gym\Services\GymService;
use Illuminate\Http\Request;

class GymController extends Controller
{
    public function __construct(
        protected GymService $gymService
    ) {}

    public function index(Request $request)
    {
        $player = $request->user();
        $info = $this->gymService->getTrainingInfo();

        return response()->json([
            'player' => $player,
            'costs' => $info['costs'],
            'maxPerSession' => $info['max_per_session'],
        ]);
    }

    public function train(Request $request)
    {
        $request->validate([
            'attribute' => 'required|in:strength,defense,speed,stamina',
            'times' => 'required|integer|min:1|max:100',
        ]);

        $player = $request->user();

        try {
            $result = $this->gymService->train($player, $request->attribute, $request->times);
            return response()->json(['success' => true, 'message' => $result['message'], 'result' => $result]);
        } catch (\Throwable $e) {
            return $this->handleGameException($e, 422);
        }
    }
}
