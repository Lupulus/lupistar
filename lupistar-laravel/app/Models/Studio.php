<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Studio extends Model
{
    protected $table = 'studios';

    public $timestamps = false;

    public function films(): HasMany
    {
        return $this->hasMany(Film::class, 'studio_id');
    }
}
