<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumDiscussion extends Model
{
    protected $table = 'forum_discussions';

    public $timestamps = false;

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Membre::class, 'author_id');
    }

    public function lastCommentBy(): BelongsTo
    {
        return $this->belongsTo(Membre::class, 'last_comment_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ForumComment::class, 'discussion_id');
    }
}
