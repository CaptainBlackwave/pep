<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityDiscussionReaction extends Model
{
    protected $fillable = [
        'target_type',
        'target_id',
        'user_id',
        'emoji',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
