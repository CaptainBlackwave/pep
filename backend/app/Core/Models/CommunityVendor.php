<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityVendor extends Model
{
    protected $fillable = [
        'owner_user_id',
        'claim_status',
        'name',
        'slug',
        'logo_initials',
        'logo_text',
        'logo_class',
        'status_label',
        'status_class',
        'description',
        'website_url',
        'image_url',
        'contact_email',
        'contact_telegram',
        'contact_signal',
        'contact_discord',
        'support_url',
        'response_policy',
        'public_contact_notes',
        'profile_submitted_at',
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
            'profile_submitted_at' => 'datetime',
            'review_count' => 'integer',
            'average_rating' => 'decimal:2',
            'would_buy_again_percent' => 'decimal:2',
            'response_rate_percent' => 'decimal:2',
            'tags' => 'array',
            'top_products' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
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
