<?php

namespace App\Http\Controllers;

use App\Models\ForumCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForumController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureDefaultCategories();

        $titre = (string) $request->session()->get('titre', 'Membre');
        $isAdmin = in_array($titre, ['Admin', 'Super-Admin'], true);

        if ($this->useMongo()) {
            $mongo = $this->mongo();

            $categories = $mongo->table('forum_categories')
                ->where('active', 1)
                ->when(! $isAdmin, fn ($q) => $q->where('admin_only', 0))
                ->orderBy('ordre')
                ->get()
                ->each(function ($cat) use ($mongo) {
                    $catId = $this->mongoDocId($cat);
                    if (! $catId) {
                        return;
                    }
                    $topicsCount = (int) $mongo->table('forum_discussions')->where('category_id', $catId)->count();
                    $views = (int) $mongo->table('forum_discussions')->where('category_id', $catId)->sum('views');
                    $last = $mongo->table('forum_discussions')
                        ->where('category_id', $catId)
                        ->orderByDesc('updated_at')
                        ->first(['updated_at']);

                    $cat->route_id = (string) $catId;
                    $cat->topics_count = $topicsCount;
                    $cat->views = $views;
                    $cat->last_activity_at = $this->normalizeDateValue($last?->updated_at ?? null);
                });
        } else {
            $stats = DB::table('forum_discussions')
                ->select([
                    'category_id',
                    DB::raw('COUNT(*) as topics_count'),
                    DB::raw('MAX(updated_at) as last_activity_at'),
                    DB::raw('SUM(COALESCE(views,0)) as views'),
                ])
                ->groupBy('category_id')
                ->get()
                ->keyBy('category_id');

            $categories = ForumCategory::query()
                ->where('active', 1)
                ->when(! $isAdmin, fn ($q) => $q->where('admin_only', 0))
                ->orderBy('ordre')
                ->get()
                ->each(function (ForumCategory $cat) use ($stats) {
                    $row = $stats->get($cat->id);
                    $cat->setAttribute('route_id', (string) $cat->id);
                    $cat->setAttribute('topics_count', (int) ($row->topics_count ?? 0));
                    $cat->setAttribute('views', (int) ($row->views ?? 0));
                    $cat->setAttribute('last_activity_at', $row->last_activity_at ?? null);
                });
        }

        return view('Forum.index', [
            'title' => 'Forum',
            'categories' => $categories,
        ]);
    }

    private function ensureDefaultCategories(): void
    {
        $now = now();
        $defaults = [
            [
                'nom' => 'Discussions Générales',
                'description' => 'Cinéma, séries, nouveautés…',
                'couleur' => '#ff8c00',
                'icone' => 'fas fa-comments',
                'ordre' => 1,
                'admin_only' => 0,
                'active' => 1,
                'created_at' => $now,
            ],
            [
                'nom' => 'Critique et avis',
                'description' => 'Reviews, critiques, notes et avis des membres.',
                'couleur' => '#3498db',
                'icone' => 'fas fa-star',
                'ordre' => 2,
                'admin_only' => 0,
                'active' => 1,
                'created_at' => $now,
            ],
            [
                'nom' => 'Suggestion & Reco...',
                'description' => 'Films à découvrir, recommandations d’autres utilisateurs.',
                'couleur' => '#2ecc71',
                'icone' => 'fas fa-lightbulb',
                'ordre' => 3,
                'admin_only' => 0,
                'active' => 1,
                'created_at' => $now,
            ],
            [
                'nom' => 'Support / Questions',
                'description' => 'Bugs du site, questions techniques.',
                'couleur' => '#e74c3c',
                'icone' => 'fas fa-life-ring',
                'ordre' => 4,
                'admin_only' => 0,
                'active' => 1,
                'created_at' => $now,
            ],
            [
                'nom' => 'Admin',
                'description' => 'Section administrative (réservée aux administrateurs)',
                'couleur' => '#34495e',
                'icone' => 'fas fa-shield-alt',
                'ordre' => 99,
                'admin_only' => 1,
                'active' => 1,
                'created_at' => $now,
            ],
        ];

        $allowedNames = array_map(static fn (array $c) => $c['nom'], $defaults);

        if ($this->useMongo()) {
            $mongo = $this->mongo();
            foreach ($defaults as $cat) {
                $exists = $mongo->table('forum_categories')->where('nom', $cat['nom'])->exists();
                if ($exists) {
                    $mongo->table('forum_categories')
                        ->where('nom', $cat['nom'])
                        ->update([
                            'description' => $cat['description'],
                            'couleur' => $cat['couleur'],
                            'icone' => $cat['icone'],
                            'ordre' => $cat['ordre'],
                            'admin_only' => $cat['admin_only'],
                            'active' => $cat['active'],
                        ]);
                    continue;
                }

                $mongo->table('forum_categories')->insert($cat);
            }

            $mongo->table('forum_categories')
                ->whereNotIn('nom', $allowedNames)
                ->update(['active' => 0]);
            return;
        }

        foreach ($defaults as $cat) {
            $exists = DB::table('forum_categories')->where('nom', $cat['nom'])->exists();
            if ($exists) {
                DB::table('forum_categories')
                    ->where('nom', $cat['nom'])
                    ->update([
                        'description' => $cat['description'],
                        'couleur' => $cat['couleur'],
                        'icone' => $cat['icone'],
                        'ordre' => $cat['ordre'],
                        'admin_only' => $cat['admin_only'],
                        'active' => $cat['active'],
                    ]);
                continue;
            }

            DB::table('forum_categories')->insert($cat);
        }

        DB::table('forum_categories')
            ->whereNotIn('nom', $allowedNames)
            ->update(['active' => 0]);
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
}
