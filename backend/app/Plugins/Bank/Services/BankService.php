<?php

namespace App\Plugins\Bank\Services;

use App\Core\Exceptions\InsufficientFundsException;
use App\Core\Models\PlayerProfile;
use App\Core\Models\User;
use App\Core\Services\WalletService;
use Illuminate\Support\Facades\DB;

class BankService
{
    /**
     * Bank tax percentage (15% default from legacy)
     */
    protected int $taxRate = 15;

    public function __construct(protected WalletService $wallet) {}

    /**
     * Deposit cash into bank (with tax).
     * Locks the player_profiles row — cash and bank both live there.
     */
    public function deposit(User $player, int $amount): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'message' => "You can't deposit negative cash."];
        }

        return DB::transaction(function () use ($player, $amount) {
            $profile = PlayerProfile::where('user_id', $player->id)->lockForUpdate()->firstOrFail();

            if ($profile->cash < $amount) {
                return ['success' => false, 'message' => "You don't have enough money for this transaction!"];
            }

            $taxMultiplier   = (100 - $this->taxRate) / 100;
            $depositedAmount = (int) ($amount * $taxMultiplier);
            $taxAmount       = $amount - $depositedAmount;

            $profile->cash -= $amount;
            $profile->bank += $depositedAmount;
            $profile->save();

            return [
                'success'   => true,
                'message'   => "You sent $" . number_format($amount) . " to your money launderer. He deposited $" . number_format($depositedAmount) . " into your bank account!",
                'amount'    => $amount,
                'deposited' => $depositedAmount,
                'tax'       => $taxAmount,
            ];
        });
    }

    /**
     * Withdraw cash from bank (no tax).
     */
    public function withdraw(User $player, int $amount): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'message' => "You can't withdraw negative cash."];
        }

        return DB::transaction(function () use ($player, $amount) {
            $profile = PlayerProfile::where('user_id', $player->id)->lockForUpdate()->firstOrFail();

            if ($profile->bank < $amount) {
                return ['success' => false, 'message' => "You don't have enough money in your bank for this transaction!"];
            }

            $profile->bank -= $amount;
            $profile->cash += $amount;
            $profile->save();

            return [
                'success' => true,
                'message' => "You have withdrawn $" . number_format($amount) . "!",
                'amount'  => $amount,
            ];
        });
    }

    /**
     * Transfer cash between two players via WalletService (fires economy events,
     * locks player_profiles in ID order to prevent deadlocks).
     */
    public function transfer(User $sender, string $recipientUsername, int $amount): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'message' => "How much money do you want to send?"];
        }

        $recipient = User::where('username', $recipientUsername)->first();

        if (!$recipient) {
            return ['success' => false, 'message' => "This user does not exist."];
        }

        if ($recipient->id === $sender->id) {
            return ['success' => false, 'message' => "You can't send money to yourself!"];
        }

        try {
            $this->wallet->transfer($sender, $recipient, $amount, 'player_transfer', 'bank');
        } catch (InsufficientFundsException $e) {
            return ['success' => false, 'message' => "You don't have that much money."];
        }

        app(\App\Core\Services\NotificationService::class)->moneyReceived($recipient, $sender, $amount);

        return [
            'success'   => true,
            'message'   => "You have sent $" . number_format($amount) . " to {$recipient->username}!",
            'amount'    => $amount,
            'recipient' => $recipient->username,
        ];
    }

    /**
     * Get bank tax rate
     */
    public function getTaxRate(): int
    {
        return $this->taxRate;
    }

    /**
     * Calculate deposit amount after tax
     */
    public function calculateDepositAmount(int $amount): array
    {
        $taxMultiplier = (100 - $this->taxRate) / 100;
        $depositedAmount = (int)($amount * $taxMultiplier);
        $taxAmount = $amount - $depositedAmount;

        return [
            'original' => $amount,
            'deposited' => $depositedAmount,
            'tax' => $taxAmount,
            'tax_rate' => $this->taxRate,
        ];
    }
}
