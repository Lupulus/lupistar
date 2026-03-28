<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pays extends Model
{
    protected $table = 'pays';

    public $timestamps = false;

    public function films(): HasMany
    {
        return $this->hasMany(Film::class, 'pays_id');
    }
}
