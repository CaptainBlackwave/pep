<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityVendorDocument extends Model
{
    protected $fillable = [
        'vendor_id',
        'title',
        'file_path',
        'file_type',
        'category',
        'description',
        'status',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(CommunityVendor::class, 'vendor_id');
    }
}
