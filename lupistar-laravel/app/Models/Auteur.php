<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Auteur extends Model
{
    protected $table = 'auteurs';

    public $timestamps = false;

    public function films(): HasMany
    {
        return $this->hasMany(Film::class, 'auteur_id');
    }
}
