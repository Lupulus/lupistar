<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membre extends Model
{
    protected $table = 'membres';

    public $timestamps = false;

    public function films(): BelongsToMany
    {
        return $this->belongsToMany(Film::class, 'membres_films_list', 'membres_id', 'films_id')->withPivot('note');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(ForumDiscussion::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ForumComment::class, 'author_id');
    }

    public function moderations(): HasMany
    {
        return $this->hasMany(ForumModeration::class, 'moderator_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function passwordResets(): HasMany
    {
        return $this->hasMany(PasswordReset::class, 'user_id');
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(UserPreference::class, 'user_id');
    }
}
