<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TmdbController extends Controller
{
    public function autofill(Request $request)
    {
        $title = trim((string) $request->query('title', ''));
        $type = trim((string) $request->query('type', 'movie')); // movie|tv
        $year = (int) $request->query('year', 0);
        if ($title === '') {
            return response()->json(['success' => false, 'error' => 'Titre requis'], 422);
        }

        $key = (string) env('TMDB_API_KEY', '');
        if ($key === '') {
            return response()->json(['success' => false, 'error' => 'Clé TMDb manquante'], 500);
        }

        $baseUrl = 'https://api.themoviedb.org/3';
        $imageBase = 'https://image.tmdb.org/t/p/w500';

        $searchEndpoint = $type === 'tv' ? '/search/tv' : '/search/movie';
        $searchParams = [
            'api_key' => $key,
            'query' => $title,
            'include_adult' => 'false',
            'language' => 'fr-FR',
        ];
        if ($year > 0) {
            $searchParams[$type === 'tv' ? 'first_air_date_year' : 'year'] = $year;
        }

        $searchRes = Http::get($baseUrl . $searchEndpoint, $searchParams);
        if (! $searchRes->ok()) {
            return response()->json(['success' => false, 'error' => 'Recherche TMDb échouée'], 502);
        }
        $search = $searchRes->json();
        $results = is_array($search['results'] ?? null) ? $search['results'] : [];
        if (empty($results)) {
            return response()->json(['success' => false, 'error' => 'Aucun résultat TMDb'], 404);
        }
        $first = $results[0];
        $id = (int) ($first['id'] ?? 0);
        if ($id <= 0) {
            return response()->json(['success' => false, 'error' => 'ID TMDb invalide'], 502);
        }

        $detailsEndpoint = $type === 'tv' ? "/tv/{$id}" : "/movie/{$id}";
        $detailsRes = Http::get($baseUrl . $detailsEndpoint, [
            'api_key' => $key,
            'language' => 'fr-FR',
            'append_to_response' => 'credits',
        ]);
        if (! $detailsRes->ok()) {
            return response()->json(['success' => false, 'error' => 'Détails TMDb échoués'], 502);
        }
        $details = $detailsRes->json();

        $titleOut = (string) ($details['title'] ?? $details['name'] ?? $title);
        $overview = (string) ($details['overview'] ?? '');
        $date = (string) ($details['release_date'] ?? $details['first_air_date'] ?? '');
        $yearOut = $date !== '' ? (int) substr($date, 0, 4) : ($year > 0 ? $year : null);

        $posterPath = (string) ($details['poster_path'] ?? '');
        $posterUrl = $posterPath !== '' ? $imageBase . $posterPath : null;

        $prodCompanies = is_array($details['production_companies'] ?? null) ? $details['production_companies'] : [];
        $studio = null;
        foreach ($prodCompanies as $pc) {
            if (! empty($pc['name'])) { $studio = (string) $pc['name']; break; }
        }

        $countries = is_array($details['production_countries'] ?? null) ? $details['production_countries'] : [];
        $countryNames = [];
        $countryIso = [];
        foreach ($countries as $c) {
            if (! empty($c['name'])) { $countryNames[] = (string) $c['name']; }
            if (! empty($c['iso_3166_1'])) { $countryIso[] = (string) $c['iso_3166_1']; }
        }

        $genres = is_array($details['genres'] ?? null) ? array_map(fn($g) => (string) ($g['name'] ?? ''), $details['genres']) : [];

        $credits = is_array($details['credits'] ?? null) ? $details['credits'] : [];
        $crew = is_array($credits['crew'] ?? null) ? $credits['crew'] : [];
        $auteur = null;
        foreach ($crew as $member) {
            $job = (string) ($member['job'] ?? '');
            if (in_array($job, ['Director', 'Writer'], true) && ! empty($member['name'])) {
                $auteur = (string) $member['name'];
                break;
            }
        }
        if ($auteur === null) {
            foreach ($crew as $member) {
                if (! empty($member['name'])) { $auteur = (string) $member['name']; break; }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'title' => $titleOut,
                'overview' => $overview,
                'year' => $yearOut,
                'poster_url' => $posterUrl,
                'countries' => $countryNames,
                'countries_iso' => $countryIso,
                'studio' => $studio,
                'auteur' => $auteur,
                'genres' => $genres,
                'tmdb_id' => $id,
                'type' => $type,
            ],
        ]);
    }
}
