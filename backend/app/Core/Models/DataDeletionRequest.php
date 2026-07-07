<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;

class DataDeletionRequest extends Model
{
    protected $fillable = ['email', 'reason', 'status', 'admin_notes', 'processed_by', 'processed_at'];

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
