<?php

namespace App\Services;

use App\Models\Film;
use App\Models\UserPreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccueilService
{
    public function categoriesOrderForUser(?int $userId): array
    {
        $default = ['Film', 'Série', 'Animation', "Série d'Animation", 'Anime'];

        if (! $userId) {
            return $default;
        }

        $pref = UserPreference::query()
            ->where('user_id', $userId)
            ->where('preference_type', 'categories_order')
            ->value('preference_value');

        if (! is_string($pref) || $pref === '') {
            return $default;
        }

        $decoded = json_decode($pref, true);
        if (! is_array($decoded) || $decoded === []) {
            return $default;
        }

        return array_values(array_filter($decoded, fn ($v) => is_string($v) && $v !== ''));
    }

    public function recentFilmsByCategory(array $categories, int $limit = 15): array
    {
        $result = [];

        foreach ($categories as $category) {
            if (! is_string($category) || $category === '') {
                continue;
            }

            $result[$category] = Film::query()
                ->with('studio')
                ->where('categorie', $category)
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->each(function (Film $film) {
                    $film->setAttribute('image_asset_path', $this->toPublicAssetPath($film->image_path));
                });
        }

        return $result;
    }

    public function recentFilmsAll(int $limit = 30): Collection
    {
        $films = Film::query()
            ->with(['pays'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $films = $this->attachAvgNotes($films);

        return $films->each(function (Film $film) {
            $film->setAttribute('image_asset_path', $this->toPublicAssetPath($film->image_path));
        });
    }

    public function heroFilm(): ?Film
    {
        $year = (int) now()->year;
        $candidates = Film::query()
            ->with(['studio', 'auteur', 'pays'])
            ->where('date_sortie', '>=', $year - 1)
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        if ($candidates->isEmpty()) {
            $fallback = Film::query()->with(['studio', 'auteur', 'pays'])->orderByDesc('id')->first();
            if ($fallback) {
                $this->attachAvgNotes(collect([$fallback]));
                $fallback->setAttribute('image_asset_path', $this->toPublicAssetPath($fallback->image_path));
            }

            return $fallback;
        }

        $picked = $candidates->random(1)->first();
        if (! $picked) {
            return null;
        }

        $this->attachAvgNotes(collect([$picked]));
        $picked->setAttribute('image_asset_path', $this->toPublicAssetPath($picked->image_path));

        return $picked;
    }

    public function voyageForDay(int $minFilms = 5, int $limit = 8): array
    {
        $rows = DB::table('films as f')
            ->join('pays as p', 'f.pays_id', '=', 'p.id')
            ->groupBy('p.id', 'p.nom')
            ->havingRaw('COUNT(*) >= ?', [$minFilms])
            ->get([
                DB::raw('p.id as pays_id'),
                DB::raw('p.nom as pays_nom'),
                DB::raw('COUNT(*) as total'),
            ])
            ->map(fn ($r) => ['pays_id' => (int) $r->pays_id, 'pays_nom' => (string) $r->pays_nom, 'total' => (int) $r->total])
            ->toArray();

        if ($rows === []) {
            return ['pays' => null, 'films' => collect()];
        }

        $seed = (int) sprintf('%u', crc32(date('Y-m-d')));
        $weights = [];
        $sum = 0.0;
        foreach ($rows as $r) {
            $w = 1.0 / max(1.0, sqrt((float) ($r['total'] ?? 1)));
            $sum += $w;
            $weights[] = $w;
        }

        $x = (($seed % 1000000) / 1000000) * $sum;
        $acc = 0.0;
        $picked = $rows[0];
        foreach ($rows as $i => $r) {
            $acc += $weights[$i];
            if ($x <= $acc) {
                $picked = $r;
                break;
            }
        }

        $paysId = (int) $picked['pays_id'];
        $paysNom = (string) $picked['pays_nom'];

        $films = Film::query()
            ->with(['pays'])
            ->where('pays_id', $paysId)
            ->orderByRaw('MOD(films.id * '.$seed.', 1000000)')
            ->limit($limit)
            ->get();

        $films = $this->attachAvgNotes($films);
        $films->each(function (Film $film) {
            $film->setAttribute('image_asset_path', $this->toPublicAssetPath($film->image_path));
        });

        return ['pays' => $paysNom, 'films' => $films];
    }

    public function toPublicAssetPath(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', trim($path));

        while (str_starts_with($normalized, './')) {
            $normalized = substr($normalized, 2);
        }

        while (str_starts_with($normalized, '../')) {
            $normalized = substr($normalized, 3);
        }

        return ltrim($normalized, '/');
    }

    private function attachAvgNotes(Collection $films): Collection
    {
        $ids = $films->pluck('id')->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (int) $v)->values()->all();
        if ($ids === []) {
            return $films;
        }

        $avg = DB::table('membres_films_list')
            ->whereIn('films_id', $ids)
            ->whereNotNull('note')
            ->select(['films_id', DB::raw('ROUND(AVG(note), 2) as avg_note')])
            ->groupBy('films_id')
            ->get()
            ->keyBy(fn ($r) => (int) $r->films_id);

        foreach ($films as $film) {
            if (! $film instanceof Film) {
                continue;
            }
            $fid = (int) $film->id;
            if (! isset($avg[$fid])) {
                continue;
            }
            $film->setAttribute('note_moyenne_global', (float) $avg[$fid]->avg_note);
        }

        return $films;
    }
}
