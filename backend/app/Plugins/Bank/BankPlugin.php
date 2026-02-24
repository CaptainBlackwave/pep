<?php

namespace App\Plugins\Bank;

use App\Plugins\Plugin;
use App\Core\Contracts\PluginInterface;
use App\Core\Contracts\EconomyInterface;
use Illuminate\Support\Facades\DB;

/**
 * Bank Plugin
 *
 * Reference implementation demonstrating economy integration.
 * Manages deposits, withdrawals, and transfers.
 */
class BankPlugin extends Plugin implements PluginInterface
{
    /**
     * Plugin constructor.
     */
    public function __construct()
    {
        parent::__construct(app_path('Plugins/Bank'));
    }

    // ==========================================
    // PluginInterface Implementation
    // ==========================================

    /**
     * Register the plugin's services.
     */
    public function register(): void
    {
        $this->app->singleton('bank.service', function ($app) {
            return new Services\BankService();
        });
    }

    /**
     * Boot the plugin's functionality.
     */
    public function boot(): void
    {
        $this->registerHooks();
    }

    // ==========================================
    // PluginLifecycleInterface Implementation
    // ==========================================

    public function install(): void
    {
        $this->log('info', 'Bank plugin installed');
    }

    public function enable(): void
    {
        $this->log('info', 'Bank plugin enabled');
    }

    public function disable(): void
    {
        $this->log('info', 'Bank plugin disabled');
    }

    public function uninstall(): void
    {
        \App\Core\Models\PluginMetadata::where('plugin_id', 'bank')->delete();
        $this->log('info', 'Bank plugin uninstalled');
    }

    public function upgrade(string $fromVersion, string $toVersion): void
    {
        $this->log('info', "Bank upgraded from {$fromVersion} to {$toVersion}");
    }

    // ==========================================
    // Bank Methods
    // ==========================================

    /**
     * Deposit cash into the bank.
     */
    public function deposit($user, int $amount): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid amount'];
        }

        $economy = app(EconomyInterface::class);

        if ($economy->getBalance($user) < $amount) {
            return ['success' => false, 'message' => 'Insufficient funds'];
        }

        return DB::transaction(function () use ($user, $amount, $economy) {
            // Debit from cash
            $economy->debit($user, $amount, 'Bank deposit', 'bank');

            // Credit to bank (stored in metadata)
            $currentBank = $user->getPluginMeta('bank', 'balance', 0);
            $newBank = $currentBank + $amount;
            $user->setPluginMeta('bank', 'balance', $newBank);

            // Track transaction count
            $user->incrementPluginMeta('bank', 'total_deposits', 1);
            $user->incrementPluginMeta('bank', 'total_deposited', $amount);

            // Log transaction
            $this->logTransaction($user, 'deposit', $amount);

            // Broadcast
            broadcastToPluginUser($user->id, 'bank', 'balance_updated', [
                'bank_balance' => $newBank,
                'cash_balance' => $economy->getBalance($user),
            ]);

            return [
                'success' => true,
                'message' => "Deposited \${$amount}",
                'bank_balance' => $newBank,
                'cash_balance' => $economy->getBalance($user),
            ];
        });
    }

    /**
     * Withdraw cash from the bank.
     */
    public function withdraw($user, int $amount): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid amount'];
        }

        $currentBank = $user->getPluginMeta('bank', 'balance', 0);

        if ($currentBank < $amount) {
            return ['success' => false, 'message' => 'Insufficient bank balance'];
        }

        $economy = app(EconomyInterface::class);

        return DB::transaction(function () use ($user, $amount, $economy, $currentBank) {
            // Debit from bank
            $newBank = $currentBank - $amount;
            $user->setPluginMeta('bank', 'balance', $newBank);

            // Credit to cash
            $economy->credit($user, $amount, 'Bank withdrawal', 'bank');

            // Track transaction count
            $user->incrementPluginMeta('bank', 'total_withdrawals', 1);
            $user->incrementPluginMeta('bank', 'total_withdrawn', $amount);

            // Log transaction
            $this->logTransaction($user, 'withdrawal', $amount);

            // Broadcast
            broadcastToPluginUser($user->id, 'bank', 'balance_updated', [
                'bank_balance' => $newBank,
                'cash_balance' => $economy->getBalance($user),
            ]);

            return [
                'success' => true,
                'message' => "Withdrew \${$amount}",
                'bank_balance' => $newBank,
                'cash_balance' => $economy->getBalance($user),
            ];
        });
    }

    /**
     * Transfer cash to another user.
     */
    public function transfer($from, $toId, int $amount): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid amount'];
        }

        $to = \App\Core\Models\User::find($toId);
        if (!$to) {
            return ['success' => false, 'message' => 'Recipient not found'];
        }

        if ($from->id === $to->id) {
            return ['success' => false, 'message' => 'Cannot transfer to yourself'];
        }

        $fromBank = $from->getPluginMeta('bank', 'balance', 0);
        if ($fromBank < $amount) {
            return ['success' => false, 'message' => 'Insufficient bank balance'];
        }

        return DB::transaction(function () use ($from, $to, $amount, $fromBank) {
            // Debit from sender
            $newFromBank = $fromBank - $amount;
            $from->setPluginMeta('bank', 'balance', $newFromBank);

            // Credit to recipient
            $toBank = $to->getPluginMeta('bank', 'balance', 0);
            $to->setPluginMeta('bank', 'balance', $toBank + $amount);

            // Track transfers
            $from->incrementPluginMeta('bank', 'total_transfers_sent', 1);
            $from->incrementPluginMeta('bank', 'total_transferred', $amount);
            $to->incrementPluginMeta('bank', 'total_transfers_received', 1);

            // Log transactions
            $this->logTransaction($from, 'transfer_out', $amount, $to->id);
            $this->logTransaction($to, 'transfer_in', $amount, $from->id);

            // Broadcast to sender
            broadcastToPluginUser($from->id, 'bank', 'balance_updated', [
                'bank_balance' => $newFromBank,
            ]);

            // Broadcast to recipient
            broadcastToPluginUser($to->id, 'bank', 'transfer_received', [
                'amount' => $amount,
                'from' => $from->username,
            ]);

            return [
                'success' => true,
                'message' => "Transferred \${$amount} to {$to->username}",
                'bank_balance' => $newFromBank,
            ];
        });
    }

    /**
     * Get user's bank balance.
     */
    public function getBalance($user): int
    {
        return $user->getPluginMeta('bank', 'balance', 0);
    }

    /**
     * Get user's bank statistics.
     */
    public function getStats($user): array
    {
        return [
            'balance' => $user->getPluginMeta('bank', 'balance', 0),
            'total_deposits' => $user->getPluginMeta('bank', 'total_deposits', 0),
            'total_deposited' => $user->getPluginMeta('bank', 'total_deposited', 0),
            'total_withdrawals' => $user->getPluginMeta('bank', 'total_withdrawals', 0),
            'total_withdrawn' => $user->getPluginMeta('bank', 'total_withdrawn', 0),
            'total_transfers_sent' => $user->getPluginMeta('bank', 'total_transfers_sent', 0),
            'total_transfers_received' => $user->getPluginMeta('bank', 'total_transfers_received', 0),
        ];
    }

    /**
     * Log a bank transaction.
     */
    protected function logTransaction($user, string $type, int $amount, ?int $relatedUserId = null): void
    {
        $history = $user->getPluginMeta('bank', 'history', []);

        array_unshift($history, [
            'type' => $type,
            'amount' => $amount,
            'related_user_id' => $relatedUserId,
            'timestamp' => now()->toIso8601String(),
        ]);

        // Keep only last 50 transactions
        $history = array_slice($history, 0, 50);

        $user->setPluginMeta('bank', 'history', $history);
    }

    /**
     * Get transaction history.
     */
    public function getHistory($user, int $limit = 20): array
    {
        return array_slice($user->getPluginMeta('bank', 'history', []), 0, $limit);
    }
}
