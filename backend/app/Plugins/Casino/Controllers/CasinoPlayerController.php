<?php

namespace App\Plugins\Casino\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Plugins\Casino\CasinoModule;
use Illuminate\Http\Request;

class CasinoPlayerController extends Controller
{
    public function __construct(
        protected CasinoModule $casino
    ) {}

    /**
     * Get all available casino games
     */
    public function games()
    {
        return response()->json($this->casino->getAllGames());
    }

    /**
     * Play slots
     */
    public function playSlots(Request $request)
    {
        $request->validate([
            'game_id' => 'required|integer',
            'bet_amount' => 'required|integer|min:1',
        ]);

        try {
            $result = $this->casino->playSlots(
                $request->user(),
                $request->game_id,
                $request->bet_amount
            );
            return response()->json($result);
        } catch (\Throwable $e) {
            return $this->handleGameException($e, 422);
        }
    }

    /**
     * Play roulette
     */
    public function playRoulette(Request $request)
    {
        $request->validate([
            'game_id' => 'required|integer',
            'bet_amount' => 'required|integer|min:1',
            'bet_type' => 'required|in:number,color',
        ]);

        try {
            if ($request->bet_type === 'number') {
                $request->validate(['number' => 'required|integer|min:0|max:36']);
                $result = $this->casino->playRouletteNumber(
                    $request->user(),
                    $request->game_id,
                    $request->bet_amount,
                    $request->number
                );
            } else {
                $request->validate(['color' => 'required|in:red,black']);
                $result = $this->casino->playRouletteColor(
                    $request->user(),
                    $request->game_id,
                    $request->bet_amount,
                    $request->color
                );
            }
            return response()->json($result);
        } catch (\Throwable $e) {
            return $this->handleGameException($e, 422);
        }
    }

    /**
     * Play dice
     */
    public function playDice(Request $request)
    {
        $request->validate([
            'game_id' => 'required|integer',
            'bet_amount' => 'required|integer|min:1',
            'choice' => 'required|in:high,low',
        ]);

        try {
            $result = $this->casino->playDice(
                $request->user(),
                $request->game_id,
                $request->bet_amount,
                $request->choice
            );
            return response()->json($result);
        } catch (\Throwable $e) {
            return $this->handleGameException($e, 422);
        }
    }

    /**
     * Get player's casino stats
     */
    public function stats(Request $request)
    {
        return response()->json($this->casino->getUserStats($request->user()));
    }

    /**
     * Get player's bet history
     */
    public function history(Request $request)
    {
        return response()->json($this->casino->getBetHistory($request->user()));
    }

    /**
     * Buy a lottery ticket
     */
    public function buyLotteryTicket(Request $request)
    {
        $request->validate([
            'lottery_id' => 'required|integer',
            'numbers' => 'required|array',
        ]);

        try {
            $ticket = $this->casino->buyLotteryTicket(
                $request->user(),
                $request->lottery_id,
                $request->numbers
            );
            return response()->json(['message' => 'Lottery ticket purchased', 'ticket' => $ticket]);
        } catch (\Throwable $e) {
            return $this->handleGameException($e, 422);
        }
    }
}
