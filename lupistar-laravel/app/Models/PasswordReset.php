<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordReset extends Model
{
    protected $table = 'password_resets';

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(Membre::class, 'user_id');
    }
}
