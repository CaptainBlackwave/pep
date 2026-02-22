<?php

namespace App\Plugins\Inventory\Services;

use App\Core\Contracts\InventoryInterface;
use App\Core\Exceptions\GameException;
use App\Core\Models\User;
use App\Core\Models\Item;
use App\Plugins\Inventory\Models\UserInventory;
use Illuminate\Support\Collection;

class InventoryService implements InventoryInterface
{
    // --- InventoryInterface ---

    public function give(User $user, int $itemId, int $quantity = 1): mixed
    {
        return $this->addItem($user, $itemId, $quantity);
    }

    public function take(User $user, int $itemId, int $quantity = 1): bool
    {
        return $this->removeItem($user, $itemId, $quantity);
    }

    public function has(User $user, int $itemId, int $quantity = 1): bool
    {
        return $this->hasItem($user, $itemId, $quantity);
    }

    // --- End InventoryInterface ---

    /**
     * Get user's inventory with full details.
     */
    public function getPlayerInventory(User $user): Collection
    {
        return UserInventory::where('user_id', $user->id)
            ->with('item')
            ->get()
            ->map(function ($inventory) {
                return [
                    'id' => $inventory->id,
                    'item' => $inventory->item,
                    'quantity' => $inventory->quantity,
                    'equipped' => $inventory->equipped ?? false,
                ];
            });
    }

    /**
     * Get user's inventory.
     */
    public function getInventory(User $user): Collection
    {
        return UserInventory::where('user_id', $user->id)
            ->with('item')
            ->get();
    }

    /**
     * Add an item to user's inventory.
     */
    public function addItem(User $user, int $itemId, int $quantity = 1): UserInventory
    {
        $inventory = UserInventory::firstOrCreate(
            ['user_id' => $user->id, 'item_id' => $itemId],
            ['quantity' => 0]
        );

        $inventory->addQuantity($quantity);

        return $inventory->fresh();
    }

    /**
     * Remove an item from user's inventory.
     */
    public function removeItem(User $user, int $itemId, int $quantity = 1): bool
    {
        $inventory = UserInventory::where('user_id', $user->id)
            ->where('item_id', $itemId)
            ->first();

        if (!$inventory) {
            return false;
        }

        return $inventory->removeQuantity($quantity);
    }

    /**
     * Check if user has an item.
     */
    public function hasItem(User $user, int $itemId, int $quantity = 1): bool
    {
        $inventory = UserInventory::where('user_id', $user->id)
            ->where('item_id', $itemId)
            ->first();

        if (!$inventory) {
            return false;
        }

        return $inventory->hasQuantity($quantity);
    }

    /**
     * Get item quantity in user's inventory.
     */
    public function getItemQuantity(User $user, int $itemId): int
    {
        $inventory = UserInventory::where('user_id', $user->id)
            ->where('item_id', $itemId)
            ->first();

        return $inventory ? $inventory->quantity : 0;
    }

    /**
     * Transfer an item to another user.
     */
    public function transferItem(User $from, User $to, int $itemId, int $quantity = 1): bool
    {
        // Check if sender has the item
        if (!$this->hasItem($from, $itemId, $quantity)) {
            return false;
        }

        // Check if item is tradeable
        $item = Item::find($itemId);
        if (!$item || !$item->tradeable) {
            return false;
        }

        // Remove from sender
        if (!$this->removeItem($from, $itemId, $quantity)) {
            return false;
        }

        // Add to receiver
        $this->addItem($to, $itemId, $quantity);

        return true;
    }

    /**
     * Use a consumable item.
     */
    public function useItem(User $user, int $inventoryId): array
    {
        $inventory = UserInventory::where('id', $inventoryId)
            ->where('user_id', $user->id)
            ->first();
        if (!$inventory) {
            return ['success' => false, 'message' => 'Inventory item not found.'];
        }
        $item = Item::find($inventory->item_id);
        if (!$item) {
            return ['success' => false, 'message' => 'Item not found.'];
        }
        if ($item->type !== 'consumable') {
            return ['success' => false, 'message' => 'This item cannot be used.'];
        }
        if ($inventory->quantity < 1) {
            return ['success' => false, 'message' => 'You do not have this item.'];
        }
        // Apply item effects
        $effects = $item->stats ?? [];
        if (is_string($effects)) {
            $effects = json_decode($effects, true);
        }
        foreach ($effects as $stat => $value) {
            if (in_array($stat, ['health', 'energy', 'nerve', 'happiness', 'awake'])) {
                $currentValue = $user->{$stat} ?? 0;
                $maxValue = $user->{"max_$stat"} ?? 100;
                $user->{$stat} = min($maxValue, $currentValue + $value);
            }
        }
        $user->save();
        $inventory->quantity -= 1;
        $inventory->save();
        return [
            'success' => true,
            'message' => "You used {$item->name}.",
            'effects' => $effects,
        ];
    }

    /**
     * Buy an item.
     */
    public function buyItem(User $user, int $itemId, int $quantity = 1): array
    {
        return \DB::transaction(function () use ($user, $itemId, $quantity) {
            $user = User::where('id', $user->id)->lockForUpdate()->first();
            $item = Item::find($itemId);
            if (!$item) {
                return ['success' => false, 'message' => 'Item not found.'];
            }
            $totalCost = $item->price * $quantity;
            if ($user->cash < $totalCost) {
                return ['success' => false, 'message' => 'Insufficient funds.'];
            }
            $user->cash -= $totalCost;
            $user->save();
            $this->addItem($user, $itemId, $quantity);
            return [
                'success' => true,
                'message' => "You purchased {$quantity}x {$item->name} for \${$totalCost}.",
                'cost' => $totalCost,
            ];
        });
    }

    /**
     * Sell an item.
     */
    public function sellItem(User $user, int $inventoryId, int $quantity = 1): array
    {
        $inventory = UserInventory::where('id', $inventoryId)
            ->where('user_id', $user->id)
            ->first();
        if (!$inventory) {
            return ['success' => false, 'message' => 'Inventory item not found.'];
        }
        $item = Item::find($inventory->item_id);
        if (!$item) {
            return ['success' => false, 'message' => 'Item not found.'];
        }
        if (!$item->tradeable) {
            return ['success' => false, 'message' => 'This item cannot be sold.'];
        }
        if ($inventory->quantity < $quantity) {
            return ['success' => false, 'message' => 'You do not have enough of this item.'];
        }
        $sellPrice = ($item->sell_price ?? 0) * $quantity;
        $inventory->quantity -= $quantity;
        $inventory->save();
        $user->cash += $sellPrice;
        $user->save();
        return [
            'success' => true,
            'message' => "You sold {$quantity}x {$item->name} for \${$sellPrice}.",
            'amount' => $sellPrice,
        ];
    }

    /**
     * Get inventory by type.
     */
    public function getInventoryByType(User $user, string $type): Collection
    {
        return UserInventory::where('user_id', $user->id)
            ->whereHas('item', function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->with('item')
            ->get();
    }

    /**
     * Get total inventory value.
     */
    public function getTotalInventoryValue(User $user): int
    {
        $inventory = $this->getInventory($user);
        $totalValue = 0;

        foreach ($inventory as $item) {
            $totalValue += ($item->item->sell_price ?? 0) * $item->quantity;
        }

        return $totalValue;
    }

    /**
     * Clear user's inventory.
     */
    public function clearInventory(User $user): void
    {
        UserInventory::where('user_id', $user->id)->delete();
    }

    /**
     * Get inventory count.
     */
    public function getInventoryCount(User $user): int
    {
        return UserInventory::where('user_id', $user->id)->count();
    }

    /**
     * Equip an item from the user's inventory.
     */
    public function equipItem(User $user, int $inventoryId): array
    {
        $inventory = UserInventory::where('user_id', $user->id)
            ->where('id', $inventoryId)
            ->first();

        if (!$inventory) {
            throw new GameException('Item not found in your inventory.');
        }

        $item = $inventory->item;

        if (!$item || !in_array($item->type, ['weapon', 'armor', 'equipment'])) {
            throw new GameException('This item cannot be equipped.');
        }

        $inventory->equipped = true;
        $inventory->save();

        return [
            'success' => true,
            'message' => "{$item->name} has been equipped.",
        ];
    }

    /**
     * Unequip an item from the user's inventory.
     */
    public function unequipItem(User $user, int $inventoryId): array
    {
        $inventory = UserInventory::where('user_id', $user->id)
            ->where('id', $inventoryId)
            ->first();

        if (!$inventory) {
            throw new GameException('Item not found in your inventory.');
        }

        if (!$inventory->equipped) {
            throw new GameException('This item is not equipped.');
        }

        $item = $inventory->item;

        $inventory->equipped = false;
        $inventory->save();

        return [
            'success' => true,
            'message' => "{$item->name} has been unequipped.",
        ];
    }
}
