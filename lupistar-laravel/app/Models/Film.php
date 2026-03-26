<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Film extends Model
{
    protected $table = 'films';

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

    public function sousGenres(): BelongsToMany
    {
        return $this->belongsToMany(SousGenre::class, 'films_sous_genres', 'film_id', 'sous_genre_id');
    }

    public function membres(): BelongsToMany
    {
        return $this->belongsToMany(Membre::class, 'membres_films_list', 'films_id', 'membres_id')->withPivot('note');
    }
}
