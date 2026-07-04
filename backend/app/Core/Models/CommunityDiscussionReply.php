<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityDiscussionReply extends Model
{
    protected $fillable = [
        'discussion_id',
        'user_id',
        'author_name',
        'body',
        'attachment_name',
        'votes_count',
        'is_solution',
    ];

    protected function casts(): array
    {
        return [
            'is_solution' => 'boolean',
        ];
    }

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(CommunityDiscussion::class, 'discussion_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayAuthorName(): string
    {
        return $this->author_name ?: ($this->user?->username ?: $this->user?->name ?: '');
    }
}
