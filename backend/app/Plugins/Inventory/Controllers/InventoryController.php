<?php

namespace App\Plugins\Inventory\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Core\Models\Item;
use App\Core\Pipeline\ActionContext;
use App\Core\Pipeline\ActionPipeline;
use App\Plugins\Inventory\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $player = $request->user();
        $inventory = $this->inventoryService->getPlayerInventory($player);

        return response()->json([
            'player'    => $player,
            'inventory' => $inventory,
        ]);
    }

    public function shop(Request $request): JsonResponse
    {
        $player = $request->user();
        $items  = Item::all();

        return response()->json([
            'player' => $player,
            'items'  => $items,
        ]);
    }

    public function buy(Request $request, Item $item): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $player   = $request->user();
        $quantity = (int) $request->quantity;
        $service  = $this->inventoryService;

        $context = new ActionContext($player, 'inventory.buy', ['item_id' => $item->id, 'quantity' => $quantity], 'inventory');
        $context = app(ActionPipeline::class)->run($context, function (ActionContext $ctx) use ($service, $item) {
            $ctx->result = $service->buyItem($ctx->player, $item->id, $ctx->payload['quantity']);
        });

        if (!$context->isSuccessful()) {
            return response()->json(['success' => false, 'error' => implode('; ', $context->errors)], 400);
        }

        $result = $context->result;
        return response()->json($result, ($result['success'] ?? false) ? 200 : 400);
    }

    public function sell(Request $request, int $inventoryId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $player   = $request->user();
        $quantity = (int) $request->quantity;
        $service  = $this->inventoryService;

        $context = new ActionContext($player, 'inventory.sell', ['inventory_id' => $inventoryId, 'quantity' => $quantity], 'inventory');
        $context = app(ActionPipeline::class)->run($context, function (ActionContext $ctx) use ($service) {
            $ctx->result = $service->sellItem($ctx->player, $ctx->payload['inventory_id'], $ctx->payload['quantity']);
        });

        $result = $context->result ?? ['success' => false, 'error' => implode('; ', $context->errors)];
        return response()->json($result, ($result['success'] ?? false) ? 200 : 400);
    }

    public function equip(Request $request, int $inventoryId): JsonResponse
    {
        $player  = $request->user();
        $service = $this->inventoryService;

        $context = new ActionContext($player, 'inventory.equip', ['inventory_id' => $inventoryId], 'inventory');
        $context = app(ActionPipeline::class)->run($context, function (ActionContext $ctx) use ($service) {
            $ctx->result = $service->equipItem($ctx->player, $ctx->payload['inventory_id']);
        });

        $result = $context->result ?? ['success' => false, 'error' => implode('; ', $context->errors)];
        return response()->json($result, ($result['success'] ?? false) ? 200 : 400);
    }

    public function unequip(Request $request, int $inventoryId): JsonResponse
    {
        $player  = $request->user();
        $service = $this->inventoryService;

        $context = new ActionContext($player, 'inventory.unequip', ['inventory_id' => $inventoryId], 'inventory');
        $context = app(ActionPipeline::class)->run($context, function (ActionContext $ctx) use ($service) {
            $ctx->result = $service->unequipItem($ctx->player, $ctx->payload['inventory_id']);
        });

        $result = $context->result ?? ['success' => false, 'error' => implode('; ', $context->errors)];
        return response()->json($result, ($result['success'] ?? false) ? 200 : 400);
    }

    public function use(Request $request, int $inventoryId): JsonResponse
    {
        $player  = $request->user();
        $service = $this->inventoryService;

        $context = new ActionContext($player, 'inventory.use', ['inventory_id' => $inventoryId], 'inventory');
        $context = app(ActionPipeline::class)->run($context, function (ActionContext $ctx) use ($service) {
            $ctx->result = $service->useItem($ctx->player, $ctx->payload['inventory_id']);
        });

        $result = $context->result ?? ['success' => false, 'error' => implode('; ', $context->errors)];
        return response()->json($result, ($result['success'] ?? false) ? 200 : 400);
    }
}
