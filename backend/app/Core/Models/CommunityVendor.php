<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityVendor extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo_initials',
        'logo_text',
        'logo_class',
        'status_label',
        'status_class',
        'description',
        'website_url',
        'member_since',
        'last_active_at',
        'review_count',
        'average_rating',
        'would_buy_again_percent',
        'response_rate_percent',
        'avg_response_time',
        'tags',
        'top_products',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'member_since' => 'date',
            'last_active_at' => 'datetime',
            'review_count' => 'integer',
            'average_rating' => 'decimal:2',
            'would_buy_again_percent' => 'decimal:2',
            'response_rate_percent' => 'decimal:2',
            'tags' => 'array',
            'top_products' => 'array',
        ];
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CommunityVendorReview::class, 'vendor_id');
    }

    public function publishedReviews(): HasMany
    {
        return $this->reviews()->where('status', 'published');
    }
}
