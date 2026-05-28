<?php

namespace App\Services;

use App\Models\Film;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    public function recommendations(?int $userId, bool $includeSeen = false, int $limit = 18, ?int $seed = null): Collection
    {
        if (! $userId) {
            return $this->topRated($limit, $seed);
        }

        $seed = is_int($seed) ? $seed : random_int(1, 1000000);

        $seenIds = DB::table('membres_films_list')
            ->where('membres_id', $userId)
            ->pluck('films_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $categoryCounts = DB::table('films as f')
            ->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('f.id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            })
            ->groupBy('f.categorie')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->get([
                DB::raw('f.categorie as categorie'),
                DB::raw('COUNT(*) as total'),
            ])
            ->map(fn ($r) => ['categorie' => (string) $r->categorie, 'total' => (int) $r->total])
            ->toArray();

        $topCategories = array_values(array_filter(array_map(fn ($r) => $r['categorie'], $categoryCounts)));

        $topSousGenres = DB::table('films_sous_genres as fsg')
            ->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('fsg.film_id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            })
            ->groupBy('fsg.sous_genre_id')
            ->orderByDesc(DB::raw('SUM(COALESCE(mfl.note, 5))'))
            ->limit(6)
            ->pluck('fsg.sous_genre_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $topStudios = DB::table('films as f')
            ->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('f.id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            })
            ->groupBy('f.studio_id')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(3)
            ->pluck('f.studio_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $topAuteurs = DB::table('films as f')
            ->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('f.id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            })
            ->groupBy('f.auteur_id')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(3)
            ->pluck('f.auteur_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $bestDecade = $this->bestDecade($userId);
        $targetDecade = $bestDecade !== null ? $bestDecade + 10 : null;

        $avgNotesSub = DB::table('membres_films_list')
            ->select([
                'films_id',
                DB::raw('AVG(note) as note_moyenne_raw'),
            ])
            ->whereNotNull('note')
            ->groupBy('films_id');

        $candidatesQuery = Film::query()
            ->from('films')
            ->with(['pays'])
            ->select('films.*')
            ->leftJoinSub($avgNotesSub, 'avg_notes', function ($join) {
                $join->on('films.id', '=', 'avg_notes.films_id');
            })
            ->addSelect(DB::raw('ROUND(avg_notes.note_moyenne_raw, 2) as note_moyenne_global'));

        if (! $includeSeen && $seenIds !== []) {
            $candidatesQuery->whereNotIn('films.id', $seenIds);
        }

        $candidates = $candidatesQuery
            ->orderByDesc('films.date_sortie')
            ->orderByDesc('films.id')
            ->limit(650)
            ->get();

        if ($candidates->isEmpty()) {
            return $this->topRated($limit, $seed, $includeSeen ? [] : $seenIds);
        }

        $candidateIds = $candidates->pluck('id')->map(fn ($v) => (int) $v)->all();

        $sgRows = DB::table('films_sous_genres')
            ->whereIn('film_id', $candidateIds)
            ->get(['film_id', 'sous_genre_id']);

        $sgByFilm = [];
        foreach ($sgRows as $r) {
            $fid = (int) $r->film_id;
            $sgByFilm[$fid][] = (int) $r->sous_genre_id;
        }

        $catRank = [];
        foreach ($topCategories as $idx => $cat) {
            $catRank[$cat] = $idx;
        }

        $sgRank = [];
        foreach ($topSousGenres as $idx => $sg) {
            $sgRank[(int) $sg] = $idx;
        }

        $studioRank = [];
        foreach ($topStudios as $idx => $sid) {
            $studioRank[(int) $sid] = $idx;
        }

        $auteurRank = [];
        foreach ($topAuteurs as $idx => $aid) {
            $auteurRank[(int) $aid] = $idx;
        }

        $scored = $candidates->map(function (Film $film) use ($catRank, $sgRank, $studioRank, $auteurRank, $sgByFilm, $targetDecade) {
            $score = 0.0;

            $cat = (string) ($film->categorie ?? '');
            if ($cat !== '' && array_key_exists($cat, $catRank)) {
                $r = (int) $catRank[$cat];
                $score += max(0, 4 - $r) * 1.25;
            }

            $sid = is_numeric($film->studio_id) ? (int) $film->studio_id : null;
            if ($sid !== null && array_key_exists($sid, $studioRank)) {
                $r = (int) $studioRank[$sid];
                $score += max(0, 3 - $r) * 1.0;
            }

            $aid = is_numeric($film->auteur_id) ? (int) $film->auteur_id : null;
            if ($aid !== null && array_key_exists($aid, $auteurRank)) {
                $r = (int) $auteurRank[$aid];
                $score += max(0, 3 - $r) * 0.9;
            }

            $fsg = $sgByFilm[(int) $film->id] ?? [];
            foreach ($fsg as $gid) {
                if (! array_key_exists($gid, $sgRank)) continue;
                $r = (int) $sgRank[$gid];
                $score += max(0, 6 - $r) * 0.65;
            }

            $year = is_numeric($film->date_sortie) ? (int) $film->date_sortie : null;
            if ($targetDecade !== null && $year !== null) {
                $decade = (int) (floor($year / 10) * 10);
                if ($decade === $targetDecade) $score += 1.2;
                if ($decade === $targetDecade - 10) $score += 0.6;
            }

            $avg = $film->getAttribute('note_moyenne_global');
            $avgFloat = is_numeric($avg) ? (float) $avg : null;
            if ($avgFloat !== null) {
                $score += min(10.0, $avgFloat) * 0.25;
            }

            $film->setAttribute('_reco_score', $score);
            return $film;
        });

        $sorted = $scored->sortByDesc(fn (Film $f) => (float) $f->getAttribute('_reco_score'))->values();

        $quotas = $this->categoryQuotas($categoryCounts, $limit);
        $pickedIds = [];
        $out = collect();

        foreach ($quotas as $cat => $quota) {
            $items = $sorted->filter(fn (Film $f) => (string) $f->categorie === $cat)
                ->take($quota);
            foreach ($items as $film) {
                $fid = (int) $film->id;
                if (isset($pickedIds[$fid])) continue;
                $pickedIds[$fid] = true;
                $out->push($film);
            }
        }

        if ($out->count() < $limit) {
            foreach ($sorted as $film) {
                if ($out->count() >= $limit) break;
                $fid = (int) $film->id;
                if (isset($pickedIds[$fid])) continue;
                $pickedIds[$fid] = true;
                $out->push($film);
            }
        }

        return $this->finalizeFilms($out, $seed);
    }

    private function topRated(int $limit, ?int $seed, array $excludeIds = []): Collection
    {
        $seed = is_int($seed) ? $seed : random_int(1, 1000000);

        $avgNotesSub = DB::table('membres_films_list')
            ->select([
                'films_id',
                DB::raw('AVG(note) as note_moyenne_raw'),
            ])
            ->whereNotNull('note')
            ->groupBy('films_id');

        $query = Film::query()
            ->from('films')
            ->with(['pays'])
            ->select('films.*')
            ->leftJoinSub($avgNotesSub, 'avg_notes', function ($join) {
                $join->on('films.id', '=', 'avg_notes.films_id');
            })
            ->addSelect(DB::raw('ROUND(avg_notes.note_moyenne_raw, 2) as note_moyenne_global'))
            ->whereNotNull('avg_notes.note_moyenne_raw')
            ->where('avg_notes.note_moyenne_raw', '>=', 8.5);

        if ($excludeIds !== []) {
            $query->whereNotIn('films.id', $excludeIds);
        }

        $films = $query
            ->orderByDesc('avg_notes.note_moyenne_raw')
            ->limit(140)
            ->get();

        if ($films->isEmpty()) {
            $fallback = Film::query()
                ->from('films')
                ->with(['pays'])
                ->select('films.*')
                ->orderByDesc('films.id')
                ->limit($limit)
                ->get();

            return $this->finalizeFilms($fallback, $seed);
        }

        $slice = $films->shuffle()->take($limit)->values();
        return $this->finalizeFilms($slice, $seed);
    }

    private function finalizeFilms(Collection $films, int $seed): Collection
    {
        $accueil = app(AccueilService::class);
        foreach ($films as $film) {
            if (! $film instanceof Film) continue;
            $film->setAttribute('image_asset_path', $accueil->toPublicAssetPath($film->image_path));
            $film->setAttribute('_seed', $seed);
        }
        return $films;
    }

    private function categoryQuotas(array $categoryCounts, int $limit): array
    {
        $total = array_sum(array_map(fn ($r) => (int) ($r['total'] ?? 0), $categoryCounts));
        if ($total <= 0) {
            return [];
        }

        $quotas = [];
        $remaining = $limit;
        foreach ($categoryCounts as $row) {
            $cat = (string) ($row['categorie'] ?? '');
            $count = (int) ($row['total'] ?? 0);
            if ($cat === '' || $count <= 0) continue;
            $q = (int) round(($count / $total) * $limit);
            $q = max(2, $q);
            $quotas[$cat] = $q;
        }

        $sum = array_sum($quotas);
        if ($sum <= 0) {
            return [];
        }

        if ($sum > $limit) {
            arsort($quotas);
            while (array_sum($quotas) > $limit) {
                foreach ($quotas as $cat => $q) {
                    if (array_sum($quotas) <= $limit) break;
                    if ($q <= 1) continue;
                    $quotas[$cat] = $q - 1;
                }
            }
        } else {
            $remaining = $limit - $sum;
            if ($remaining > 0) {
                arsort($quotas);
                $cats = array_keys($quotas);
                $i = 0;
                while ($remaining > 0 && $cats !== []) {
                    $cat = $cats[$i % count($cats)];
                    $quotas[$cat] += 1;
                    $remaining -= 1;
                    $i += 1;
                }
            }
        }

        return $quotas;
    }

    private function bestDecade(int $userId): ?int
    {
        $seenByDecade = DB::table('films as f')
            ->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('f.id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            })
            ->selectRaw('FLOOR(f.date_sortie / 10) * 10 as decade, COUNT(*) as total_seen')
            ->groupBy('decade')
            ->havingRaw('COUNT(*) >= 6')
            ->get();

        if ($seenByDecade->isEmpty()) return null;

        $decades = $seenByDecade->pluck('decade')->map(fn ($v) => (int) $v)->all();
        $allByDecade = DB::table('films as f')
            ->selectRaw('FLOOR(f.date_sortie / 10) * 10 as decade, COUNT(*) as total_all')
            ->groupBy('decade')
            ->get()
            ->filter(fn ($r) => in_array((int) $r->decade, $decades, true))
            ->keyBy(fn ($r) => (int) $r->decade);

        $best = null;
        $bestRatio = 0.0;
        foreach ($seenByDecade as $row) {
            $decade = (int) $row->decade;
            $seen = (int) $row->total_seen;
            $all = isset($allByDecade[$decade]) ? (int) $allByDecade[$decade]->total_all : 0;
            if ($all <= 0) continue;
            $ratio = $seen / $all;
            if ($ratio > $bestRatio) {
                $bestRatio = $ratio;
                $best = $decade;
            }
        }

        return $best;
    }
}
