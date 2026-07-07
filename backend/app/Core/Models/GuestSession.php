<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;

class GuestSession extends Model
{
    protected $fillable = [
        'guest_id',
        'current_path',
        'current_label',
        'ip_address',
        'user_agent',
        'last_active',
    ];

    protected $casts = [
        'last_active' => 'datetime',
    ];
}
