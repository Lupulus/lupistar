<?php

namespace App\Services;

use App\Models\Film;
use App\Models\UserPreference;

class AccueilService
{
    public function categoriesOrderForUser(?int $userId): array
    {
        $default = ['Animation', 'Anime', "Série d'Animation", 'Film', 'Série'];

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
}
