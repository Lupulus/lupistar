<?php

namespace App\Http\Controllers;

use App\Models\ForumComment;
use App\Models\ForumDiscussion;
use App\Services\ForumContentFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;

class ForumCommentController extends Controller
{
    public function store(Request $request)
    {
        $this->ensureForumAccess($request);
        $this->ensureForumWrite($request);

        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return back();
        }

        $data = $request->validate([
            'discussion_id' => ['required'],
            'content' => ['required', 'string'],
            'parent_id' => ['nullable'],
        ]);

        $formatter = app(ForumContentFormatter::class);
        if ($this->useMongo()) {
            $mongo = $this->mongo();
            $discussionOid = $this->toObjectId((string) $data['discussion_id']);
            $discussion = $mongo->table('forum_discussions')->where('_id', $discussionOid)->first(['category_id']);
            if (! $discussion) {
                abort(404);
            }

            $category = $mongo->table('forum_categories')->where('_id', $discussion->category_id)->first(['admin_only']);
            if (! $category) {
                abort(404);
            }
            if ((int) ($category->admin_only ?? 0) === 1 && ! $this->isAdmin($request)) {
                abort(403);
            }

            $parentIdRaw = (string) ($data['parent_id'] ?? '');
            $parentOid = null;
            if ($parentIdRaw !== '') {
                $parentOid = $this->toObjectId($parentIdRaw);
            }

            $now = now();
            $insertedId = null;
            $mongo->table('forum_comments')->raw(function ($collection) use ($discussionOid, $userId, $data, $parentOid, $now, &$insertedId) {
                $r = $collection->insertOne([
                    'discussion_id' => $discussionOid,
                    'author_id' => $userId,
                    'content' => (string) $data['content'],
                    'parent_id' => $parentOid,
                    'likes_user_ids' => [],
                    'created_at' => $now,
                    'edited_at' => null,
                    'edited_by' => null,
                ]);
                $insertedId = $r->getInsertedId();

                return $r;
            });

            $mongo->table('forum_discussions')->raw(function ($collection) use ($discussionOid, $userId, $now) {
                return $collection->updateOne(['_id' => $discussionOid], [
                    '$set' => [
                        'last_comment_at' => $now,
                        'last_comment_by' => $userId,
                        'updated_at' => $now,
                    ],
                ]);
            });

            $mentions = $formatter->extractMentions((string) $data['content']);
            $this->notifyMentions($mentions, (string) $discussionOid, (string) $insertedId, $userId);

            return redirect()->route('forum.discussion', ['id' => (string) $discussionOid]).'#comment-'.(string) $insertedId;
        }

        $discussionIdInt = (int) $data['discussion_id'];
        $discussion = ForumDiscussion::with(['category'])->findOrFail($discussionIdInt);
        if ($discussion->category?->admin_only && ! $this->isAdmin($request)) {
            abort(403);
        }

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

        $mentions = $formatter->extractMentions((string) $comment->content);
        $this->notifyMentions($mentions, (string) $discussion->id, (string) $comment->id, $userId);

        return redirect()->route('forum.discussion', ['id' => (string) $discussion->id]).'#comment-'.(string) $comment->id;
    }

    public function update(Request $request, string $id)
    {
        $this->ensureForumAccess($request);
        $this->ensureForumWrite($request);

        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return redirect()->route('forum');
        }

        $data = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $formatter = app(ForumContentFormatter::class);
        if ($this->useMongo()) {
            $mongo = $this->mongo();
            $commentOid = $this->toObjectId($id);
            $comment = $mongo->table('forum_comments')->where('_id', $commentOid)->first(['discussion_id', 'author_id']);
            if (! $comment) {
                abort(404);
            }

            $discussion = $mongo->table('forum_discussions')->where('_id', $comment->discussion_id)->first(['category_id']);
            if (! $discussion) {
                abort(404);
            }
            $category = $mongo->table('forum_categories')->where('_id', $discussion->category_id)->first(['admin_only']);
            if (! $category) {
                abort(404);
            }
            if ((int) ($category->admin_only ?? 0) === 1 && ! $this->isAdmin($request)) {
                abort(403);
            }
            if (! $this->canEdit($request, $userId, (int) ($comment->author_id ?? 0))) {
                abort(403);
            }

            $now = now();
            $mongo->table('forum_comments')->raw(function ($collection) use ($commentOid, $data, $userId, $now) {
                return $collection->updateOne(['_id' => $commentOid], [
                    '$set' => [
                        'content' => (string) $data['content'],
                        'edited_at' => $now,
                        'edited_by' => $userId,
                    ],
                ]);
            });

            $mongo->table('forum_discussions')->raw(function ($collection) use ($comment) {
                return $collection->updateOne(['_id' => $comment->discussion_id], ['$set' => ['updated_at' => now()]]);
            });

            $mentions = $formatter->extractMentions((string) $data['content']);
            $this->notifyMentions($mentions, (string) $comment->discussion_id, $id, $userId);

            return redirect()->route('forum.discussion', ['id' => (string) $comment->discussion_id]).'#comment-'.$id;
        }

        $idInt = (int) $id;
        $comment = ForumComment::with(['discussion.category'])->findOrFail($idInt);
        if ($comment->discussion?->category?->admin_only && ! $this->isAdmin($request)) {
            abort(403);
        }
        if (! $this->canEdit($request, $userId, (int) $comment->author_id)) {
            abort(403);
        }

        $comment->content = $data['content'];
        $comment->edited_at = now();
        $comment->edited_by = $userId;
        $comment->save();

        $discussionId = (int) $comment->discussion_id;
        DB::table('forum_discussions')->where('id', $discussionId)->update(['updated_at' => now()]);

        $mentions = $formatter->extractMentions((string) $comment->content);
        $this->notifyMentions($mentions, (string) $discussionId, (string) $comment->id, $userId);

        return redirect()->route('forum.discussion', ['id' => (string) $discussionId]).'#comment-'.(string) $comment->id;
    }

    public function destroy(Request $request, string $id)
    {
        $this->ensureForumAccess($request);
        $this->ensureForumWrite($request);

        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return redirect()->route('forum');
        }

        if ($this->useMongo()) {
            $mongo = $this->mongo();
            $commentOid = $this->toObjectId($id);
            $comment = $mongo->table('forum_comments')->where('_id', $commentOid)->first(['discussion_id', 'author_id']);
            if (! $comment) {
                abort(404);
            }

            $discussion = $mongo->table('forum_discussions')->where('_id', $comment->discussion_id)->first(['category_id']);
            if (! $discussion) {
                abort(404);
            }
            $category = $mongo->table('forum_categories')->where('_id', $discussion->category_id)->first(['admin_only']);
            if (! $category) {
                abort(404);
            }
            if ((int) ($category->admin_only ?? 0) === 1 && ! $this->isAdmin($request)) {
                abort(403);
            }
            if (! $this->canEdit($request, $userId, (int) ($comment->author_id ?? 0))) {
                abort(403);
            }

            $mongo->table('forum_comments')->where('_id', $commentOid)->delete();

            $last = $mongo->table('forum_comments')
                ->where('discussion_id', $comment->discussion_id)
                ->orderByDesc('created_at')
                ->first(['author_id', 'created_at']);

            $mongo->table('forum_discussions')->raw(function ($collection) use ($comment, $last) {
                return $collection->updateOne(['_id' => $comment->discussion_id], [
                    '$set' => [
                        'last_comment_at' => $last?->created_at,
                        'last_comment_by' => $last?->author_id,
                        'updated_at' => now(),
                    ],
                ]);
            });

            return redirect()->route('forum.discussion', ['id' => (string) $comment->discussion_id]);
        }

        $idInt = (int) $id;
        $comment = ForumComment::with(['discussion.category'])->findOrFail($idInt);
        if ($comment->discussion?->category?->admin_only && ! $this->isAdmin($request)) {
            abort(403);
        }
        if (! $this->canEdit($request, $userId, (int) $comment->author_id)) {
            abort(403);
        }

        $discussionId = (int) $comment->discussion_id;

        DB::table('forum_comment_likes')->where('comment_id', $idInt)->delete();
        DB::table('forum_comments')->where('id', $idInt)->delete();

        $last = DB::table('forum_comments')
            ->where('discussion_id', $discussionId)
            ->orderByDesc('created_at')
            ->first(['author_id', 'created_at']);

        DB::table('forum_discussions')->where('id', $discussionId)->update([
            'last_comment_at' => $last?->created_at,
            'last_comment_by' => $last?->author_id,
            'updated_at' => now(),
        ]);

        return redirect()->route('forum.discussion', ['id' => (string) $discussionId]);
    }

    public function toggleLike(Request $request, string $id)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Non connecté'], 401);
        }

        if ($this->useMongo()) {
            $mongo = $this->mongo();
            $commentOid = $this->toObjectId($id);

            $comment = $mongo->table('forum_comments')->where('_id', $commentOid)->first(['likes_user_ids']);
            if (! $comment) {
                return response()->json(['success' => false, 'message' => 'Introuvable'], 404);
            }

            $likes = is_array($comment->likes_user_ids ?? null) ? $comment->likes_user_ids : [];
            $likes = array_values(array_filter(array_map(static fn ($v) => is_numeric($v) ? (int) $v : null, $likes), static fn ($v) => $v !== null));
            $already = in_array($userId, $likes, true);

            if ($already) {
                $mongo->table('forum_comments')->raw(function ($collection) use ($commentOid, $userId) {
                    return $collection->updateOne(['_id' => $commentOid], ['$pull' => ['likes_user_ids' => $userId]]);
                });
            } else {
                $mongo->table('forum_comments')->raw(function ($collection) use ($commentOid, $userId) {
                    return $collection->updateOne(['_id' => $commentOid], ['$addToSet' => ['likes_user_ids' => $userId]]);
                });
            }

            $after = $mongo->table('forum_comments')->where('_id', $commentOid)->first(['likes_user_ids']);
            $afterLikes = is_array($after?->likes_user_ids ?? null) ? $after->likes_user_ids : [];
            $afterLikes = array_values(array_filter(array_map(static fn ($v) => is_numeric($v) ? (int) $v : null, $afterLikes), static fn ($v) => $v !== null));

            return response()->json(['success' => true, 'liked' => ! $already, 'count' => count($afterLikes)]);
        }

        $idInt = (int) $id;
        $exists = DB::table('forum_comment_likes')->where('comment_id', $idInt)->where('user_id', $userId)->exists();
        if ($exists) {
            DB::table('forum_comment_likes')->where('comment_id', $idInt)->where('user_id', $userId)->delete();
        } else {
            DB::table('forum_comment_likes')->insert([
                'comment_id' => $idInt,
                'user_id' => $userId,
                'created_at' => now(),
            ]);
        }

        $count = (int) DB::table('forum_comment_likes')->where('comment_id', $idInt)->count();

        return response()->json(['success' => true, 'liked' => ! $exists, 'count' => $count]);
    }

    private function notifyMentions(array $usernames, string $discussionId, string $commentId, int $fromUserId): void
    {
        $usernames = array_values(array_filter(array_unique(array_map(static fn ($u) => trim((string) $u), $usernames))));
        if (empty($usernames)) {
            return;
        }

        $targets = DB::table('membres')
            ->whereIn('username', $usernames)
            ->get(['id', 'username'])
            ->map(fn ($u) => ['id' => (int) $u->id, 'username' => (string) $u->username])
            ->all();

        if (empty($targets)) {
            return;
        }

        $url = url('/forum/discussion/'.$discussionId.'#comment-'.$commentId);
        $now = now();

        foreach ($targets as $t) {
            if ($t['id'] === $fromUserId) {
                continue;
            }
            DB::table('notifications')->insert([
                'user_id' => $t['id'],
                'titre' => 'Mention dans le forum',
                'message' => "Quelqu'un vous a cité dans le forum : $url",
                'type' => 'forum_mention',
                'lu' => false,
                'date_creation' => $now,
            ]);
        }
    }

    private function isAdmin(Request $request): bool
    {
        $titre = (string) $request->session()->get('titre', 'Membre');

        return in_array($titre, ['Admin', 'Super-Admin'], true);
    }

    private function canEdit(Request $request, int $currentUserId, int $authorId): bool
    {
        if ($currentUserId === $authorId) {
            return true;
        }

        return $this->isAdmin($request);
    }

    private function useMongo(): bool
    {
        return (string) env('FORUM_DRIVER', 'mysql') === 'mongodb';
    }

    private function mongo()
    {
        if (! extension_loaded('mongodb')) {
            abort(500, 'Extension PHP mongodb manquante (ext-mongodb).');
        }

        return DB::connection('mongodb');
    }

    private function toObjectId(string $id)
    {
        try {
            return new ObjectId($id);
        } catch (\Throwable) {
            abort(404);
        }
    }

    private function ensureForumAccess(Request $request): void
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : 0;
        if ($userId <= 0) {
            return;
        }

        if (in_array('Forum Accès Off', $this->currentRestrictions($request), true)) {
            abort(403);
        }
    }

    private function ensureForumWrite(Request $request): void
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : 0;
        if ($userId <= 0) {
            return;
        }

        if (in_array('Forum Écriture Off', $this->currentRestrictions($request), true)) {
            abort(403);
        }
    }

    private function currentRestrictions(Request $request): array
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : 0;
        if ($userId <= 0) {
            return [];
        }

        $raw = (string) (DB::table('membres')->where('id', $userId)->value('restriction') ?? '');
        $list = array_values(array_filter(array_map(
            static fn ($v) => trim((string) $v),
            explode(',', $raw)
        ), static fn ($v) => $v !== '' && $v !== 'Aucune'));

        return array_values(array_unique($list));
    }
}
