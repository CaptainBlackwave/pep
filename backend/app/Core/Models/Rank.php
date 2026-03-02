<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Shim model for Rank - provided by Progression plugin.
 *
 * This model exists in Core to provide relation compatibility when the
 * Progression plugin is not installed. When the plugin is installed,
 * it will provide the actual Rank model with full functionality.
 *
 * @property int $id
 * @property string $name
 * @property int $required_exp
 * @property int $max_health
 */
class Rank extends Model
{
    protected $fillable = [
        'name',
        'required_exp',
        'max_health',
    ];

    protected function casts(): array
    {
        return [
            'required_exp' => 'integer',
            'max_health'   => 'integer',
        ];
    }
}
