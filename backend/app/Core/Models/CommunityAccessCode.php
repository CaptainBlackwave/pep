<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;

class CommunityAccessCode extends Model
{
    protected $fillable = [
        'code_hash',
        'label',
        'created_by_user_id',
        'used_by_user_id',
        'used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $appends = [
        'status',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where(function (Builder $inner) {
                $inner->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function getStatusAttribute(): string
    {
        if ($this->used_at) {
            return 'used';
        }

        if ($this->revoked_at) {
            return 'revoked';
        }

        if ($this->expires_at && $this->expires_at->lte(now())) {
            return 'expired';
        }

        return 'available';
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function markUsedBy(User $user): void
    {
        $this->forceFill([
            'used_by_user_id' => $user->id,
            'used_at' => now(),
        ])->save();
    }

    public static function normalizeCode(string $code): string
    {
        return preg_replace('/\s+/', '', strtoupper(trim($code))) ?? '';
    }

    public static function hashCode(string $code): string
    {
        return hash('sha256', static::normalizeCode($code));
    }

    public static function generatePlainCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $groups = [];

        for ($group = 0; $group < 3; $group++) {
            $chunk = '';

            for ($index = 0; $index < 4; $index++) {
                $chunk .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $groups[] = $chunk;
        }

        return 'PV-' . implode('-', $groups);
    }

    public static function createFresh(?User $creator = null, ?string $label = null, mixed $expiresAt = null): array
    {
        do {
            $plainCode = static::generatePlainCode();
            $hash = static::hashCode($plainCode);
        } while (static::query()->where('code_hash', $hash)->exists());

        $accessCode = static::create([
            'code_hash' => $hash,
            'label' => $label,
            'created_by_user_id' => $creator?->id,
            'expires_at' => $expiresAt,
        ]);

        return [$accessCode, $plainCode];
    }

    public static function isUsableCode(string $code): bool
    {
        try {
            return static::query()
                ->available()
                ->where('code_hash', static::hashCode($code))
                ->exists();
        } catch (QueryException) {
            return false;
        }
    }
}
