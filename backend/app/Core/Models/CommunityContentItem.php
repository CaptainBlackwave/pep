<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityContentItem extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'slug',
        'category',
        'category_slug',
        'tag',
        'excerpt',
        'body',
        'icon',
        'image_index',
        'image_url',
        'read_minutes',
        'views_count',
        'downloads_count',
        'comments_count',
        'author_name',
        'author_badge',
        'metadata',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'image_index' => 'integer',
            'read_minutes' => 'integer',
            'views_count' => 'integer',
            'downloads_count' => 'integer',
            'comments_count' => 'integer',
            'metadata' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function displayAuthorName(): string
    {
        return $this->author_name ?: ($this->author?->username ?: $this->author?->name ?: '');
    }
}
