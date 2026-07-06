<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\CommunityVendorDocumentResource;
use App\Core\Http\Resources\CommunityVendorResource;
use App\Core\Http\Resources\CommunityVendorProductResource;
use App\Core\Http\Resources\CommunityVendorReviewResource;
use App\Core\Models\CommunityVendor;
use App\Core\Models\CommunityVendorClaim;
use App\Core\Models\CommunityVendorDocument;
use App\Core\Models\CommunityVendorProduct;
use App\Core\Models\CommunityVendorReview;
use App\Core\Models\User;
use App\Core\Services\CommunityNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommunityVendorController extends Controller
{
    public function __construct(private CommunityNotificationService $notifications)
    {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9_-]+$/i'],
            'rating_min' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'tag' => ['nullable', 'string', 'max:80'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = CommunityVendor::query()->where('status', 'published');

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('publishedProducts', function ($products) use ($search) {
                        $products
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%")
                            ->orWhere('strength', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($validated['status'])) {
            $query->where('status_class', $validated['status']);
        }

        if (!empty($validated['rating_min'])) {
            $query->where('average_rating', '>=', (float) $validated['rating_min']);
        }

        if (!empty($validated['tag'])) {
            $query->whereJsonContains('tags', $validated['tag']);
        }

        $limit = (int) ($validated['limit'] ?? 25);

        $vendors = $query->orderByDesc('average_rating')->orderByDesc('review_count')->paginate($limit)->withQueryString();
        $this->hydrateRatingDistributions($vendors->getCollection());

        return CommunityVendorResource::collection($vendors)->additional([
            'meta' => [
                'stats' => $this->stats(),
                'top_vendors' => CommunityVendorResource::collection($this->topVendors()),
                'filters' => $this->filters(),
            ],
        ]);
    }

    public function show(string $vendor)
    {
        $vendorModel = $this->findPublishedVendor($vendor)
            ->load([
                'publishedReviews' => fn ($query) => $query->with('user')->latest('reviewed_at')->latest(),
                'publishedProducts' => fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
                'publishedDocuments',
            ]);
        $this->hydrateRatingDistributions(collect([$vendorModel]));

        return new CommunityVendorResource($vendorModel);
    }

    public function myVendorProfile(Request $request)
    {
        $vendor = CommunityVendor::query()
            ->with([
                'products' => fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
                'documents',
            ])
            ->where('owner_user_id', $request->user()->id)
            ->latest('updated_at')
            ->first();
        $claims = CommunityVendorClaim::query()
            ->with('vendor')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (CommunityVendorClaim $claim) => [
                'id' => $claim->id,
                'status' => $claim->status,
                'message' => $claim->message,
                'vendor' => $claim->vendor ? [
                    'id' => $claim->vendor->id,
                    'name' => $claim->vendor->name,
                    'slug' => $claim->vendor->slug,
                    'href' => "/vendor-reviews/{$claim->vendor->slug}",
                ] : null,
                'created_at' => $claim->created_at?->toIso8601String(),
                'reviewed_at' => $claim->reviewed_at?->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'data' => $vendor ? (new CommunityVendorResource($vendor))->resolve($request) : null,
            'claims' => $claims,
            'is_approved_vendor' => $this->userIsApprovedVendor($request->user()),
            'can_create_vendor_profile' => $this->userIsApprovedVendor($request->user()) && !$vendor,
        ]);
    }

    public function storeVendorProfile(Request $request)
    {
        abort_unless(
            $this->userIsApprovedVendor($request->user()),
            403,
            'An admin must approve this account as a vendor before you can create a vendor profile.'
        );

        abort_if(
            !$request->user()->hasRole('admin')
            && CommunityVendor::where('owner_user_id', $request->user()->id)->exists(),
            422,
            'This account already has a vendor profile.'
        );

        $validated = $this->validateVendorProfile($request);
        $name = trim($validated['name']);

        $vendor = CommunityVendor::create([
            ...$validated,
            'owner_user_id' => $request->user()->id,
            'claim_status' => 'verified',
            'slug' => $this->uniqueSlug($validated['slug'] ?? $name),
            'logo_initials' => $this->initials($name),
            'logo_text' => $this->initials($name),
            'logo_class' => 'purple',
            'status_label' => 'Trusted',
            'status_class' => 'trusted',
            'status' => 'published',
            'member_since' => now()->toDateString(),
            'last_active_at' => now(),
            'profile_submitted_at' => now(),
        ]);

        return (new CommunityVendorResource($vendor))
            ->response()
            ->setStatusCode(201);
    }

    public function updateVendorProfile(Request $request)
    {
        $vendor = CommunityVendor::query()
            ->where('owner_user_id', $request->user()->id)
            ->firstOrFail();

        abort_unless(
            $this->userIsApprovedVendor($request->user()),
            403,
            'An admin must approve this account as a vendor before you can edit a vendor profile.'
        );

        $validated = $this->validateVendorProfile($request, true, $vendor->id);

        if (isset($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug($validated['slug'], $vendor->id);
        }

        $vendor->fill([
            ...$validated,
            'claim_status' => 'verified',
            'status' => 'published',
            'status_label' => $vendor->status_label === 'Pending Verification'
                ? 'Trusted'
                : ($vendor->status_label ?: 'Trusted'),
            'status_class' => $vendor->status_class ?: 'caution',
            'last_active_at' => now(),
            'profile_submitted_at' => now(),
        ])->save();

        return new CommunityVendorResource($vendor);
    }

    public function uploadVendorImage(Request $request)
    {
        abort_unless(
            $this->userIsApprovedVendor($request->user()),
            403,
            'An admin must approve this account as a vendor before you can upload a vendor image.'
        );

        $validated = $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $path = $validated['image']->store('vendor-images', 'public');
        $url = $this->publicStorageUrl($request, $path);

        $vendor = CommunityVendor::query()
            ->where('owner_user_id', $request->user()->id)
            ->latest('updated_at')
            ->first();

        if ($vendor) {
            $vendor->forceFill([
                'image_url' => $url,
                'last_active_at' => now(),
            ])->save();

            return (new CommunityVendorResource($vendor->refresh()))
                ->additional(['image_url' => $url]);
        }

        return response()->json([
            'image_url' => $url,
            'data' => null,
        ], 201);
    }

    public function storeVendorProduct(Request $request)
    {
        $vendor = $this->ownedVendor($request);
        $validated = $this->validateVendorProduct($request, $vendor);
        $imageUrl = $this->storeProductImageIfPresent($request);
        $name = trim($validated['name']);

        unset($validated['image']);

        $product = $vendor->products()->create([
            ...$validated,
            'name' => $name,
            'slug' => $this->uniqueProductSlug($vendor, $validated['slug'] ?? $name),
            'image_url' => $imageUrl ?? ($validated['image_url'] ?? null),
            'currency_code' => strtoupper($validated['currency_code'] ?? 'USD'),
            'availability' => $validated['availability'] ?? 'in_stock',
            'status' => $validated['status'] ?? 'published',
        ]);

        $this->refreshVendorProductSnapshots($vendor);

        return (new CommunityVendorProductResource($product->load('vendor')))
            ->response()
            ->setStatusCode(201);
    }

    public function updateVendorProduct(Request $request, string $product)
    {
        $vendor = $this->ownedVendor($request);
        $productModel = $vendor->products()->whereKey($product)->firstOrFail();
        $validated = $this->validateVendorProduct($request, $vendor, true, $productModel->id);
        $imageUrl = $this->storeProductImageIfPresent($request);

        unset($validated['image']);

        if (isset($validated['name'])) {
            $validated['name'] = trim($validated['name']);
        }

        if (isset($validated['slug'])) {
            $validated['slug'] = $this->uniqueProductSlug($vendor, $validated['slug'], $productModel->id);
        }

        if (isset($validated['currency_code'])) {
            $validated['currency_code'] = strtoupper($validated['currency_code']);
        }

        if ($imageUrl) {
            $validated['image_url'] = $imageUrl;
        }

        $productModel->fill($validated)->save();
        $vendor->forceFill(['last_active_at' => now()])->save();
        $this->refreshVendorProductSnapshots($vendor);

        return new CommunityVendorProductResource($productModel->refresh()->load('vendor'));
    }

    public function destroyVendorProduct(Request $request, string $product)
    {
        $vendor = $this->ownedVendor($request);
        $productModel = $vendor->products()->whereKey($product)->firstOrFail();
        $productModel->delete();
        $this->refreshVendorProductSnapshots($vendor);

        return response()->json(['message' => 'Product removed.']);
    }

    public function claimVendor(Request $request, string $vendor)
    {
        $vendorModel = $this->findPublishedVendor($vendor);

        if ((int) $vendorModel->owner_user_id === (int) $request->user()->id) {
            return new CommunityVendorResource($vendorModel);
        }

        abort_if($vendorModel->owner_user_id !== null, 409, 'This vendor profile is already controlled.');

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $claim = CommunityVendorClaim::updateOrCreate(
            [
                'vendor_id' => $vendorModel->id,
                'user_id' => $request->user()->id,
            ],
            [
                'message' => $validated['message'] ?? null,
                'status' => 'pending',
                'reviewed_at' => null,
            ],
        );

        $vendorModel->forceFill(['claim_status' => 'pending'])->save();

        return response()->json([
            'message' => 'Claim request submitted.',
            'data' => [
                'id' => $claim->id,
                'status' => $claim->status,
                'vendor' => [
                    'id' => $vendorModel->id,
                    'name' => $vendorModel->name,
                    'slug' => $vendorModel->slug,
                    'href' => "/vendor-reviews/{$vendorModel->slug}",
                ],
            ],
        ], 201);
    }

    public function storeReview(Request $request, string $vendor)
    {
        $vendorModel = $this->findPublishedVendor($vendor);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:4000'],
            'product_name' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:60'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'max:5120'],
            'would_buy_again' => ['nullable', 'boolean'],
        ]);

        $photoUrls = collect($request->file('photos', []))
            ->map(fn ($file) => $this->publicStorageUrl($request, $file->store('vendor-review-photos', 'public')))
            ->values()
            ->all();

        unset($validated['photos']);

        $user = $request->user();
        $review = CommunityVendorReview::create([
            ...$validated,
            'vendor_id' => $vendorModel->id,
            'user_id' => $user?->id,
            'author_name' => null,
            'status' => 'published',
            'is_verified_buyer' => false,
            'photo_urls' => $photoUrls !== [] ? $photoUrls : null,
            'reviewed_at' => now()->toDateString(),
        ]);

        $this->refreshVendorSnapshot($vendorModel);
        $this->notifications->syncVendorReview($review);

        return (new CommunityVendorReviewResource($review->load(['user', 'vendor'])))
            ->response()
            ->setStatusCode(201);
    }

    public function respondToReview(Request $request, CommunityVendorReview $review)
    {
        $vendor = CommunityVendor::query()
            ->where('owner_user_id', $request->user()->id)
            ->firstOrFail();

        abort_unless((int) $review->vendor_id === (int) $vendor->id, 403, 'You do not own the vendor associated with this review.');

        $validated = $request->validate([
            'vendor_response' => ['required', 'string', 'max:2000'],
        ]);

        $review->update([
            'vendor_response' => $validated['vendor_response'],
            'responded_at' => now(),
        ]);

        return new CommunityVendorReviewResource($review->load('user'));
    }

    public function markReviewHelpful(string $review)
    {
        $reviewModel = CommunityVendorReview::query()
            ->with('user')
            ->where('status', 'published')
            ->whereKey($review)
            ->firstOrFail();

        $reviewModel->increment('helpful_count');
        $reviewModel->refresh()->load('user');

        return new CommunityVendorReviewResource($reviewModel);
    }

    public function storeVendorDocument(Request $request)
    {
        $vendor = $this->ownedVendor($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'file' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg,gif,webp', 'max:10240'],
            'category' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('file');
        $path = $file->store('vendor-documents', 'public');

        $document = $vendor->documents()->create([
            'title' => $validated['title'],
            'file_path' => $path,
            'file_type' => in_array($file->getMimeType(), ['image/png', 'image/jpeg', 'image/gif', 'image/webp']) ? 'image' : 'pdf',
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => 'published',
        ]);

        $vendor->forceFill(['last_active_at' => now()])->save();

        return (new CommunityVendorDocumentResource($document))
            ->response()
            ->setStatusCode(201);
    }

    public function destroyVendorDocument(Request $request, CommunityVendorDocument $document)
    {
        $vendor = $this->ownedVendor($request);

        abort_if((int) $document->vendor_id !== (int) $vendor->id, 403, 'This document does not belong to your vendor profile.');

        $document->forceFill(['status' => 'hidden'])->save();

        $vendor->forceFill(['last_active_at' => now()])->save();

        return response()->json(['message' => 'Document removed.']);
    }

    private function findPublishedVendor(string $value): CommunityVendor
    {
        return CommunityVendor::query()
            ->where('status', 'published')
            ->where(function ($query) use ($value) {
                $query->where('slug', $value);

                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->firstOrFail();
    }

    private function validateVendorProfile(Request $request, bool $partial = false, ?int $ignoreVendorId = null): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9-]+$/i', Rule::unique('community_vendors', 'slug')->ignore($ignoreVendorId)],
            'description' => ['nullable', 'string', 'max:4000'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_telegram' => ['nullable', 'string', 'max:120'],
            'contact_signal' => ['nullable', 'string', 'max:120'],
            'contact_discord' => ['nullable', 'string', 'max:120'],
            'support_url' => ['nullable', 'url', 'max:255'],
            'response_policy' => ['nullable', 'string', 'max:1000'],
            'public_contact_notes' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array', 'max:12'],
            'tags.*' => ['string', 'max:60'],
        ]);
    }

    private function validateVendorProduct(Request $request, CommunityVendor $vendor, bool $partial = false, ?int $ignoreProductId = null): array
    {
        // Decode JSON string variants sent from FormData
        if ($request->has('variants') && is_string($request->input('variants'))) {
            $decoded = json_decode($request->input('variants'), true);
            if (is_array($decoded)) {
                $request->merge(['variants' => $decoded]);
            }
        }

        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:160'],
            'slug' => [
                'nullable',
                'string',
                'max:180',
                'regex:/^[a-z0-9-]+$/i',
                Rule::unique('community_vendor_products', 'slug')
                    ->where(fn ($query) => $query->where('vendor_id', $vendor->id))
                    ->ignore($ignoreProductId),
            ],
            'category' => ['nullable', 'string', 'max:80'],
            'strength' => ['nullable', 'string', 'max:80'],
            'package_size' => ['nullable', 'string', 'max:80'],
            'purity_label' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'variants' => ['nullable', 'array'],
            'variants.*.label' => ['required', 'string', 'max:80'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.availability' => ['nullable', Rule::in(['in_stock', 'limited', 'out_of_stock'])],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'availability' => ['nullable', Rule::in(['in_stock', 'limited', 'out_of_stock'])],
            'image_url' => ['nullable', 'url', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:60'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'status' => ['nullable', Rule::in(['published', 'hidden'])],
        ]);
    }

    private function ownedVendor(Request $request): CommunityVendor
    {
        abort_unless(
            $this->userIsApprovedVendor($request->user()),
            403,
            'An admin must approve this account as a vendor before you can manage products.'
        );

        return CommunityVendor::query()
            ->where('owner_user_id', $request->user()->id)
            ->firstOrFail();
    }

    private function storeProductImageIfPresent(Request $request): ?string
    {
        if (!$request->hasFile('image')) {
            return null;
        }

        return $this->publicStorageUrl($request, $request->file('image')->store('vendor-product-images', 'public'));
    }

    private function userIsApprovedVendor(User $user): bool
    {
        $attributes = $user->getAttributes();

        if (array_key_exists('is_approved_vendor', $attributes)) {
            return (bool) $attributes['is_approved_vendor'];
        }

        return (bool) $user->newQuery()
            ->whereKey($user->getKey())
            ->value('is_approved_vendor');
    }

    private function publicStorageUrl(Request $request, string $path): string
    {
        $url = Storage::disk('public')->url($path);

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $parsed = parse_url($url);
            if (($parsed['host'] ?? null) === 'localhost' && empty($parsed['port'])) {
                return rtrim($request->getSchemeAndHttpHost(), '/') . '/' . ltrim($parsed['path'] ?? '', '/');
            }

            return $url;
        }

        return rtrim($request->getSchemeAndHttpHost(), '/') . '/' . ltrim($url, '/');
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

    private function uniqueProductSlug(CommunityVendor $vendor, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while ($vendor->products()
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

    private function refreshVendorSnapshot(CommunityVendor $vendor): void
    {
        $reviews = $vendor->publishedReviews()->get(['rating', 'would_buy_again']);
        $count = $reviews->count();

        $vendor->forceFill([
            'review_count' => $count,
            'average_rating' => $count > 0 ? round((float) $reviews->avg('rating'), 2) : 0,
            'would_buy_again_percent' => $count > 0
                ? round(($reviews->where('would_buy_again', true)->count() / $count) * 100, 2)
                : 0,
        ])->save();

        $this->refreshVendorProductSnapshots($vendor);
    }

    private function refreshVendorProductSnapshots(CommunityVendor $vendor): void
    {
        $products = $vendor->products()->get();

        if ($products->isEmpty()) {
            return;
        }

        $reviews = $vendor->publishedReviews()
            ->whereNotNull('product_name')
            ->get(['product_name', 'rating'])
            ->groupBy(fn (CommunityVendorReview $review) => Str::lower(trim((string) $review->product_name)));

        foreach ($products as $product) {
            $matchedReviews = $reviews->get(Str::lower($product->name), collect());

            $product->forceFill([
                'review_count' => $matchedReviews->count(),
                'average_rating' => $matchedReviews->isNotEmpty()
                    ? round((float) $matchedReviews->avg('rating'), 2)
                    : 0,
            ])->save();
        }
    }

    private function topVendors()
    {
        $vendors = CommunityVendor::query()
            ->where('status', 'published')
            ->orderByDesc('average_rating')
            ->orderByDesc('review_count')
            ->limit(5)
            ->get();
        $this->hydrateRatingDistributions($vendors);

        return $vendors;
    }

    private function hydrateRatingDistributions($vendors): void
    {
        $vendors = collect($vendors);
        $ids = $vendors->pluck('id')->filter()->values();

        if ($ids->isEmpty()) {
            return;
        }

        $countsByVendor = CommunityVendorReview::query()
            ->where('status', 'published')
            ->whereIn('vendor_id', $ids)
            ->selectRaw('vendor_id, rating, count(*) as aggregate')
            ->groupBy('vendor_id', 'rating')
            ->get()
            ->groupBy('vendor_id');

        foreach ($vendors as $vendor) {
            $ratingCounts = $countsByVendor
                ->get($vendor->id, collect())
                ->pluck('aggregate', 'rating');
            $total = max(1, (int) $ratingCounts->sum());

            $vendor->setAttribute('community_rating_distribution', collect([5, 4, 3, 2, 1])
                ->map(fn (int $rating) => [
                    'rating' => $rating,
                    'count' => (int) ($ratingCounts[$rating] ?? 0),
                    'percent' => round(((int) ($ratingCounts[$rating] ?? 0) / $total) * 100),
                ])
                ->all());
        }
    }

    private function stats(): array
    {
        $vendors = CommunityVendor::query()->where('status', 'published')->get();
        $reviewCount = (int) $vendors->sum('review_count');
        $weightedRating = $vendors->sum(fn (CommunityVendor $vendor) => (float) $vendor->average_rating * $vendor->review_count);
        $weightedBuyAgain = $vendors->sum(fn (CommunityVendor $vendor) => (float) $vendor->would_buy_again_percent * $vendor->review_count);

        return [
            'vendors_reviewed' => $vendors->count(),
            'total_reviews' => $reviewCount,
            'average_rating' => $reviewCount > 0 ? round($weightedRating / $reviewCount, 1) : 0,
            'would_buy_again' => $reviewCount > 0 ? round($weightedBuyAgain / $reviewCount) : 0,
        ];
    }

    private function filters(): array
    {
        $vendors = CommunityVendor::query()
            ->where('status', 'published')
            ->get(['status_class', 'status_label', 'average_rating', 'tags']);
        $statusOrder = ['trusted' => 10, 'caution' => 20, 'avoid' => 30];

        return [
            'statuses' => $vendors
                ->groupBy('status_class')
                ->map(fn ($group, string $slug) => [
                    'slug' => $slug,
                    'name' => $group->first()->status_label ?: ucfirst(str_replace(['-', '_'], ' ', $slug)),
                    'count' => $group->count(),
                ])
                ->sortBy(fn (array $status) => $statusOrder[$status['slug']] ?? 100)
                ->values()
                ->all(),
            'ratings' => $vendors
                ->pluck('average_rating')
                ->map(fn ($rating) => (int) floor((float) $rating))
                ->filter(fn (int $rating) => $rating > 0)
                ->unique()
                ->sortDesc()
                ->values()
                ->all(),
            'tags' => $vendors
                ->flatMap(fn (CommunityVendor $vendor) => $vendor->tags ?? [])
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all(),
        ];
    }
}
