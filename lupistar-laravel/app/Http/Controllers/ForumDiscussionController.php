<?php

namespace App\Http\Controllers;

use App\Models\ForumCategory;
use App\Models\ForumComment;
use App\Models\ForumDiscussion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForumDiscussionController extends Controller
{
    public function index(int $categoryId)
    {
        $category = ForumCategory::findOrFail($categoryId);
        $discussions = ForumDiscussion::query()
            ->where('category_id', $categoryId)
            ->orderByDesc('pinned')
            ->orderByDesc('updated_at')
            ->get();

        return view('Forum.discussions', [
            'title' => 'Discussions',
            'category' => $category,
            'discussions' => $discussions,
        ]);
    }

    public function show(int $id)
    {
        $discussion = ForumDiscussion::with(['author', 'category'])->findOrFail($id);
        $comments = ForumComment::query()
            ->where('discussion_id', $id)
            ->orderBy('created_at')
            ->get();

        DB::table('forum_discussions')->where('id', $id)->update([
            'views' => DB::raw('COALESCE(views,0)+1'),
        ]);

        return view('Forum.show', [
            'title' => $discussion->titre,
            'discussion' => $discussion,
            'comments' => $comments,
        ]);
    }

    public function store(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (! $userId) {
            return redirect()->route('forum.index');
        }

        $data = $request->validate([
            'category_id' => ['required', 'integer'],
            'titre' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string'],
        ]);

        $category = ForumCategory::findOrFail((int) $data['category_id']);

        $discussion = new ForumDiscussion;
        $discussion->category_id = $category->id;
        $discussion->titre = $data['titre'];
        $discussion->description = $data['description'];
        $discussion->author_id = $userId;
        $discussion->created_at = now();
        $discussion->updated_at = now();
        $discussion->save();

        return redirect()->route('forum.discussion', ['id' => $discussion->id]);
    }
}
