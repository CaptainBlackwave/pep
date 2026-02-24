<?php

namespace App\Plugins\Bank\Controllers\Api;

use App\Plugins\Bank\BankPlugin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Bank API Controller
 */
class BankController extends Controller
{
    protected BankPlugin $plugin;

    public function __construct(BankPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Get bank balance and statistics.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->plugin->getStats($user);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Deposit cash into bank.
     */
    public function deposit(Request $request): JsonResponse
    {
        $request->validate(['amount' => 'required|integer|min:1']);

        $user = $request->user();
        $result = $this->plugin->deposit($user, $request->amount);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result,
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Withdraw cash from bank.
     */
    public function withdraw(Request $request): JsonResponse
    {
        $request->validate(['amount' => 'required|integer|min:1']);

        $user = $request->user();
        $result = $this->plugin->withdraw($user, $request->amount);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result,
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Transfer cash to another user.
     */
    public function transfer(Request $request): JsonResponse
    {
        $request->validate([
            'to_user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $result = $this->plugin->transfer($user, $request->to_user_id, $request->amount);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result,
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Get transaction history.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->input('limit', 20);
        $history = $this->plugin->getHistory($user, $limit);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
