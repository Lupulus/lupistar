<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumModeration extends Model
{
    protected $table = 'forum_moderations';

    public $timestamps = false;

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(Membre::class, 'moderator_id');
    }
}
