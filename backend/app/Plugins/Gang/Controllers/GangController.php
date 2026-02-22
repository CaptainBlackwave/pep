<?php

namespace App\Plugins\Gang\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Core\Models\User;
use App\Plugins\Gang\Models\Gang;
use App\Plugins\Gang\Services\GangService;
use Illuminate\Http\Request;

class GangController extends Controller
{
    public function __construct(
        private GangService $gangService
    ) {}

    /**
     * List all gangs (public leaderboard).
     */
    public function index()
    {
        $gangs = Gang::with('leader')
            ->orderBy('respect', 'desc')
            ->get();

        return response()->json($gangs);
    }

    /**
     * Create a new gang. Player must not already be in a gang.
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'tag'  => 'required|string|max:10',
        ]);

        $result = $this->gangService->createGang(
            $request->user(),
            $request->input('name'),
            $request->input('tag')
        );

        return response()->json($result, $result['success'] ? 201 : 422);
    }

    /**
     * Leave your current gang.
     */
    public function leave(Request $request)
    {
        $result = $this->gangService->leaveGang($request->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Kick a member from your gang. Requires the caller to be gang leader.
     */
    public function kick(Request $request, int $playerId)
    {
        $target = User::findOrFail($playerId);
        $result = $this->gangService->kickMember($request->user(), $target);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Deposit cash into the gang bank.
     */
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $result = $this->gangService->depositToGangBank(
            $request->user(),
            (int) $request->input('amount')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Withdraw cash from the gang bank. Requires the caller to be gang leader.
     */
    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $result = $this->gangService->withdrawFromGangBank(
            $request->user(),
            (int) $request->input('amount')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
