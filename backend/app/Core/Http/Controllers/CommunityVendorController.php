<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\CommunityVendorResource;
use App\Core\Http\Resources\CommunityVendorReviewResource;
use App\Core\Models\CommunityVendor;
use App\Core\Models\CommunityVendorReview;
use Illuminate\Http\Request;

class CommunityVendorController extends Controller
{
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
                    ->orWhere('description', 'like', "%{$search}%");
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

        return CommunityVendorResource::collection(
            $query->orderByDesc('average_rating')->orderByDesc('review_count')->limit($limit)->get()
        )->additional([
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
            ->load(['publishedReviews' => fn ($query) => $query->with('user')->latest('reviewed_at')->latest()]);

        return new CommunityVendorResource($vendorModel);
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
            'would_buy_again' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $review = CommunityVendorReview::create([
            ...$validated,
            'vendor_id' => $vendorModel->id,
            'user_id' => $user?->id,
            'author_name' => null,
            'status' => 'pending',
            'is_verified_buyer' => false,
            'reviewed_at' => now()->toDateString(),
        ]);

        return (new CommunityVendorReviewResource($review->load('user')))
            ->response()
            ->setStatusCode(201);
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

    private function topVendors()
    {
        return CommunityVendor::query()
            ->where('status', 'published')
            ->orderByDesc('average_rating')
            ->orderByDesc('review_count')
            ->limit(5)
            ->get();
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
