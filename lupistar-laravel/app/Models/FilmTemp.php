<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FilmTemp extends Model
{
    protected $table = 'films_temp';

    public $timestamps = false;

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(Auteur::class, 'auteur_id');
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class, 'studio_id');
    }

    public function pays(): BelongsTo
    {
        return $this->belongsTo(Pays::class, 'pays_id');
    }

    public function proposePar(): BelongsTo
    {
        return $this->belongsTo(Membre::class, 'propose_par');
    }

    public function sousGenres(): BelongsToMany
    {
        return $this->belongsToMany(SousGenre::class, 'films_temp_sous_genres', 'film_temp_id', 'sous_genre_id');
    }
}
