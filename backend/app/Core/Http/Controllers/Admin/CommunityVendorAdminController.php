<?php

namespace App\Core\Http\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Http\Resources\CommunityVendorResource;
use App\Core\Models\CommunityVendor;
use App\Core\Models\User;
use App\Core\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommunityVendorAdminController extends Controller
{
    public function __construct(
        private PushNotificationService $push,
    ) {
    }
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'published', 'hidden'])],
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CommunityVendor::query()->with('owner');

        if (($validated['status'] ?? 'all') !== 'all') {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $limit = (int) ($validated['limit'] ?? 50);

        return CommunityVendorResource::collection(
            $query->orderByDesc('updated_at')->limit($limit)->get()
        )->additional([
            'meta' => [
                'stats' => [
                    'total' => CommunityVendor::count(),
                    'published' => CommunityVendor::where('status', 'published')->count(),
                    'hidden' => CommunityVendor::where('status', 'hidden')->count(),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateVendor($request);

        $name = trim($validated['name']);

        $vendor = CommunityVendor::create([
            ...$validated,
            'owner_user_id' => $validated['owner_user_id'] ?? $request->user()->id,
            'slug' => $this->uniqueSlug($validated['slug'] ?? $name),
            'logo_initials' => $this->initials($name),
            'logo_text' => $this->initials($name),
            'logo_class' => 'purple',
            'claim_status' => 'verified',
            'status_label' => 'Trusted',
            'status_class' => 'trusted',
            'status' => $validated['status'] ?? 'published',
            'member_since' => $validated['member_since'] ?? now()->toDateString(),
            'last_active_at' => now(),
        ]);

        if (($validated['status'] ?? 'published') === 'published') {
            $this->push->notifyAllPremium(
                'vendor_added',
                "New vendor: {$vendor->name}",
                "A new vendor has been added to the community.",
                "/vendors/{$vendor->slug}",
            );
        }

        return (new CommunityVendorResource($vendor->load('owner')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, string $vendor)
    {
        $vendorModel = CommunityVendor::with('owner')->findOrFail($vendor);
        $validated = $this->validateVendor($request, true);

        if (isset($validated['slug']) || isset($validated['name'])) {
            $validated['slug'] = $this->uniqueSlug(
                $validated['slug'] ?? $validated['name'] ?? $vendorModel->slug,
                $vendorModel->id
            );
        }

        $vendorModel->fill($validated)->save();

        return new CommunityVendorResource($vendorModel->fresh()->load('owner'));
    }

    public function destroy(string $vendor)
    {
        $vendorModel = CommunityVendor::findOrFail($vendor);
        $vendorModel->forceFill(['status' => 'hidden'])->save();

        return response()->json([
            'success' => true,
            'message' => 'Vendor hidden.',
        ]);
    }

    private function validateVendor(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => [$required, 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:50000'],
            'country' => ['nullable', 'string', 'max:100'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_telegram' => ['nullable', 'string', 'max:120'],
            'contact_signal' => ['nullable', 'string', 'max:120'],
            'contact_discord' => ['nullable', 'string', 'max:120'],
            'support_url' => ['nullable', 'url', 'max:255'],
            'response_policy' => ['nullable', 'string', 'max:5000'],
            'public_contact_notes' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'array'],
            'top_products' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(['published', 'hidden'])],
        ]);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'vendor';
        $slug = $base;
        $suffix = 2;

        while (CommunityVendor::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function initials(string $name): string
    {
        $parts = explode(' ', $name, 2);

        return strtoupper(($parts[0][0] ?? '') . ($parts[1][0] ?? $parts[0][1] ?? ''));
    }
}
