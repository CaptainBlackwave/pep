<?php

namespace App\Core\Http\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Http\Resources\CommunityVendorReviewResource;
use App\Core\Models\CommunityVendorReview;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunityVendorReviewAdminController extends Controller
{

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'pending', 'published', 'hidden'])],
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CommunityVendorReview::query()->with(['vendor', 'user']);

        if (($validated['status'] ?? 'all') !== 'all') {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%")
                    ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->where('name', 'like', "%{$search}%"));
            });
        }

        $limit = (int) ($validated['limit'] ?? 50);

        return CommunityVendorReviewResource::collection(
            $query->latest('updated_at')->limit($limit)->get()
        )->additional([
            'meta' => [
                'stats' => [
                    'total' => CommunityVendorReview::count(),
                    'pending' => CommunityVendorReview::where('status', 'pending')->count(),
                    'published' => CommunityVendorReview::where('status', 'published')->count(),
                    'hidden' => CommunityVendorReview::where('status', 'hidden')->count(),
                ],
            ],
        ]);
    }

    public function update(Request $request, string $review)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'published', 'hidden'])],
            'is_verified_buyer' => ['nullable', 'boolean'],
            'helpful_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $reviewModel = CommunityVendorReview::with(['vendor', 'user'])->findOrFail($review);
        $wasPublished = $reviewModel->status === 'published';
        $reviewModel->fill($validated);

        if (($validated['status'] ?? null) === 'published' && !$reviewModel->reviewed_at) {
            $reviewModel->reviewed_at = now()->toDateString();
        }

        $reviewModel->save();

        if ($reviewModel->vendor && ($wasPublished !== ($reviewModel->status === 'published'))) {
            $this->refreshVendorSnapshot($reviewModel->vendor);
        }

        return new CommunityVendorReviewResource($reviewModel->load(['vendor', 'user']));
    }

    public function destroy(string $review)
    {
        $reviewModel = CommunityVendorReview::with('vendor')->findOrFail($review);
        $wasPublished = $reviewModel->status === 'published';
        $reviewModel->forceFill(['status' => 'hidden'])->save();

        if ($wasPublished && $reviewModel->vendor) {
            $this->refreshVendorSnapshot($reviewModel->vendor);
        }

        return response()->json([
            'success' => true,
            'message' => 'Review hidden.',
        ]);
    }

    private function refreshVendorSnapshot($vendor): void
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
    }
}
