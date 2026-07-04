<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityDiscussion extends Model
{
    protected $fillable = [
        'category_id',
        'user_id',
        'last_reply_user_id',
        'author_name',
        'title',
        'slug',
        'tag',
        'excerpt',
        'body',
        'status',
        'is_pinned',
        'is_locked',
        'views_count',
        'replies_count',
        'last_reply_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'last_reply_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CommunityDiscussionCategory::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lastReplyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_reply_user_id');
    }

    /**
     * @return HasMany<CommunityDiscussionReply>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(CommunityDiscussionReply::class, 'discussion_id');
    }

    public function displayAuthorName(): string
    {
        return $this->author_name ?: ($this->user?->username ?: $this->user?->name ?: '');
    }
}
