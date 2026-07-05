<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityVendorProduct extends Model
{
    protected $fillable = [
        'vendor_id',
        'name',
        'slug',
        'category',
        'strength',
        'package_size',
        'purity_label',
        'description',
        'price',
        'currency_code',
        'availability',
        'image_url',
        'tags',
        'review_count',
        'average_rating',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'tags' => 'array',
            'review_count' => 'integer',
            'average_rating' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(CommunityVendor::class, 'vendor_id');
    }
}
