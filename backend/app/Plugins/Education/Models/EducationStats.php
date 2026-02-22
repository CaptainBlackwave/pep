<?php

namespace App\Plugins\Education\Models;

use Illuminate\Database\Eloquent\Model;
use App\Core\Models\User;

class EducationStats extends Model
{
    protected $fillable = ['user_id', 'intelligence', 'endurance'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
