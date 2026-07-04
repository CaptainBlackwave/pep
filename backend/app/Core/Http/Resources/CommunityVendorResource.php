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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_initials' => $this->logo_initials,
            'logo_text' => $this->logo_text,
            'logo_class' => $this->logo_class,
            'status_label' => $this->status_label,
            'status_class' => $this->status_class,
            'tone' => $this->tone(),
            'description' => $this->description,
            'website_url' => $this->website_url,
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
            'top_products' => $this->top_products ?? [],
            'status' => $this->status,
            'href' => "/vendor-reviews/{$this->slug}",
            'rating_distribution' => $this->ratingDistribution(),
            'review_items' => CommunityVendorReviewResource::collection($this->whenLoaded('publishedReviews')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
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
