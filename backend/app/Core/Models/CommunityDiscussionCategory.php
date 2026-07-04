<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityDiscussionCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'sort_order',
    ];

    /**
     * @return HasMany<CommunityDiscussion>
     */
    public function discussions(): HasMany
    {
        return $this->hasMany(CommunityDiscussion::class, 'category_id');
    }
}
