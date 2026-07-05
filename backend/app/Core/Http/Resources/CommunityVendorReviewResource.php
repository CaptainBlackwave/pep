<?php

namespace App\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class CommunityVendorReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $authorName = $this->displayAuthorName();

        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'title' => $this->title,
            'body' => $this->body,
            'rating' => $this->rating,
            'product_name' => $this->product_name,
            'helpful_count' => $this->helpful_count,
            'would_buy_again' => $this->would_buy_again,
            'is_verified_buyer' => $this->is_verified_buyer,
            'tags' => $this->tags ?? [],
            'photo_urls' => $this->photo_urls ?? [],
            'status' => $this->status,
            'vendor' => $this->whenLoaded('vendor', fn () => [
                'id' => $this->vendor->id,
                'name' => $this->vendor->name,
                'slug' => $this->vendor->slug,
            ]),
            'reviewed_at' => $this->reviewed_at?->toDateString(),
            'reviewed_date' => $this->reviewed_at?->format('M j, Y') ?? $this->created_at?->format('M j, Y'),
            'author' => [
                'id' => $this->user_id,
                'name' => $authorName,
                'username' => $this->user?->username,
                'initial' => Str::upper(Str::substr($authorName, 0, 1)),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
