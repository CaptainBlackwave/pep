<?php

namespace App\Core\Services;

use App\Core\Contracts\EconomyInterface;
use App\Core\Exceptions\InsufficientFundsException;
use App\Core\Models\PlayerProfile;
use App\Core\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WalletService implements EconomyInterface
{
    public function credit(User $user, int $amount, string $reason, string $pluginSlug = 'core'): int
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($user, $amount) {
            $profile = $this->lockedProfileFor($user);
            $profile->cash = (int) $profile->cash + $amount;
            $profile->save();

            return (int) $profile->cash;
        }, 3);
    }

    public function debit(User $user, int $amount, string $reason, string $pluginSlug = 'core'): int
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($user, $amount) {
            $profile = $this->lockedProfileFor($user);
            $available = (int) $profile->cash;

            if ($available < $amount) {
                throw new InsufficientFundsException($user, $amount, $available);
            }

            $profile->cash = $available - $amount;
            $profile->save();

            return (int) $profile->cash;
        }, 3);
    }

    public function transfer(User $from, User $to, int $amount, string $reason, string $pluginSlug = 'core'): bool
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($from, $to, $amount) {
            $this->ensureProfile($from);
            $this->ensureProfile($to);

            $profiles = PlayerProfile::query()
                ->whereIn('user_id', [$from->id, $to->id])
                ->orderBy('user_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('user_id');

            $fromProfile = $profiles->get($from->id);
            $toProfile = $profiles->get($to->id);

            $available = (int) $fromProfile->cash;
            if ($available < $amount) {
                throw new InsufficientFundsException($from, $amount, $available);
            }

            $fromProfile->cash = $available - $amount;
            $toProfile->cash = (int) $toProfile->cash + $amount;
            $fromProfile->save();
            $toProfile->save();

            return true;
        }, 3);
    }

    public function getBalance(User $user): int
    {
        return (int) $this->ensureProfile($user)->cash;
    }

    public function getBankBalance(User $user): int
    {
        return (int) $this->ensureProfile($user)->bank;
    }

    private function assertPositiveAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }
    }

    private function lockedProfileFor(User $user): PlayerProfile
    {
        $this->ensureProfile($user);

        return PlayerProfile::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensureProfile(User $user): PlayerProfile
    {
        return $user->profile()->firstOrCreate([]);
    }
}
