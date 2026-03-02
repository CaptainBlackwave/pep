<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Shim model for Location - provided by Travel plugin.
 *
 * This model exists in Core to provide relation compatibility when the
 * Travel plugin is not installed. When the plugin is installed,
 * it will provide the actual Location model with full functionality.
 *
 * @property int $id
 * @property string $name
 * @property string $description
 */
class Location extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    protected function casts(): array
    {
        return [];
    }
}
