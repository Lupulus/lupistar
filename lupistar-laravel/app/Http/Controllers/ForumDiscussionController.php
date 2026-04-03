<?php

namespace App\Http\Controllers;

use App\Models\ForumCategory;
use App\Models\ForumComment;
use App\Models\ForumDiscussion;
use App\Services\ForumContentFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;

class ForumDiscussionController extends Controller
{
    public function index(Request $request, string $categoryId)
    {
        $this->ensureForumAccess($request);
        $q = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'activity');
        $filmId = is_numeric($request->query('film_id')) ? (int) $request->query('film_id') : null;

        if ($this->useMongo()) {
            $mongo = $this->mongo();
            $categoryOid = $this->toObjectId($categoryId);

            $category = $mongo->table('forum_categories')->where('_id', $categoryOid)->first();
            if (! $category) {
                abort(404);
            }
            if ((int) ($category->admin_only ?? 0) === 1 && ! $this->isAdmin($request)) {
                abort(403);
            }
            $category->route_id = (string) $this->mongoDocId($category);

            $authorIds = [];
            if ($q !== '') {
                $lookup = ltrim($q, '@');
                if ($lookup !== '') {
                    $authorIds = DB::table('membres')
                        ->where('username', 'like', '%'.$lookup.'%')
                        ->limit(25)
                        ->pluck('id')
                        ->map(fn ($v) => (int) $v)
                        ->all();
                }
            }

            $query = $mongo->table('forum_discussions')->where('category_id', $categoryOid);
            if ($q !== '') {
                $query->where(function ($sub) use ($q, $authorIds) {
                    $sub->where('titre', 'like', '%'.$q.'%')
                        ->orWhere('description', 'like', '%'.$q.'%');
                    if (! empty($authorIds)) {
                        $sub->orWhereIn('author_id', $authorIds);
                    }
                });
            }
            if ($filmId) {
                $query->where('description', 'like', '%[film:'.$filmId.':%');
            }

            $query->orderByDesc('pinned');
            if ($sort === 'popular') {
                $query->orderByDesc('views');
            } elseif ($sort === 'recent') {
                $query->orderByDesc('created_at');
            } else {
                $query->orderByDesc('updated_at');
            }

            $discussions = $query->limit(60)->get();

            $discussionIds = collect($discussions)->map(fn ($d) => $this->mongoDocId($d))->filter()->values()->all();
            $countsById = [];
            if (! empty($discussionIds)) {
                $cursor = $mongo->table('forum_comments')->raw(function ($collection) use ($discussionIds) {
                    return $collection->aggregate([
                        ['$match' => ['discussion_id' => ['$in' => $discussionIds]]],
                        ['$group' => ['_id' => '$discussion_id', 'c' => ['$sum' => 1]]],
                    ]);
                });
                foreach ($cursor as $row) {
                    $key = (string) ($row->_id ?? $row->id ?? '');
                    if ($key !== '') {
                        $countsById[$key] = (int) ($row->c ?? 0);
                    }
                }
            }

            $authorMap = [];
            $authorList = collect($discussions)->pluck('author_id')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all();
            $lastIds = collect($discussions)->pluck('last_comment_by')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all();
            $authorList = array_values(array_unique(array_merge($authorList, $lastIds)));
            if (! empty($authorList)) {
                $authorMap = DB::table('membres')
                    ->whereIn('id', $authorList)
                    ->get(['id', 'username'])
                    ->mapWithKeys(fn ($u) => [(int) $u->id => (string) $u->username])
                    ->all();
            }

            collect($discussions)->each(function ($d) use ($countsById, $authorMap) {
                $docId = $this->mongoDocId($d);
                if (! $docId) {
                    return;
                }
                $d->route_id = (string) $docId;
                $d->author = (object) ['username' => (string) ($authorMap[(int) ($d->author_id ?? 0)] ?? '—')];
                $d->last_comment_user = (object) ['username' => (string) ($authorMap[(int) ($d->last_comment_by ?? 0)] ?? '')];
                $count = (int) ($countsById[(string) $docId] ?? 0);
                $d->replies_count = max(0, $count - 1);
                $d->created_at = $this->normalizeDateValue($d->created_at ?? null);
                $d->updated_at = $this->normalizeDateValue($d->updated_at ?? null);
                $d->last_comment_at = $this->normalizeDateValue($d->last_comment_at ?? null);
            });
        } else {
            $categoryIdInt = (int) $categoryId;
            $category = ForumCategory::findOrFail($categoryIdInt);
            if ($category->admin_only && ! $this->isAdmin($request)) {
                abort(403);
            }
            $category->setAttribute('route_id', (string) $category->id);

            $query = ForumDiscussion::query()
                ->with(['author'])
                ->where('category_id', $categoryIdInt);

            if ($q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('titre', 'like', '%'.$q.'%')
                        ->orWhere('description', 'like', '%'.$q.'%')
                        ->orWhereHas('author', fn ($a) => $a->where('username', 'like', '%'.$q.'%'));
                });
            }

            if ($filmId) {
                $query->where('description', 'like', '%[film:'.$filmId.':%');
            }

            $query->orderByDesc('pinned');
            if ($sort === 'popular') {
                $query->orderByDesc('views');
            } elseif ($sort === 'recent') {
                $query->orderByDesc('created_at');
            } else {
                $query->orderByDesc('updated_at');
            }

            $discussions = $query->get();
            $discussions->each(fn (ForumDiscussion $d) => $d->setAttribute('route_id', (string) $d->id));

            $commentCounts = DB::table('forum_comments')
                ->select(['discussion_id', DB::raw('COUNT(*) as comments_count')])
                ->whereIn('discussion_id', $discussions->pluck('id')->all())
                ->groupBy('discussion_id')
                ->get()
                ->keyBy('discussion_id');

            $discussions->each(function (ForumDiscussion $d) use ($commentCounts) {
                $row = $commentCounts->get($d->id);
                $count = (int) ($row->comments_count ?? 0);
                $d->setAttribute('replies_count', max(0, $count - 1));
            });

            $lastIds = $discussions->pluck('last_comment_by')->filter()->map(fn ($v) => is_numeric($v) ? (int) $v : null)->filter()->unique()->values()->all();
            $lastMap = [];
            if (! empty($lastIds)) {
                $lastMap = DB::table('membres')
                    ->whereIn('id', $lastIds)
                    ->get(['id', 'username'])
                    ->mapWithKeys(fn ($u) => [(int) $u->id => (string) $u->username])
                    ->all();
            }
            $discussions->each(function (ForumDiscussion $d) use ($lastMap) {
                $d->setAttribute('last_comment_user', (object) ['username' => (string) ($lastMap[(int) ($d->last_comment_by ?? 0)] ?? '')]);
            });
        }

        return view('Forum.discussions', [
            'title' => 'Discussions',
            'category' => $category,
            'discussions' => $discussions,
            'q' => $q,
            'sort' => $sort,
            'film_id' => $filmId,
        ]);
    }

    public function show(Request $request, string $id)
    {
        $formatter = app(ForumContentFormatter::class);
        $userId = request()->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        if ($this->useMongo()) {
            $mongo = $this->mongo();
            $discussionOid = $this->toObjectId($id);

            $discussion = $mongo->table('forum_discussions')->where('_id', $discussionOid)->first();
            if (! $discussion) {
                abort(404);
            }
            $category = $mongo->table('forum_categories')->where('_id', $discussion->category_id)->first();
            if (! $category) {
                abort(404);
            }
            if ((int) ($category->admin_only ?? 0) === 1 && ! $this->isAdmin($request)) {
                abort(403);
            }

            $author = DB::table('membres')->where('id', (int) ($discussion->author_id ?? 0))->first(['username']);

            $discussion->route_id = (string) $this->mongoDocId($discussion);
            $discussion->id = $discussion->route_id;
            $discussion->author = (object) ['username' => (string) ($author?->username ?? '—')];
            $category->route_id = (string) $this->mongoDocId($category);
            $discussion->category = $category;
            $discussion->created_at = $this->normalizeDateValue($discussion->created_at ?? null);
            $discussion->updated_at = $this->normalizeDateValue($discussion->updated_at ?? null);
            $discussion->description_html = $formatter->toHtml((string) ($discussion->description ?? ''));

            $comments = $mongo->table('forum_comments')
                ->where('discussion_id', $discussionOid)
                ->orderBy('created_at')
                ->get();

            $authorIds = collect($comments)->pluck('author_id')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all();
            $authorMap = [];
            if (! empty($authorIds)) {
                $authorMap = DB::table('membres')
                    ->whereIn('id', $authorIds)
                    ->get(['id', 'username', 'photo_profil'])
                    ->mapWithKeys(fn ($u) => [(int) $u->id => ['username' => (string) $u->username, 'photo_profil' => (string) ($u->photo_profil ?? '')]])
                    ->all();
            }

            collect($comments)->each(function ($c) use ($formatter, $authorMap, $userId) {
                $c->route_id = (string) $this->mongoDocId($c);
                $c->id = $c->route_id;
                $c->parent_id = $c->parent_id ? (string) $c->parent_id : null;
                $a = $authorMap[(int) ($c->author_id ?? 0)] ?? null;
                $c->author = (object) [
                    'username' => (string) ($a['username'] ?? '—'),
                    'photo_profil' => (string) ($a['photo_profil'] ?? ''),
                ];
                $likes = is_array($c->likes_user_ids ?? null) ? $c->likes_user_ids : [];
                $likes = array_values(array_filter(array_map(static fn ($v) => is_numeric($v) ? (int) $v : null, $likes), static fn ($v) => $v !== null));
                $c->likes_count = count($likes);
                $c->is_liked = $userId ? in_array($userId, $likes, true) : false;
                $c->content_html = $formatter->toHtml((string) ($c->content ?? ''));
                $c->created_at = $this->normalizeDateValue($c->created_at ?? null);
                $c->edited_at = $this->normalizeDateValue($c->edited_at ?? null);
            });

            $mongo->table('forum_discussions')->raw(function ($collection) use ($discussionOid) {
                return $collection->updateOne(['_id' => $discussionOid], ['$inc' => ['views' => 1]]);
            });
        } else {
            $idInt = (int) $id;
            $discussion = ForumDiscussion::with(['author', 'category'])->findOrFail($idInt);
            if ($discussion->category?->admin_only && ! $this->isAdmin($request)) {
                abort(403);
            }
            $discussion->setAttribute('route_id', (string) $discussion->id);
            $discussion->category?->setAttribute('route_id', (string) $discussion->category?->id);

            $comments = ForumComment::query()
                ->with(['author', 'parent.author'])
                ->where('discussion_id', $idInt)
                ->orderBy('created_at')
                ->get();
            $comments->each(fn (ForumComment $c) => $c->setAttribute('route_id', (string) $c->id));

            $likeCounts = DB::table('forum_comment_likes')
                ->select(['comment_id', DB::raw('COUNT(*) as c')])
                ->whereIn('comment_id', $comments->pluck('id')->all())
                ->groupBy('comment_id')
                ->get()
                ->keyBy('comment_id');

            $userLikes = [];
            if ($userId) {
                $userLikes = DB::table('forum_comment_likes')
                    ->whereIn('comment_id', $comments->pluck('id')->all())
                    ->where('user_id', $userId)
                    ->pluck('comment_id')
                    ->map(fn ($v) => (int) $v)
                    ->toArray();
            }

            $discussion->setAttribute('description_html', $formatter->toHtml((string) ($discussion->description ?? '')));

            $comments->each(function (ForumComment $c) use ($likeCounts, $userLikes, $formatter) {
                $row = $likeCounts->get($c->id);
                $c->setAttribute('likes_count', (int) ($row->c ?? 0));
                $c->setAttribute('is_liked', in_array((int) $c->id, $userLikes, true));
                $c->setAttribute('content_html', $formatter->toHtml((string) ($c->content ?? '')));
            });

            DB::table('forum_discussions')->where('id', $idInt)->update([
                'views' => DB::raw('COALESCE(views,0)+1'),
            ]);
        }

        return view('Forum.show', [
            'title' => $discussion->titre,
            'discussion' => $discussion,
            'comments' => $comments,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureForumAccess($request);
        $this->ensureForumWrite($request);

        $userId = $request->session()->get('user_id');
        if (! $userId) {
            return redirect()->route('forum');
        }

        $data = $request->validate([
            'category_id' => ['required'],
            'titre' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string'],
        ]);

        $formatter = app(ForumContentFormatter::class);

        if ($this->useMongo()) {
            $mongo = $this->mongo();
            $categoryOid = $this->toObjectId((string) $data['category_id']);
            $category = $mongo->table('forum_categories')->where('_id', $categoryOid)->first();
            if (! $category) {
                abort(404);
            }
            if ((int) ($category->admin_only ?? 0) === 1 && ! $this->isAdmin($request)) {
                abort(403);
            }

            $now = now();
            $insertedId = null;
            $mongo->table('forum_discussions')->raw(function ($collection) use ($categoryOid, $data, $userId, $now, &$insertedId) {
                $r = $collection->insertOne([
                    'category_id' => $categoryOid,
                    'titre' => (string) $data['titre'],
                    'description' => (string) $data['description'],
                    'author_id' => (int) $userId,
                    'pinned' => false,
                    'locked' => false,
                    'views' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'last_comment_at' => null,
                    'last_comment_by' => null,
                ]);
                $insertedId = $r->getInsertedId();

                return $r;
            });

            $mentions = $formatter->extractMentions((string) $data['description']);
            $this->notifyMentions($mentions, (string) $insertedId, null, (int) $userId);

            return redirect()->route('forum.discussion', ['id' => (string) $insertedId]);
        }

        $categoryIdInt = (int) $data['category_id'];
        $category = ForumCategory::findOrFail($categoryIdInt);
        if ($category->admin_only && ! $this->isAdmin($request)) {
            abort(403);
        }

        $discussion = new ForumDiscussion;
        $discussion->category_id = $category->id;
        $discussion->titre = $data['titre'];
        $discussion->description = $data['description'];
        $discussion->author_id = $userId;
        $discussion->created_at = now();
        $discussion->updated_at = now();
        $discussion->save();

        $mentions = $formatter->extractMentions((string) $discussion->description);
        $this->notifyMentions($mentions, (string) $discussion->id, null, (int) $userId);

        return redirect()->route('forum.discussion', ['id' => (string) $discussion->id]);
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
            'titre' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string'],
        ]);

        $formatter = app(ForumContentFormatter::class);
        if ($this->useMongo()) {
            $mongo = $this->mongo();
            $discussionOid = $this->toObjectId($id);
            $discussion = $mongo->table('forum_discussions')->where('_id', $discussionOid)->first(['author_id']);
            if (! $discussion) {
                abort(404);
            }
            if (! $this->canEdit($request, $userId, (int) ($discussion->author_id ?? 0))) {
                abort(403);
            }

            $mongo->table('forum_discussions')->raw(function ($collection) use ($discussionOid, $data) {
                return $collection->updateOne(['_id' => $discussionOid], [
                    '$set' => [
                        'titre' => (string) $data['titre'],
                        'description' => (string) $data['description'],
                        'updated_at' => now(),
                    ],
                ]);
            });

            $mentions = $formatter->extractMentions((string) $data['description']);
            $this->notifyMentions($mentions, $id, null, $userId);

            return redirect()->route('forum.discussion', ['id' => $id]);
        }

        $idInt = (int) $id;
        $discussion = ForumDiscussion::with(['category'])->findOrFail($idInt);
        if (! $this->canEdit($request, $userId, (int) $discussion->author_id)) {
            abort(403);
        }

        $discussion->titre = $data['titre'];
        $discussion->description = $data['description'];
        $discussion->updated_at = now();
        $discussion->save();

        $mentions = $formatter->extractMentions((string) $discussion->description);
        $this->notifyMentions($mentions, (string) $discussion->id, null, $userId);

        return redirect()->route('forum.discussion', ['id' => (string) $discussion->id]);
    }

    public function destroy(Request $request, string $id)
    {
        $this->ensureForumAccess($request);
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return redirect()->route('forum');
        }

        if ($this->useMongo()) {
            $mongo = $this->mongo();
            $discussionOid = $this->toObjectId($id);
            $discussion = $mongo->table('forum_discussions')->where('_id', $discussionOid)->first(['author_id', 'category_id']);
            if (! $discussion) {
                abort(404);
            }
            if (! $this->canEdit($request, $userId, (int) ($discussion->author_id ?? 0))) {
                abort(403);
            }

            $mongo->table('forum_comments')->where('discussion_id', $discussionOid)->delete();
            $mongo->table('forum_discussions')->where('_id', $discussionOid)->delete();

            return redirect()->route('forum.category', ['id' => (string) ($discussion->category_id ?? '')]);
        }

        $idInt = (int) $id;
        $discussion = ForumDiscussion::findOrFail($idInt);
        if (! $this->canEdit($request, $userId, (int) $discussion->author_id)) {
            abort(403);
        }

        $commentIds = DB::table('forum_comments')->where('discussion_id', $idInt)->pluck('id')->all();
        if (! empty($commentIds)) {
            DB::table('forum_comment_likes')->whereIn('comment_id', $commentIds)->delete();
        }
        DB::table('forum_comments')->where('discussion_id', $idInt)->delete();
        DB::table('forum_discussions')->where('id', $idInt)->delete();

        return redirect()->route('forum.category', ['id' => (string) $discussion->category_id]);
    }

    private function notifyMentions(array $usernames, string $discussionId, ?string $commentId, int $fromUserId): void
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

        $url = url('/forum/discussion/'.$discussionId.($commentId ? '#comment-'.$commentId : ''));
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

    private function mongoDocId(object $doc)
    {
        return $doc->_id ?? $doc->id ?? null;
    }

    private function toObjectId(string $id)
    {
        try {
            return new ObjectId($id);
        } catch (\Throwable) {
            abort(404);
        }
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        if (is_object($value) && get_class($value) === 'MongoDB\\BSON\\UTCDateTime' && method_exists($value, 'toDateTime')) {
            return $value->toDateTime()->format('Y-m-d H:i:s');
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
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
