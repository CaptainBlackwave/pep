<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityMessage extends Model
{
    protected $fillable = [
        'thread_id',
        'sender_user_id',
        'body',
        'attachment_name',
        'attachment_meta',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'attachment_meta' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(CommunityMessageThread::class, 'thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
