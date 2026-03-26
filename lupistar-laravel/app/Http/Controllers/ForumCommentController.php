<?php

namespace App\Http\Controllers;

use App\Models\ForumComment;
use App\Models\ForumDiscussion;
use Illuminate\Http\Request;

class ForumCommentController extends Controller
{
    public function store(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (! $userId) {
            return back();
        }

        $data = $request->validate([
            'discussion_id' => ['required', 'integer'],
            'content' => ['required', 'string'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $discussion = ForumDiscussion::findOrFail((int) $data['discussion_id']);

        $comment = new ForumComment;
        $comment->discussion_id = $discussion->id;
        $comment->author_id = $userId;
        $comment->content = $data['content'];
        $comment->parent_id = $data['parent_id'] ?? null;
        $comment->created_at = now();
        $comment->save();

        $discussion->last_comment_at = now();
        $discussion->last_comment_by = $userId;
        $discussion->updated_at = now();
        $discussion->save();

        return redirect()->route('forum.discussion', ['id' => $discussion->id]);
    }
}
