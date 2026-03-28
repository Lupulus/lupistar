<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(Membre::class, 'user_id');
    }
}
