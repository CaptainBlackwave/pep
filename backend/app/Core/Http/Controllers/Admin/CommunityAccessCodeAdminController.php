<?php

namespace App\Core\Http\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Models\CommunityAccessCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunityAccessCodeAdminController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'available', 'used', 'revoked', 'expired'])],
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CommunityAccessCode::query()
            ->with(['creator', 'usedBy'])
            ->latest();

        $this->applyStatusFilter($query, $validated['status'] ?? 'all');

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where('label', 'like', "%{$search}%");
        }

        $limit = (int) ($validated['limit'] ?? 50);
        $codes = $query->limit($limit)->get();

        return response()->json([
            'data' => $codes->map(fn (CommunityAccessCode $accessCode) => $this->transform($accessCode))->values(),
            'meta' => [
                'stats' => $this->stats(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:160'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        [$accessCode, $plainCode] = CommunityAccessCode::createFresh(
            $request->user(),
            $validated['label'] ?? null,
            $validated['expires_at'] ?? null,
        );

        return response()->json([
            'data' => [
                ...$this->transform($accessCode->load(['creator', 'usedBy'])),
                'code' => $plainCode,
            ],
            'message' => 'Access code generated. Copy it now; it will not be shown again.',
        ], 201);
    }

    public function destroy(CommunityAccessCode $accessCode)
    {
        if ($accessCode->used_at) {
            return response()->json([
                'message' => 'Used access codes cannot be revoked.',
            ], 422);
        }

        $accessCode->forceFill([
            'revoked_at' => $accessCode->revoked_at ?? now(),
        ])->save();

        return response()->json([
            'data' => $this->transform($accessCode->fresh(['creator', 'usedBy'])),
            'message' => 'Access code revoked.',
        ]);
    }

    private function applyStatusFilter($query, string $status): void
    {
        match ($status) {
            'available' => $query->available(),
            'used' => $query->whereNotNull('used_at'),
            'revoked' => $query->whereNull('used_at')->whereNotNull('revoked_at'),
            'expired' => $query
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()),
            default => null,
        };
    }

    private function stats(): array
    {
        return [
            'total' => CommunityAccessCode::count(),
            'available' => CommunityAccessCode::query()->available()->count(),
            'used' => CommunityAccessCode::whereNotNull('used_at')->count(),
            'revoked' => CommunityAccessCode::whereNull('used_at')->whereNotNull('revoked_at')->count(),
            'expired' => CommunityAccessCode::whereNull('used_at')
                ->whereNull('revoked_at')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->count(),
        ];
    }

    private function transform(?CommunityAccessCode $accessCode): array
    {
        return [
            'id' => $accessCode?->id,
            'label' => $accessCode?->label,
            'status' => $accessCode?->status,
            'created_at' => $accessCode?->created_at?->toISOString(),
            'expires_at' => $accessCode?->expires_at?->toISOString(),
            'used_at' => $accessCode?->used_at?->toISOString(),
            'revoked_at' => $accessCode?->revoked_at?->toISOString(),
            'created_by' => $accessCode?->creator ? [
                'id' => $accessCode->creator->id,
                'username' => $accessCode->creator->username,
            ] : null,
            'used_by' => $accessCode?->usedBy ? [
                'id' => $accessCode->usedBy->id,
                'username' => $accessCode->usedBy->username,
            ] : null,
        ];
    }
}
