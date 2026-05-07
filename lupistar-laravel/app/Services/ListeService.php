<?php

namespace App\Services;

use App\Models\Film;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListeService
{
    public function studiosForCategory(string $category): array
    {
        return DB::table('films as f')
            ->leftJoin('studios as s', 'f.studio_id', '=', 's.id')
            ->where('f.categorie', $category)
            ->whereNotNull('s.nom')
            ->selectRaw('DISTINCT s.nom as studio')
            ->orderBy('studio')
            ->pluck('studio')
            ->toArray();
    }

    public function yearsForCategory(string $category): array
    {
        return Film::query()
            ->selectRaw('DISTINCT date_sortie AS annee')
            ->where('categorie', $category)
            ->whereNotNull('date_sortie')
            ->orderBy('annee')
            ->pluck('annee')
            ->filter()
            ->values()
            ->toArray();
    }

    public function yearsForUserCategory(int $userId, string $category): array
    {
        $rows = DB::table('films as f')
            ->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('f.id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            })
            ->where('f.categorie', $category)
            ->whereNotNull('f.date_sortie')
            ->selectRaw('DISTINCT f.date_sortie AS annee')
            ->orderBy('annee')
            ->pluck('annee')
            ->filter()
            ->values()
            ->toArray();

        return $rows;
    }

    public function studiosForUserCategory(int $userId, string $category): array
    {
        return DB::table('films as f')
            ->leftJoin('studios as s', 'f.studio_id', '=', 's.id')
            ->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('f.id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            })
            ->where('f.categorie', $category)
            ->whereNotNull('s.nom')
            ->selectRaw('DISTINCT s.nom as studio')
            ->orderBy('studio')
            ->pluck('studio')
            ->toArray();
    }

    public function filmsForCategory(string $category, int $limit = 30): Collection
    {
        return Film::query()
            ->with('studio')
            ->where('categorie', $category)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function filmsForUserCategory(int $userId, string $category, int $limit = 30): Collection
    {
        return Film::query()
            ->with('studio')
            ->where('categorie', $category)
            ->whereIn('id', function ($q) use ($userId) {
                $q->select('films_id')->from('membres_films_list')->where('membres_id', $userId);
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function paysForCategory(string $category): array
    {
        return DB::table('films as f')
            ->leftJoin('pays as p', 'f.pays_id', '=', 'p.id')
            ->where('f.categorie', $category)
            ->whereNotNull('p.nom')
            ->selectRaw('DISTINCT p.nom as pays')
            ->orderBy('pays')
            ->pluck('pays')
            ->toArray();
    }

    public function paysForUserCategory(int $userId, string $category): array
    {
        return DB::table('films as f')
            ->leftJoin('pays as p', 'f.pays_id', '=', 'p.id')
            ->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('f.id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            })
            ->where('f.categorie', $category)
            ->whereNotNull('p.nom')
            ->selectRaw('DISTINCT p.nom as pays')
            ->orderBy('pays')
            ->pluck('pays')
            ->toArray();
    }

    public function studioCountsForCategory(string $category): array
    {
        return DB::table('films as f')
            ->leftJoin('studios as s', 'f.studio_id', '=', 's.id')
            ->where('f.categorie', $category)
            ->whereNotNull('s.nom')
            ->groupBy('s.nom')
            ->orderBy('s.nom')
            ->get([DB::raw('s.nom as label'), DB::raw('COUNT(*) as total')])
            ->map(fn ($r) => ['label' => (string) $r->label, 'total' => (int) $r->total])
            ->toArray();
    }

    public function yearCountsForCategory(string $category): array
    {
        return DB::table('films as f')
            ->where('f.categorie', $category)
            ->whereNotNull('f.date_sortie')
            ->groupBy('f.date_sortie')
            ->orderByDesc('f.date_sortie')
            ->get([DB::raw('f.date_sortie as label'), DB::raw('COUNT(*) as total')])
            ->map(fn ($r) => ['label' => (string) $r->label, 'total' => (int) $r->total])
            ->toArray();
    }

    public function paysCountsForCategory(string $category): array
    {
        return DB::table('films as f')
            ->leftJoin('pays as p', 'f.pays_id', '=', 'p.id')
            ->where('f.categorie', $category)
            ->whereNotNull('p.nom')
            ->groupBy('p.nom')
            ->orderBy('p.nom')
            ->get([DB::raw('p.nom as label'), DB::raw('COUNT(*) as total')])
            ->map(fn ($r) => ['label' => (string) $r->label, 'total' => (int) $r->total])
            ->toArray();
    }

    public function studioCountsForUserCategory(int $userId, string $category): array
    {
        return DB::table('films as f')
            ->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('f.id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            })
            ->leftJoin('studios as s', 'f.studio_id', '=', 's.id')
            ->where('f.categorie', $category)
            ->whereNotNull('s.nom')
            ->groupBy('s.nom')
            ->orderBy('s.nom')
            ->get([DB::raw('s.nom as label'), DB::raw('COUNT(*) as total')])
            ->map(fn ($r) => ['label' => (string) $r->label, 'total' => (int) $r->total])
            ->toArray();
    }

    public function yearCountsForUserCategory(int $userId, string $category): array
    {
        return DB::table('films as f')
            ->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('f.id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            })
            ->where('f.categorie', $category)
            ->whereNotNull('f.date_sortie')
            ->groupBy('f.date_sortie')
            ->orderByDesc('f.date_sortie')
            ->get([DB::raw('f.date_sortie as label'), DB::raw('COUNT(*) as total')])
            ->map(fn ($r) => ['label' => (string) $r->label, 'total' => (int) $r->total])
            ->toArray();
    }

    public function paysCountsForUserCategory(int $userId, string $category): array
    {
        return DB::table('films as f')
            ->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('f.id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            })
            ->leftJoin('pays as p', 'f.pays_id', '=', 'p.id')
            ->where('f.categorie', $category)
            ->whereNotNull('p.nom')
            ->groupBy('p.nom')
            ->orderBy('p.nom')
            ->get([DB::raw('p.nom as label'), DB::raw('COUNT(*) as total')])
            ->map(fn ($r) => ['label' => (string) $r->label, 'total' => (int) $r->total])
            ->toArray();
    }

    public function noteCountsForCategory(string $category): array
    {
        $avgNotesSub = DB::table('membres_films_list')
            ->select([
                'films_id',
                DB::raw('AVG(note) as note_moyenne_raw'),
            ])
            ->whereNotNull('note')
            ->groupBy('films_id');

        $select = [
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN avg_notes.note_moyenne_raw IS NULL THEN 1 ELSE 0 END) as sans_note'),
            DB::raw('SUM(CASE WHEN avg_notes.note_moyenne_raw = 10 THEN 1 ELSE 0 END) as superstar'),
        ];
        for ($i = 1; $i <= 10; $i++) {
            $select[] = DB::raw("SUM(CASE WHEN avg_notes.note_moyenne_raw >= {$i} THEN 1 ELSE 0 END) as lim_{$i}");
        }
        for ($i = 0; $i <= 9; $i++) {
            $min = $i;
            $max = $i + 1;
            $col = "r_{$min}_{$max}";
            $select[] = DB::raw("SUM(CASE WHEN avg_notes.note_moyenne_raw >= {$min} AND avg_notes.note_moyenne_raw < {$max} THEN 1 ELSE 0 END) as {$col}");
        }

        $row = DB::table('films as f')
            ->leftJoinSub($avgNotesSub, 'avg_notes', function ($join) {
                $join->on('f.id', '=', 'avg_notes.films_id');
            })
            ->where('f.categorie', $category)
            ->first($select);

        $out = [
            'total' => (int) ($row->total ?? 0),
            'sans_note' => (int) ($row->sans_note ?? 0),
            'superstar' => (int) ($row->superstar ?? 0),
            'limites' => [],
            'ranges' => [],
        ];
        for ($i = 1; $i <= 10; $i++) {
            $k = "lim_{$i}";
            $out['limites'][(string) $i] = (int) ($row->{$k} ?? 0);
        }
        for ($i = 0; $i <= 9; $i++) {
            $min = $i;
            $max = $i + 1;
            $k = "r_{$min}_{$max}";
            $out['ranges']["{$min}-{$max}"] = (int) ($row->{$k} ?? 0);
        }
        return $out;
    }

    public function noteCountsForUserCategory(int $userId, string $category): array
    {
        $select = [
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN mfl.note IS NULL THEN 1 ELSE 0 END) as sans_note'),
            DB::raw('SUM(CASE WHEN mfl.note = 10 THEN 1 ELSE 0 END) as superstar'),
        ];
        for ($i = 1; $i <= 10; $i++) {
            $select[] = DB::raw("SUM(CASE WHEN mfl.note >= {$i} THEN 1 ELSE 0 END) as lim_{$i}");
        }
        for ($i = 0; $i <= 9; $i++) {
            $min = $i;
            $max = $i + 1;
            $col = "r_{$min}_{$max}";
            $select[] = DB::raw("SUM(CASE WHEN mfl.note >= {$min} AND mfl.note < {$max} THEN 1 ELSE 0 END) as {$col}");
        }

        $row = DB::table('films as f')
            ->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('f.id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            })
            ->where('f.categorie', $category)
            ->first($select);

        $out = [
            'total' => (int) ($row->total ?? 0),
            'sans_note' => (int) ($row->sans_note ?? 0),
            'superstar' => (int) ($row->superstar ?? 0),
            'limites' => [],
            'ranges' => [],
        ];
        for ($i = 1; $i <= 10; $i++) {
            $k = "lim_{$i}";
            $out['limites'][(string) $i] = (int) ($row->{$k} ?? 0);
        }
        for ($i = 0; $i <= 9; $i++) {
            $min = $i;
            $max = $i + 1;
            $k = "r_{$min}_{$max}";
            $out['ranges']["{$min}-{$max}"] = (int) ($row->{$k} ?? 0);
        }
        return $out;
    }

    public function paginatedFilmsForCategory(string $category, array $filters, int $page, int $perPage = 36, ?int $userId = null): LengthAwarePaginator
    {
        return $this->paginatedFilms($category, $userId, $filters, $page, $perPage, false);
    }

    public function paginatedFilmsForUserCategory(int $userId, string $category, array $filters, int $page, int $perPage = 36): LengthAwarePaginator
    {
        return $this->paginatedFilms($category, $userId, $filters, $page, $perPage, true);
    }

    public function statsForCategory(string $category): array
    {
        return $this->stats($category, null, false);
    }

    public function statsForUserCategory(int $userId, string $category): array
    {
        return $this->stats($category, $userId, true);
    }

    private function paginatedFilms(string $category, ?int $userId, array $filters, int $page, int $perPage, bool $onlyMyList): LengthAwarePaginator
    {
        $query = Film::query()
            ->from('films')
            ->with(['studio', 'pays'])
            ->where('films.categorie', $category)
            ->select('films.*');

        if ($onlyMyList) {
            $query
                ->join('membres_films_list as mfl', function ($join) use ($userId) {
                    $join->on('films.id', '=', 'mfl.films_id')
                        ->where('mfl.membres_id', '=', $userId);
                })
                ->selectRaw('mfl.note as user_note');
        } else {
            $avgNotesSub = DB::table('membres_films_list')
                ->select([
                    'films_id',
                    DB::raw('AVG(note) as note_moyenne_raw'),
                ])
                ->whereNotNull('note')
                ->groupBy('films_id');

            $query
                ->leftJoinSub($avgNotesSub, 'avg_notes', function ($join) {
                    $join->on('films.id', '=', 'avg_notes.films_id');
                })
                ->addSelect(DB::raw('ROUND(avg_notes.note_moyenne_raw, 2) as note_moyenne_global'));
        }

        $recherche = trim((string) ($filters['recherche'] ?? ''));
        if ($recherche !== '') {
            $query->where('films.nom_film', 'like', '%'.$recherche.'%');
        }

        $studio = trim((string) ($filters['studio'] ?? ''));
        if ($studio !== '') {
            $studioIds = DB::table('studios')->where('nom', $studio)->pluck('id')->toArray();
            if (count($studioIds) === 0) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('films.studio_id', $studioIds);
            }
        }

        $annee = trim((string) ($filters['annee'] ?? ''));
        if ($annee !== '' && is_numeric($annee)) {
            $query->whereYear('films.date_sortie', (int) $annee);
        }

        $note = trim((string) ($filters['note'] ?? ''));
        if ($note === 'sans_note') {
            if ($onlyMyList) {
                $query->whereNull('mfl.note');
            } else {
                $query->whereNull('avg_notes.note_moyenne_raw');
            }
        } elseif (preg_match('/^(\d+(?:\.\d+)?)-(\d+(?:\.\d+)?)$/', $note, $m)) {
            $min = (float) $m[1];
            $max = (float) $m[2];
            if ($onlyMyList) {
                $query->where('mfl.note', '>=', $min)->where('mfl.note', '<', $max);
            } else {
                $query->where('avg_notes.note_moyenne_raw', '>=', $min)->where('avg_notes.note_moyenne_raw', '<', $max);
            }
        } elseif ($note !== '' && is_numeric($note)) {
            if ($onlyMyList) {
                if ((float) $note === 10.0) {
                    $query->where('mfl.note', '=', 10);
                } else {
                    $query->where('mfl.note', '>=', (float) $note);
                }
            } else {
                if ((float) $note === 10.0) {
                    $query->where('avg_notes.note_moyenne_raw', '=', 10);
                } else {
                    $query->where('avg_notes.note_moyenne_raw', '>=', (float) $note);
                }
            }
        }

        $statut = trim((string) ($filters['statut'] ?? ''));
        if (! $onlyMyList && $userId !== null && ($statut === 'in' || $statut === 'out')) {
            $query->leftJoin('membres_films_list as mfl_user', function ($join) use ($userId) {
                $join->on('films.id', '=', 'mfl_user.films_id')
                    ->where('mfl_user.membres_id', '=', $userId);
            });

            if ($statut === 'in') {
                $query->whereNotNull('mfl_user.films_id');
            } else {
                $query->whereNull('mfl_user.films_id');
            }
        }

        $pays = trim((string) ($filters['pays'] ?? ''));
        if ($pays !== '') {
            $paysIds = DB::table('pays')->where('nom', $pays)->pluck('id')->toArray();
            if (count($paysIds) === 0) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('films.pays_id', $paysIds);
            }
        }

        $type = trim((string) ($filters['type'] ?? ''));
        if ($category === 'Anime' && $type !== '') {
            $normalizedType = mb_strtolower($type);
            if ($normalizedType === 'film') {
                $query->where(function ($q) {
                    $q->whereNull('films.nbrEpisode')->orWhere('films.nbrEpisode', 0);
                });
            }
            if ($normalizedType === 'série' || $normalizedType === 'serie') {
                $query->where('films.nbrEpisode', '>', 0);
            }
        }

        $episodes = trim((string) ($filters['episodes'] ?? ''));
        if ($category === 'Anime' && $episodes !== '') {
            if ($episodes === '1') {
                $query->where(function ($q) {
                    $q->whereNull('films.nbrEpisode')->orWhere('films.nbrEpisode', 0);
                });
            } elseif ($episodes === '24+') {
                $query->where('films.nbrEpisode', '>=', 25);
            } elseif (preg_match('/^(\d+)-(\d+)$/', $episodes, $m)) {
                $min = (int) $m[1];
                $max = (int) $m[2];
                $query->whereBetween('films.nbrEpisode', [$min, $max]);
            } elseif ($episodes === '101') {
                $query->where('films.nbrEpisode', '>=', 101);
            }
        }

        return $query
            ->orderByDesc('films.date_sortie')
            ->orderBy('films.nom_film')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    private function stats(string $category, ?int $userId, bool $onlyMyList): array
    {
        $base = DB::table('films as f')->where('f.categorie', $category);

        if ($onlyMyList) {
            $base->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('f.id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            });
        }

        $total = (clone $base)->count('f.id');

        $topStudios = (clone $base)
            ->leftJoin('studios as s', 'f.studio_id', '=', 's.id')
            ->whereNotNull('s.nom')
            ->groupBy('s.nom')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(3)
            ->get([DB::raw('s.nom as studio'), DB::raw('COUNT(*) as total')])
            ->map(fn ($row) => ['studio' => $row->studio, 'total' => (int) $row->total])
            ->toArray();

        $bestDecadeRow = (clone $base)
            ->whereNotNull('f.date_sortie')
            ->selectRaw('FLOOR(f.date_sortie / 10) * 10 AS decade, COUNT(*) AS total')
            ->groupBy('decade')
            ->orderByDesc('total')
            ->first();

        $bestDecade = $bestDecadeRow ? (int) $bestDecadeRow->decade : null;

        return [
            'total_films' => (int) $total,
            'top_studios' => $topStudios,
            'best_decade' => $bestDecade,
        ];
    }
}
