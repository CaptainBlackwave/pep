<?php

namespace App\Core\Http\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Http\Resources\CommunityVendorResource;
use App\Core\Models\CommunityVendor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommunityVendorAdminController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'published', 'hidden'])],
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CommunityVendor::query();

        if (($validated['status'] ?? 'all') !== 'all') {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('name', 'like', "%{$search}%")
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
                    'trusted' => CommunityVendor::where('status_class', 'trusted')->count(),
                    'caution' => CommunityVendor::where('status_class', 'caution')->count(),
                    'avoid' => CommunityVendor::where('status_class', 'avoid')->count(),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateVendor($request);
        $name = $validated['name'];

        $vendor = CommunityVendor::create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['slug'] ?? $name),
            'logo_initials' => $validated['logo_initials'] ?? $this->initials($name),
            'logo_text' => $validated['logo_text'] ?? $this->initials($name),
            'logo_class' => $validated['logo_class'] ?? 'purple',
            'status_label' => $validated['status_label'] ?? 'Trusted',
            'status_class' => $validated['status_class'] ?? 'trusted',
            'status' => $validated['status'] ?? 'published',
        ]);

        return (new CommunityVendorResource($vendor))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, string $vendor)
    {
        $vendorModel = $this->findVendor($vendor);

        $validated = $this->validateVendor($request, true);

        if (isset($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug($validated['slug'], $vendorModel->id);
        }

        $vendorModel->fill($validated)->save();

        return new CommunityVendorResource($vendorModel);
    }

    public function destroy(string $vendor)
    {
        $vendorModel = $this->findVendor($vendor);
        $vendorModel->forceFill(['status' => 'hidden'])->save();

        return response()->json([
            'success' => true,
            'message' => 'Vendor hidden.',
        ]);
    }

    private function findVendor(string $value): CommunityVendor
    {
        return CommunityVendor::query()
            ->where(function ($query) use ($value) {
                $query->where('slug', $value);

                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->firstOrFail();
    }

    private function validateVendor(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9-]+$/i'],
            'logo_initials' => ['nullable', 'string', 'max:12'],
            'logo_text' => ['nullable', 'string', 'max:80'],
            'logo_class' => ['nullable', 'string', 'max:40'],
            'status_label' => ['nullable', 'string', 'max:40'],
            'status_class' => ['nullable', Rule::in(['trusted', 'caution', 'avoid'])],
            'description' => ['nullable', 'string', 'max:4000'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'member_since' => ['nullable', 'date'],
            'last_active_at' => ['nullable', 'date'],
            'review_count' => ['nullable', 'integer', 'min:0'],
            'average_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'would_buy_again_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'response_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'avg_response_time' => ['nullable', 'string', 'max:80'],
            'tags' => ['nullable', 'array', 'max:12'],
            'tags.*' => ['string', 'max:60'],
            'top_products' => ['nullable', 'array', 'max:20'],
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

    private function initials(string $value): string
    {
        $words = Str::of($value)
            ->replaceMatches('/[^A-Za-z0-9 ]+/', ' ')
            ->squish()
            ->explode(' ')
            ->filter();

        $initials = $words
            ->take(2)
            ->map(fn (string $word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'V';
    }
}
