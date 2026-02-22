<?php

namespace App\Core\Services;

use App\Core\Contracts\EconomyInterface;
use App\Core\Events\Economy\MoneyCredited;
use App\Core\Events\Economy\MoneyDebited;
use App\Core\Events\Economy\MoneyTransferred;
use App\Core\Exceptions\InsufficientFundsException;
use App\Core\Models\PlayerProfile;
use App\Core\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Central economy service.
 * All cash credit/debit operations should go through this service to ensure:
 * - Atomic, race-condition-safe writes (DB transactions + row locking)
 * - Economy events are fired (MoneyCredited, MoneyDebited, MoneyTransferred)
 * - Plugin permission checks are enforced via PluginContext
 *
 * Cash is stored in player_profiles.cash (no longer stored on the users table).
 * Locks are taken on player_profiles rows to prevent race conditions.
 */
class WalletService implements EconomyInterface
{
    /**
     * Credit cash to a user's wallet.
     */
    public function credit(User $user, int $amount, string $reason, string $pluginSlug = 'core'): int
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Credit amount must be positive, got {$amount}.");
        }

        PluginContext::assertPermission('economy.write');

        return DB::transaction(function () use ($user, $amount, $reason, $pluginSlug) {
            PlayerProfile::where('user_id', $user->id)->lockForUpdate()->first();
            PlayerProfile::where('user_id', $user->id)->increment('cash', $amount);
            $newBalance = (int) PlayerProfile::where('user_id', $user->id)->value('cash');

            // Keep the in-memory model fresh so callers see the updated balance
            if ($user->relationLoaded('profile') && $user->profile) {
                $user->profile->cash = $newBalance;
            }

            MoneyCredited::dispatch($user, $amount, $newBalance, $reason, $pluginSlug);

            return $newBalance;
        });
    }

    /**
     * Debit cash from a user's wallet.
     * Throws InsufficientFundsException if the user cannot afford it.
     */
    public function debit(User $user, int $amount, string $reason, string $pluginSlug = 'core'): int
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Debit amount must be positive, got {$amount}.");
        }

        PluginContext::assertPermission('economy.write');

        return DB::transaction(function () use ($user, $amount, $reason, $pluginSlug) {
            $lockedProfile  = PlayerProfile::where('user_id', $user->id)->lockForUpdate()->first();
            $currentBalance = (int) $lockedProfile->cash;

            if ($currentBalance < $amount) {
                throw new InsufficientFundsException($user, $amount, $currentBalance);
            }

            $lockedProfile->decrement('cash', $amount);
            $newBalance = (int) PlayerProfile::where('user_id', $user->id)->value('cash');

            // Keep the in-memory model fresh so callers see the updated balance
            if ($user->relationLoaded('profile') && $user->profile) {
                $user->profile->cash = $newBalance;
            }

            MoneyDebited::dispatch($user, $amount, $newBalance, $reason, $pluginSlug);

            return $newBalance;
        });
    }

    /**
     * Atomically transfer cash between two users.
     * Locks in ascending user ID order to prevent deadlocks.
     */
    public function transfer(User $from, User $to, int $amount, string $reason, string $pluginSlug = 'core'): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Transfer amount must be positive, got {$amount}.");
        }

        PluginContext::assertPermission('economy.write');

        DB::transaction(function () use ($from, $to, $amount, $reason, $pluginSlug) {
            // Lock in consistent user_id order to prevent deadlocks
            $ids = [$from->id, $to->id];
            sort($ids);
            PlayerProfile::whereIn('user_id', $ids)->orderBy('user_id')->lockForUpdate()->get();

            $fromBalance = (int) PlayerProfile::where('user_id', $from->id)->value('cash');
            if ($fromBalance < $amount) {
                throw new InsufficientFundsException($from, $amount, $fromBalance);
            }

            PlayerProfile::where('user_id', $from->id)->decrement('cash', $amount);
            PlayerProfile::where('user_id', $to->id)->increment('cash', $amount);

            MoneyTransferred::dispatch($from, $to, $amount, $reason, $pluginSlug);
        });

        return true;
    }

    /**
     * Get the user's current cash balance (fresh read from DB).
     */
    public function getBalance(User $user): int
    {
        return (int) PlayerProfile::where('user_id', $user->id)->value('cash');
    }

    /**
     * Get the user's current bank balance (fresh read from DB).
     */
    public function getBankBalance(User $user): int
    {
        return (int) PlayerProfile::where('user_id', $user->id)->value('bank');
    }
}
