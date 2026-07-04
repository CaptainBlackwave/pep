<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscussionSubscription extends Model
{
    protected $fillable = [
        'discussion_id',
        'user_id',
    ];

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(CommunityDiscussion::class, 'discussion_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
