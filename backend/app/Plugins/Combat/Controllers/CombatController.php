<?php

namespace App\Plugins\Combat\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Core\Pipeline\ActionContext;
use App\Core\Pipeline\ActionPipeline;
use App\Plugins\Combat\Services\CombatService;
use App\Plugins\Combat\Services\NPCCombatService;
use App\Plugins\Combat\Models\CombatLocation;
use Illuminate\Http\Request;

class CombatController extends Controller
{
    public function __construct(
        private CombatService $combatService,
        private NPCCombatService $npcCombatService
    ) {}

    /**
     * Display combat interface
     */
    public function index(Request $request)
    {
        $player = $request->user();

        $availableTargets = $this->combatService->getAvailableTargets($player);
        $combatHistory = $this->combatService->getCombatHistory($player, 15);

        return response()->json([
            'availableTargets' => $availableTargets,
            'combatHistory' => $combatHistory,
            'player' => $player->load(['equippedItems.item']),
        ]);
    }

    /**
     * Attack a player
     */
    public function attack(Request $request)
    {
        $request->validate([
            'defender_id' => 'required|exists:users,id',
        ]);

        $player     = $request->user();
        $defenderId = (int) $request->defender_id;
        $service    = $this->combatService;

        $context = new ActionContext($player, 'combat.attack', ['defender_id' => $defenderId], 'combat');
        $context = app(ActionPipeline::class)->run($context, function (ActionContext $ctx) use ($service) {
            $ctx->result = $service->attackPlayer($ctx->player, $ctx->payload['defender_id']);
        });

        if (!$context->isSuccessful()) {
            return response()->json(['success' => false, 'error' => implode('; ', $context->errors)], 422);
        }

        return response()->json(['success' => true, 'result' => $context->result]);
    }

    /**
     * Get all combat locations with their areas for NPC hunting
     */
    public function locations(Request $request)
    {
        $user = $request->user();

        $locations = CombatLocation::with(['areas' => function ($query) {
                $query->where('active', true)->orderBy('difficulty');
            }])
            ->where('active', true)
            ->where('min_level', '<=', $user->level)
            ->orderBy('order')
            ->get()
            ->map(function ($location) use ($user) {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'description' => $location->description,
                    'image' => $location->image,
                    'energy_cost' => $location->energy_cost,
                    'min_level' => $location->min_level,
                    'areas' => $location->areas->map(function ($area) {
                        return [
                            'id' => $area->id,
                            'name' => $area->name,
                            'description' => $area->description,
                            'difficulty' => $area->difficulty,
                            'min_level' => $area->min_level,
                        ];
                    }),
                ];
            });

        return response()->json([
            'locations' => $locations,
            'player' => $user->only(['id', 'username', 'level', 'health', 'max_health', 'energy', 'max_energy', 'cash']),
        ]);
    }

    /**
     * Start hunting (spawn NPC enemy)
     */
    public function hunt(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:combat_locations,id',
            'area_id'     => 'required|exists:combat_areas,id',
        ]);

        $player     = $request->user();
        $locationId = (int) $request->location_id;
        $areaId     = (int) $request->area_id;
        $service    = $this->npcCombatService;

        $context = new ActionContext($player, 'combat.hunt', ['location_id' => $locationId, 'area_id' => $areaId], 'combat');
        $context = app(ActionPipeline::class)->run($context, function (ActionContext $ctx) use ($service) {
            $ctx->result = $service->startHunt($ctx->player, $ctx->payload['location_id'], $ctx->payload['area_id']);
        });

        $result = $context->result ?? ['success' => false, 'error' => implode('; ', $context->errors)];
        return response()->json($result, ($result['success'] ?? false) ? 200 : 400);
    }

    /**
     * Attack NPC enemy
     */
    public function attackNPC(Request $request)
    {
        $request->validate([
            'fight_id'  => 'required|exists:combat_fights,id',
            'weapon_id' => 'nullable|exists:items,id',
        ]);

        $player   = $request->user();
        $fightId  = (int) $request->fight_id;
        $weaponId = $request->weapon_id ? (int) $request->weapon_id : null;
        $service  = $this->npcCombatService;

        $context = new ActionContext($player, 'combat.attack_npc', ['fight_id' => $fightId, 'weapon_id' => $weaponId], 'combat');
        $context = app(ActionPipeline::class)->run($context, function (ActionContext $ctx) use ($service) {
            $ctx->result = $service->attackNPC($ctx->player, $ctx->payload['fight_id'], $ctx->payload['weapon_id']);
        });

        $result = $context->result ?? ['success' => false, 'error' => implode('; ', $context->errors)];
        return response()->json($result, ($result['success'] ?? false) ? 200 : 400);
    }

    /**
     * Auto attack NPC enemy (3-5 attacks)
     */
    public function autoAttackNPC(Request $request)
    {
        $request->validate([
            'fight_id' => 'required|exists:combat_fights,id',
        ]);

        $player  = $request->user();
        $fightId = (int) $request->fight_id;
        $service = $this->npcCombatService;

        $context = new ActionContext($player, 'combat.auto_attack_npc', ['fight_id' => $fightId], 'combat');
        $context = app(ActionPipeline::class)->run($context, function (ActionContext $ctx) use ($service) {
            $ctx->result = $service->autoAttackNPC($ctx->player, $ctx->payload['fight_id']);
        });

        $result = $context->result ?? ['success' => false, 'error' => implode('; ', $context->errors)];
        return response()->json($result, ($result['success'] ?? false) ? 200 : 400);
    }
}
