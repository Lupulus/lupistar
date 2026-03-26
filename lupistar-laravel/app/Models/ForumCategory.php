<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumCategory extends Model
{
    protected $table = 'forum_categories';

    public $timestamps = false;

    public function discussions(): HasMany
    {
        return $this->hasMany(ForumDiscussion::class, 'category_id');
    }
}
