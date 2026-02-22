<?php

namespace App\Plugins\Bank\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Core\Pipeline\ActionContext;
use App\Core\Pipeline\ActionPipeline;
use App\Plugins\Bank\Services\BankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankController extends Controller
{
    protected $bankService;

    public function __construct(BankService $bankService)
    {
        $this->bankService = $bankService;
    }

    /**
     * Get bank state for the authenticated player.
     */
    public function index(Request $request): JsonResponse
    {
        $player = $request->user();
        $taxRate = $this->bankService->getTaxRate();

        return response()->json([
            'player'  => $player,
            'taxRate' => $taxRate,
        ]);
    }

    /**
     * Deposit money into bank.
     */
    public function deposit(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $player  = $request->user();
        $amount  = (int) $request->input('amount');
        $service = $this->bankService;

        $context = new ActionContext($player, 'bank.deposit', compact('amount'), 'bank');
        $context = app(ActionPipeline::class)->run($context, function (ActionContext $ctx) use ($service) {
            $ctx->result = $service->deposit($ctx->player, $ctx->payload['amount']);
        });

        $result = $context->result ?? ['success' => false, 'error' => implode('; ', $context->errors)];
        return response()->json($result, ($result['success'] ?? false) ? 200 : 400);
    }

    /**
     * Withdraw money from bank.
     */
    public function withdraw(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $player  = $request->user();
        $amount  = (int) $request->input('amount');
        $service = $this->bankService;

        $context = new ActionContext($player, 'bank.withdraw', compact('amount'), 'bank');
        $context = app(ActionPipeline::class)->run($context, function (ActionContext $ctx) use ($service) {
            $ctx->result = $service->withdraw($ctx->player, $ctx->payload['amount']);
        });

        $result = $context->result ?? ['success' => false, 'error' => implode('; ', $context->errors)];
        return response()->json($result, ($result['success'] ?? false) ? 200 : 400);
    }

    /**
     * Transfer money to another player.
     */
    public function transfer(Request $request): JsonResponse
    {
        $request->validate([
            'recipient' => 'required|string',
            'amount'    => 'required|integer|min:1',
        ]);

        $player    = $request->user();
        $recipient = $request->input('recipient');
        $amount    = (int) $request->input('amount');
        $service   = $this->bankService;

        $context = new ActionContext($player, 'bank.transfer', compact('recipient', 'amount'), 'bank');
        $context = app(ActionPipeline::class)->run($context, function (ActionContext $ctx) use ($service) {
            $ctx->result = $service->transfer(
                $ctx->player,
                $ctx->payload['recipient'],
                $ctx->payload['amount']
            );
        });

        $result = $context->result ?? ['success' => false, 'error' => implode('; ', $context->errors)];
        return response()->json($result, ($result['success'] ?? false) ? 200 : 400);
    }
}
