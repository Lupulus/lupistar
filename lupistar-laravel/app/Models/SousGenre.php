<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SousGenre extends Model
{
    protected $table = 'sous_genres';

    public $timestamps = false;

    public function films(): BelongsToMany
    {
        return $this->belongsToMany(Film::class, 'films_sous_genres', 'sous_genre_id', 'film_id');
    }

    public function filmsTemp(): BelongsToMany
    {
        return $this->belongsToMany(FilmTemp::class, 'films_temp_sous_genres', 'sous_genre_id', 'film_temp_id');
    }
}
