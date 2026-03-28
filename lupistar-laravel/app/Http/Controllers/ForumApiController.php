<?php

namespace App\Http\Controllers;

use App\Models\Film;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForumApiController extends Controller
{
    public function searchTopics(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'activity');
        $filmId = is_numeric($request->query('film_id')) ? (int) $request->query('film_id') : null;

        if ($this->useMongo()) {
            $mongo = $this->mongo();
            $categoryIdRaw = trim((string) $request->query('category_id', ''));
            if ($categoryIdRaw === '') {
                return response()->json(['success' => false, 'items' => []], 422);
            }

            $categoryOid = $this->toObjectId($categoryIdRaw);
            $category = $mongo->table('forum_categories')->where('_id', $categoryOid)->first(['admin_only']);
            if (! $category) {
                return response()->json(['success' => false, 'items' => []], 404);
            }

            if ((int) ($category->admin_only ?? 0) === 1 && ! $this->isAdmin($request)) {
                return response()->json(['success' => false, 'items' => []], 403);
            }

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

            $rows = $query->limit(60)->get(['_id', 'titre', 'pinned', 'locked', 'views', 'created_at', 'updated_at', 'author_id']);
            $discussionIds = collect($rows)->map(fn ($d) => $this->mongoDocId($d))->filter()->values()->all();

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
            $authorList = collect($rows)->pluck('author_id')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all();
            if (! empty($authorList)) {
                $authorMap = DB::table('membres')
                    ->whereIn('id', $authorList)
                    ->get(['id', 'username'])
                    ->mapWithKeys(fn ($u) => [(int) $u->id => (string) $u->username])
                    ->all();
            }

            $items = collect($rows)->map(function ($r) use ($countsById, $authorMap) {
                $docId = $this->mongoDocId($r);
                $comments = (int) ($countsById[(string) $docId] ?? 0);
                return [
                    'id' => (string) $docId,
                    'titre' => (string) ($r->titre ?? ''),
                    'pinned' => (bool) ($r->pinned ?? false),
                    'locked' => (bool) ($r->locked ?? false),
                    'views' => (int) ($r->views ?? 0),
                    'author' => (string) ($authorMap[(int) ($r->author_id ?? 0)] ?? ''),
                    'created_at' => $this->normalizeDateValue($r->created_at ?? null),
                    'updated_at' => $this->normalizeDateValue($r->updated_at ?? null),
                    'replies_count' => max(0, $comments - 1),
                ];
            })->values();

            return response()->json(['success' => true, 'items' => $items]);
        }

        $categoryId = $request->query('category_id');
        $categoryId = is_numeric($categoryId) ? (int) $categoryId : null;
        if (! $categoryId) {
            return response()->json(['success' => false, 'items' => []], 422);
        }

        $category = DB::table('forum_categories')->where('id', $categoryId)->first(['admin_only']);
        if (! $category) {
            return response()->json(['success' => false, 'items' => []], 404);
        }

        if ((int) ($category->admin_only ?? 0) === 1 && ! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'items' => []], 403);
        }

        $countsSub = DB::table('forum_comments')
            ->select(['discussion_id', DB::raw('COUNT(*) as comments_count')])
            ->groupBy('discussion_id');

        $query = DB::table('forum_discussions as d')
            ->join('membres as m', 'm.id', '=', 'd.author_id')
            ->leftJoinSub($countsSub, 'cc', function ($join) {
                $join->on('cc.discussion_id', '=', 'd.id');
            })
            ->where('d.category_id', $categoryId);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('d.titre', 'like', '%'.$q.'%')
                    ->orWhere('d.description', 'like', '%'.$q.'%')
                    ->orWhere('m.username', 'like', '%'.$q.'%');
            });
        }

        if ($filmId) {
            $query->where('d.description', 'like', '%[film:'.$filmId.':%');
        }

        $query->orderByDesc('d.pinned');
        if ($sort === 'popular') {
            $query->orderByDesc('d.views');
        } elseif ($sort === 'recent') {
            $query->orderByDesc('d.created_at');
        } else {
            $query->orderByDesc('d.updated_at');
        }

        $items = $query
            ->limit(60)
            ->get([
                'd.id',
                'd.titre',
                'd.pinned',
                'd.locked',
                'd.views',
                'd.created_at',
                'd.updated_at',
                'm.username',
                DB::raw('COALESCE(cc.comments_count, 0) as comments_count'),
            ])
            ->map(function ($r) {
                $comments = (int) ($r->comments_count ?? 0);

                return [
                    'id' => (int) $r->id,
                    'titre' => (string) $r->titre,
                    'pinned' => (bool) $r->pinned,
                    'locked' => (bool) $r->locked,
                    'views' => (int) ($r->views ?? 0),
                    'author' => (string) ($r->username ?? ''),
                    'created_at' => (string) ($r->created_at ?? ''),
                    'updated_at' => (string) ($r->updated_at ?? ''),
                    'replies_count' => max(0, $comments - 1),
                ];
            })
            ->values();

        return response()->json(['success' => true, 'items' => $items]);
    }

    public function searchFilms(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['success' => true, 'items' => []]);
        }

        $items = Film::query()
            ->select(['id', 'nom_film', 'categorie', 'date_sortie'])
            ->where('nom_film', 'like', '%'.$q.'%')
            ->orderBy('nom_film')
            ->limit(12)
            ->get()
            ->map(fn (Film $f) => [
                'id' => (int) $f->id,
                'nom_film' => (string) $f->nom_film,
                'categorie' => (string) ($f->categorie ?? ''),
                'date_sortie' => (int) ($f->date_sortie ?? 0),
            ])
            ->values();

        return response()->json(['success' => true, 'items' => $items]);
    }

    public function searchUsers(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['success' => true, 'items' => []]);
        }

        $items = DB::table('membres')
            ->where('username', 'like', $q.'%')
            ->orderBy('username')
            ->limit(12)
            ->get(['id', 'username', 'photo_profil'])
            ->map(fn ($u) => [
                'id' => (int) $u->id,
                'username' => (string) $u->username,
                'photo_profil' => (string) ($u->photo_profil ?? ''),
            ]);

        return response()->json(['success' => true, 'items' => $items]);
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
            return new \MongoDB\BSON\ObjectId($id);
        } catch (\Throwable) {
            abort(404);
        }
    }

    private function isAdmin(Request $request): bool
    {
        $titre = (string) $request->session()->get('titre', 'Membre');

        return in_array($titre, ['Admin', 'Super-Admin'], true);
    }

    private function normalizeDateValue(mixed $value): string
    {
        if (is_object($value) && get_class($value) === 'MongoDB\\BSON\\UTCDateTime' && method_exists($value, 'toDateTime')) {
            return $value->toDateTime()->format(DATE_ATOM);
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return '';
    }
}
