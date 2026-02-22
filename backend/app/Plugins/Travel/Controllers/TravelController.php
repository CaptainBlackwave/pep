<?php

namespace App\Plugins\Travel\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Plugins\Travel\Models\Location;
use App\Plugins\Travel\Services\TravelService;
use Illuminate\Http\Request;

class TravelController extends Controller
{
    public function __construct(
        protected TravelService $travelService
    ) {}

    public function index(Request $request)
    {
        $player = $request->user();
        $locations = $this->travelService->getAvailableLocations();
        $currentLocation = $player ? Location::find($player->location_id) : null;
        $playersHere = $currentLocation ? $this->travelService->getPlayersInLocation($currentLocation) : [];

        return response()->json([
            'player' => $player,
            'locations' => $locations,
            'currentLocation' => $currentLocation,
            'playersHere' => $playersHere,
        ]);
    }

    public function travel(Request $request, Location $location)
    {
        $player = $request->user();

        try {
            $result = $this->travelService->travel($player, $location);
            return response()->json(['success' => true, 'message' => $result['message'], 'result' => $result]);
        } catch (\Throwable $e) {
            return $this->handleGameException($e, 422);
        }
    }
}
