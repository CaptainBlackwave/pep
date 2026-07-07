<?php

namespace App\Plugins\Vendors\Controllers\Admin;

use App\Core\Models\CommunityVendorClaim;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunityVendorClaimAdminController
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'pending', 'approved', 'rejected'])],
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CommunityVendorClaim::query()->with(['vendor', 'user']);

        if (($validated['status'] ?? 'pending') !== 'all') {
            $query->where('status', $validated['status'] ?? 'pending');
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('message', 'like', "%{$search}%")
                    ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('username', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $limit = (int) ($validated['limit'] ?? 50);

        return response()->json([
            'data' => $query->latest()->limit($limit)->get()->map(fn (CommunityVendorClaim $claim) => $this->claimPayload($claim))->values(),
            'meta' => [
                'stats' => [
                    'total' => CommunityVendorClaim::count(),
                    'pending' => CommunityVendorClaim::where('status', 'pending')->count(),
                    'approved' => CommunityVendorClaim::where('status', 'approved')->count(),
                    'rejected' => CommunityVendorClaim::where('status', 'rejected')->count(),
                ],
            ],
        ]);
    }

    public function update(Request $request, string $claim)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        $claimModel = CommunityVendorClaim::query()
            ->with(['vendor', 'user'])
            ->findOrFail($claim);

        if ($validated['status'] === 'approved') {
            $this->approve($claimModel);
        } else {
            $this->reject($claimModel);
        }

        return response()->json([
            'data' => $this->claimPayload($claimModel->refresh()->load(['vendor', 'user'])),
        ]);
    }

    private function approve(CommunityVendorClaim $claim): void
    {
        $vendor = $claim->vendor;

        abort_if(!$vendor, 404, 'Vendor profile not found.');
        abort_if(
            $vendor->owner_user_id !== null && (int) $vendor->owner_user_id !== (int) $claim->user_id,
            409,
            'This vendor profile is already controlled by another account.'
        );

        $claim->forceFill([
            'status' => 'approved',
            'reviewed_at' => now(),
        ])->save();

        $claim->user?->forceFill([
            'is_approved_vendor' => true,
        ])->save();

        $statusLabel = $vendor->status_label;
        $statusClass = $vendor->status_class;

        if (!$statusLabel || $statusLabel === 'Pending Verification') {
            $statusLabel = 'Listed';
            $statusClass = $statusClass ?: 'caution';
        }

        $vendor->forceFill([
            'owner_user_id' => $claim->user_id,
            'claim_status' => 'verified',
            'status' => 'published',
            'status_label' => $statusLabel,
            'status_class' => $statusClass ?: 'caution',
            'member_since' => $vendor->member_since ?: now()->toDateString(),
            'last_active_at' => now(),
        ])->save();

        CommunityVendorClaim::query()
            ->where('vendor_id', $vendor->id)
            ->whereKeyNot($claim->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
            ]);
    }

    private function reject(CommunityVendorClaim $claim): void
    {
        $claim->forceFill([
            'status' => 'rejected',
            'reviewed_at' => now(),
        ])->save();

        $vendor = $claim->vendor;
        if (!$vendor || $vendor->claim_status === 'verified') {
            return;
        }

        $hasPendingClaims = CommunityVendorClaim::query()
            ->where('vendor_id', $vendor->id)
            ->where('status', 'pending')
            ->exists();

        $vendor->forceFill([
            'claim_status' => $hasPendingClaims ? 'pending' : 'unclaimed',
            'status' => $vendor->status === 'hidden' ? 'hidden' : $vendor->status,
        ])->save();
    }

    private function claimPayload(CommunityVendorClaim $claim): array
    {
        return [
            'id' => $claim->id,
            'status' => $claim->status,
            'message' => $claim->message,
            'created_at' => $claim->created_at?->toIso8601String(),
            'reviewed_at' => $claim->reviewed_at?->toIso8601String(),
            'user' => $claim->user ? [
                'id' => $claim->user->id,
                'name' => $claim->user->username ?: $claim->user->name,
                'email' => $claim->user->email,
            ] : null,
            'vendor' => $claim->vendor ? [
                'id' => $claim->vendor->id,
                'name' => $claim->vendor->name,
                'slug' => $claim->vendor->slug,
                'status' => $claim->vendor->status,
                'claim_status' => $claim->vendor->claim_status,
                'owner_user_id' => $claim->vendor->owner_user_id,
                'description' => $claim->vendor->description,
                'website_url' => $claim->vendor->website_url,
                'image_url' => $claim->vendor->image_url,
                'href' => "/vendor-reviews/{$claim->vendor->slug}",
            ] : null,
        ];
    }
}
