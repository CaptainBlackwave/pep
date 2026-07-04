<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityAnnouncement extends Model
{
    protected $fillable = [
        'user_id',
        'author_name',
        'title',
        'slug',
        'category',
        'category_slug',
        'icon',
        'tone',
        'excerpt',
        'body',
        'image_url',
        'comments_count',
        'views_count',
        'is_pinned',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'comments_count' => 'integer',
            'views_count' => 'integer',
            'is_pinned' => 'boolean',
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
