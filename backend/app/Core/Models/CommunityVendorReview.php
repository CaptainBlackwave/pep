<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityVendorReview extends Model
{
    protected $fillable = [
        'vendor_id',
        'user_id',
        'author_name',
        'title',
        'body',
        'rating',
        'product_name',
        'helpful_count',
        'would_buy_again',
        'is_verified_buyer',
        'tags',
        'photo_urls',
        'reviewed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'helpful_count' => 'integer',
            'would_buy_again' => 'boolean',
            'is_verified_buyer' => 'boolean',
            'tags' => 'array',
            'photo_urls' => 'array',
            'reviewed_at' => 'date',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(CommunityVendor::class, 'vendor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function displayAuthorName(): string
    {
        return $this->author_name ?: ($this->user?->username ?: $this->user?->name ?: '');
    }
}
