<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $table = 'user_preferences';

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(Membre::class, 'user_id');
    }
}
