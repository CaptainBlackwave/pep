<?php

namespace App\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityVendorProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'strength' => $this->strength,
            'package_size' => $this->package_size,
            'purity_label' => $this->purity_label,
            'description' => $this->description,
            'variants' => $this->variants ?? [],
            'price' => $this->price !== null ? (float) $this->price : null,
            'price_label' => $this->priceLabel(),
            'currency_code' => $this->currency_code,
            'availability' => $this->availability,
            'availability_label' => $this->availabilityLabel(),
            'image_url' => $this->image_url,
            'tags' => $this->tags ?? [],
            'review_count' => $this->review_count,
            'average_rating' => (float) $this->average_rating,
            'rating_label' => number_format((float) $this->average_rating, 1),
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'href' => $this->vendor?->slug
                ? "/vendor-reviews/{$this->vendor->slug}?product={$this->slug}"
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function priceLabel(): ?string
    {
        if ($this->price === null) {
            return null;
        }

        $symbol = match (strtoupper((string) $this->currency_code)) {
            'GBP' => '£',
            'EUR' => '€',
            default => '$',
        };

        return $symbol . number_format((float) $this->price, 2);
    }

    private function availabilityLabel(): string
    {
        return match ($this->availability) {
            'limited' => 'Limited',
            'out_of_stock' => 'Out of stock',
            default => 'In stock',
        };
    }
}
