<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityNotification extends Model
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
        'source_type',
        'source_id',
        'source_url',
        'views_count',
        'is_pinned',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'views_count' => 'integer',
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(CommunityNotificationRead::class, 'notification_id');
    }

    public function displayAuthorName(): string
    {
        return $this->author_name ?: ($this->author?->username ?: $this->author?->name ?: '');
    }
}
