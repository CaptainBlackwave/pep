<?php

namespace App\Plugins\Racing\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Plugins\Racing\Models\Race;
use App\Plugins\Racing\Services\RaceService;
use Illuminate\Http\Request;

class RaceController extends Controller
{
    protected $raceService;

    public function __construct(RaceService $raceService)
    {
        $this->raceService = $raceService;
    }

    public function index(Request $request)
    {
        $player = $request->user();

        $availableRaces = $this->raceService->getAvailableRaces($player);
        $raceHistory = $this->raceService->getRaceHistory($player, 5);

        // Get player's vehicles
        $vehicles = $player->inventory()
            ->with('item')
            ->whereHas('item', function ($query) {
                $query->where('type', 'vehicle');
            })
            ->get();

        return response()->json([
            'availableRaces' => $availableRaces,
            'raceHistory' => $raceHistory,
            'vehicles' => $vehicles,
            'player' => $player,
        ]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'entry_fee' => 'required|integer|min:100|max:1000000',
            'min_participants' => 'integer|min:2|max:8',
            'max_participants' => 'integer|min:2|max:8',
        ]);

        try {
            $player = $request->user();
            $race = $this->raceService->createRace($player, $request->all());

            return response()->json(['success' => true, 'message' => 'Race created successfully!', 'race' => $race]);
        } catch (\Throwable $e) {
            return $this->handleGameException($e, 422);
        }
    }

    public function join(Request $request, Race $race)
    {
        $request->validate([
            'vehicle_id' => 'nullable|integer|exists:player_inventories,id',
            'bet_amount' => 'integer|min:0',
        ]);

        try {
            $player = $request->user();
            $this->raceService->joinRace(
                $player,
                $race->id,
                $request->vehicle_id,
                $request->bet_amount ?? 0
            );

            return response()->json(['success' => true, 'message' => 'Joined race successfully!']);
        } catch (\Throwable $e) {
            return $this->handleGameException($e, 422);
        }
    }

    public function leave(Request $request, Race $race)
    {
        try {
            $player = $request->user();
            $this->raceService->leaveRace($player, $race->id);

            return response()->json(['success' => true, 'message' => 'Left race (90% refunded)']);
        } catch (\Throwable $e) {
            return $this->handleGameException($e, 422);
        }
    }

    public function start(Request $request, Race $race)
    {
        try {
            $player = $request->user();
            $results = $this->raceService->startRace($race->id);

            return response()->json(['success' => true, 'message' => 'Race finished!', 'results' => $results]);
        } catch (\Throwable $e) {
            return $this->handleGameException($e, 422);
        }
    }
}
