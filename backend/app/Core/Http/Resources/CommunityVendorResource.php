<?php

namespace App\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityVendorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $products = $this->loadedProducts();

        return [
            'id' => $this->id,
            'owner_user_id' => $this->owner_user_id,
            'claim_status' => $this->claim_status ?? 'unclaimed',
            'is_owned_by_viewer' => $request->user()?->id !== null && (int) $this->owner_user_id === (int) $request->user()->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_initials' => $this->logo_initials,
            'logo_text' => $this->logo_text,
            'logo_class' => $this->logo_class,
            'status_label' => $this->status_label,
            'status_class' => $this->status_class,
            'country' => $this->country,
            'tone' => $this->tone(),
            'description' => $this->description,
            'website_url' => $this->website_url,
            'image_url' => $this->image_url,
            'contact' => [
                'email' => $this->contact_email,
                'telegram' => $this->contact_telegram,
                'signal' => $this->contact_signal,
                'discord' => $this->contact_discord,
                'support_url' => $this->support_url,
                'response_policy' => $this->response_policy,
                'public_notes' => $this->public_contact_notes,
            ],
            'member_since' => $this->member_since?->toDateString(),
            'member_since_label' => $this->member_since?->format('M Y'),
            'last_active_at' => $this->last_active_at?->toIso8601String(),
            'last_active_label' => $this->last_active_at?->diffForHumans() ?? 'today',
            'review_count' => $this->review_count,
            'average_rating' => (float) $this->average_rating,
            'rating_label' => number_format((float) $this->average_rating, 1),
            'would_buy_again_percent' => (float) $this->would_buy_again_percent,
            'would_buy_again_label' => rtrim(rtrim(number_format((float) $this->would_buy_again_percent, 1), '0'), '.') . '%',
            'response_rate_percent' => (float) $this->response_rate_percent,
            'response_rate_label' => rtrim(rtrim(number_format((float) $this->response_rate_percent, 1), '0'), '.') . '%',
            'avg_response_time' => $this->avg_response_time,
            'tags' => $this->tags ?? [],
            'product_count' => $products->where('status', 'published')->count(),
            'products' => CommunityVendorProductResource::collection($products),
            'top_products' => $this->topProducts($products, $request),
            'status' => $this->status,
            'profile_submitted_at' => $this->profile_submitted_at?->toIso8601String(),
            'href' => "/vendor-reviews/{$this->slug}",
            'rating_distribution' => $this->ratingDistribution(),
            'review_items' => CommunityVendorReviewResource::collection($this->whenLoaded('publishedReviews')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function loadedProducts()
    {
        if ($this->resource->relationLoaded('products')) {
            return $this->products;
        }

        if ($this->resource->relationLoaded('publishedProducts')) {
            return $this->publishedProducts;
        }

        return collect();
    }

    private function topProducts($products, Request $request): array
    {
        if ($products->isNotEmpty()) {
            return $products
                ->where('status', 'published')
                ->sortBy([
                    ['average_rating', 'desc'],
                    ['review_count', 'desc'],
                    ['sort_order', 'asc'],
                    ['name', 'asc'],
                ])
                ->take(5)
                ->map(fn ($product) => (new CommunityVendorProductResource($product))->resolve($request))
                ->values()
                ->all();
        }

        return $this->top_products ?? [];
    }

    private function tone(): string
    {
        return match ($this->status_class) {
            'avoid' => 'red',
            'caution' => 'amber',
            default => 'green',
        };
    }

    /**
     * @return array<int, array<string, int|float>>
     */
    private function ratingDistribution(): array
    {
        $hydratedDistribution = $this->resource->getAttributes()['community_rating_distribution'] ?? null;
        if (is_array($hydratedDistribution)) {
            return $hydratedDistribution;
        }

        $counts = $this->publishedReviews()
            ->selectRaw('rating, count(*) as aggregate')
            ->groupBy('rating')
            ->pluck('aggregate', 'rating');

        $total = max(1, (int) $counts->sum());

        return collect([5, 4, 3, 2, 1])
            ->map(fn (int $rating) => [
                'rating' => $rating,
                'count' => (int) ($counts[$rating] ?? 0),
                'percent' => round(((int) ($counts[$rating] ?? 0) / $total) * 100),
            ])
            ->all();
    }
}
