<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumComment extends Model
{
    protected $table = 'forum_comments';

    public $timestamps = false;

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(ForumDiscussion::class, 'discussion_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Membre::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumComment::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ForumComment::class, 'parent_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(Membre::class, 'edited_by');
    }
}
