<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            abort(403);
        }

        $restrictions = $this->currentRestrictions($request);
        $isAdminUser = $titre === 'Admin';
        $adminPermissions = [
            'approveFilm' => ! ($isAdminUser && in_array('Admin Film Approuver Off', $restrictions, true)),
            'deleteFilm' => ! ($isAdminUser && in_array('Admin Film Supprimer Off', $restrictions, true)),
            'modifyFilm' => ! ($isAdminUser && in_array('Admin Film Modifier Off', $restrictions, true)),
            'sendNotification' => ! ($isAdminUser && in_array('Admin Notif Off', $restrictions, true)),
            'studioConversions' => ! ($isAdminUser && in_array('Admin Conversions Off', $restrictions, true)),
        ];

        $stats = [
            'membres' => (int) DB::table('membres')->count(),
            'films' => (int) DB::table('films')->count(),
            'propositions_en_attente' => (int) DB::table('films_temp')->where('statut', 'en_attente')->count(),
            'notifications_non_lues' => (int) DB::table('notifications')->where('lu', false)->count(),
        ];

        $studios = $this->getOptions('studios', true);
        $auteurs = $this->getOptions('auteurs', true);
        $pays = $this->getOptions('pays', false);
        $sousGenres = $this->getOptions('sous_genres', false);

        $films = DB::table('films as f')
            ->leftJoin('studios as s', 'f.studio_id', '=', 's.id')
            ->leftJoin('auteurs as a', 'f.auteur_id', '=', 'a.id')
            ->leftJoin('pays as p', 'f.pays_id', '=', 'p.id')
            ->select(
                'f.id',
                'f.nom_film',
                'f.description',
                'f.categorie',
                'f.image_path',
                'f.ordre_suite',
                'f.date_sortie',
                'f.saison',
                'f.nbrEpisode',
                'f.studio_id',
                'f.auteur_id',
                'f.pays_id',
                DB::raw("COALESCE(s.nom, 'Inconnu') as studio_nom"),
                DB::raw("COALESCE(a.nom, 'Inconnu') as auteur_nom"),
                DB::raw("COALESCE(p.nom, 'Inconnu') as pays_nom"),
            )
            ->orderByDesc('f.id')
            ->limit(300)
            ->get();

        $filmIds = $films->pluck('id')->all();
        $sgByFilm = [];
        if (! empty($filmIds)) {
            $rows = DB::table('films_sous_genres as fsg')
                ->join('sous_genres as sg', 'fsg.sous_genre_id', '=', 'sg.id')
                ->whereIn('fsg.film_id', $filmIds)
                ->orderBy('sg.nom')
                ->get(['fsg.film_id', 'sg.id as sous_genre_id', 'sg.nom']);
            foreach ($rows as $r) {
                $sgByFilm[(int) $r->film_id][] = ['id' => (int) $r->sous_genre_id, 'nom' => (string) $r->nom];
            }
        }

        return view('Administration.index', [
            'title' => 'Administration',
            'stats' => $stats,
            'studios' => $studios,
            'auteurs' => $auteurs,
            'pays' => $pays,
            'sousGenres' => $sousGenres,
            'films' => $films,
            'sousGenresByFilm' => $sgByFilm,
            'adminPermissions' => $adminPermissions,
        ]);
    }

    public function propositions(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            abort(403);
        }

        $rows = DB::table('films_temp as ft')
            ->leftJoin('studios as s', 'ft.studio_id', '=', 's.id')
            ->leftJoin('auteurs as a', 'ft.auteur_id', '=', 'a.id')
            ->leftJoin('pays as p', 'ft.pays_id', '=', 'p.id')
            ->select(
                'ft.*',
                DB::raw("COALESCE(s.nom, ft.nouveau_studio, 'Inconnu') as studio_nom"),
                DB::raw("COALESCE(a.nom, ft.nouveau_auteur, 'Inconnu') as auteur_nom"),
                'p.nom as pays_nom'
            )
            ->where('ft.statut', 'en_attente')
            ->orderBy('ft.date_proposition', 'desc')
            ->get();

        return view('Administration.propositions', [
            'title' => 'Propositions',
            'propositions' => $rows,
        ]);
    }

    public function pendingFilms(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['success' => false, 'error' => 'Accès non autorisé'], 403);
        }

        $rows = DB::table('films_temp as ft')
            ->leftJoin('studios as s', 'ft.studio_id', '=', 's.id')
            ->leftJoin('auteurs as a', 'ft.auteur_id', '=', 'a.id')
            ->leftJoin('pays as p', 'ft.pays_id', '=', 'p.id')
            ->leftJoin('membres as m', 'ft.propose_par', '=', 'm.id')
            ->where('ft.statut', 'en_attente')
            ->orderByDesc('ft.date_proposition')
            ->get([
                'ft.*',
                's.nom as studio_nom',
                'a.nom as auteur_nom',
                'p.nom as pays_nom',
                'm.username as propose_par_pseudo',
            ]);

        $ids = $rows->pluck('id')->all();
        $sgByTemp = [];
        if (! empty($ids)) {
            $sgRows = DB::table('films_temp_sous_genres as ftsg')
                ->join('sous_genres as sg', 'ftsg.sous_genre_id', '=', 'sg.id')
                ->whereIn('ftsg.film_temp_id', $ids)
                ->orderBy('sg.nom')
                ->get(['ftsg.film_temp_id', 'sg.nom']);
            foreach ($sgRows as $r) {
                $sgByTemp[(int) $r->film_temp_id][] = (string) $r->nom;
            }
        }

        $films = $rows->map(function ($r) use ($sgByTemp) {
            $imagePath = (string) ($r->image_path ?? '');
            $date = $r->date_proposition ? (string) $r->date_proposition : null;
            $nouveauStudio = trim((string) ($r->nouveau_studio ?? ''));
            $nouveauAuteur = trim((string) ($r->nouveau_auteur ?? ''));

            return [
                'id' => (int) $r->id,
                'nom_film' => (string) $r->nom_film,
                'categorie' => (string) $r->categorie,
                'description' => (string) ($r->description ?? ''),
                'date_sortie' => (int) $r->date_sortie,
                'ordre_suite' => (int) ($r->ordre_suite ?? 0),
                'saison' => $r->saison !== null ? (int) $r->saison : null,
                'nbrEpisode' => $r->nbrEpisode !== null ? (int) $r->nbrEpisode : null,
                'studio_nom' => $r->studio_nom !== null ? (string) $r->studio_nom : ($nouveauStudio !== '' ? $nouveauStudio : 'Inconnu'),
                'auteur_nom' => $r->auteur_nom !== null ? (string) $r->auteur_nom : ($nouveauAuteur !== '' ? $nouveauAuteur : 'Inconnu'),
                'pays_nom' => $r->pays_nom !== null ? (string) $r->pays_nom : 'Inconnu',
                'propose_par' => (int) ($r->propose_par ?? 0),
                'propose_par_pseudo' => (string) ($r->propose_par_pseudo ?? 'Inconnu'),
                'date_proposition' => $date,
                'date_proposition_formatted' => $date ? date('d/m/Y H:i', strtotime($date)) : '',
                'image_path' => $imagePath !== '' ? asset($imagePath) : '',
                'sous_genres' => $sgByTemp[(int) $r->id] ?? [],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'count' => $films->count(),
            'films' => $films,
        ]);
    }

    public function addFilm(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['success' => false, 'error' => 'Accès non autorisé'], 403);
        }

        $data = $request->validate([
            'nom_film' => ['required', 'string', 'max:75'],
            'categorie' => ['required', 'in:Film,Animation,Anime,Série,Série d\'Animation'],
            'anime_type' => ['nullable', 'in:Film,Série'],
            'description' => ['nullable', 'string', 'max:400'],
            'date_sortie' => ['required', 'integer', 'min:1900', 'max:2099'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
            'image_url' => ['nullable', 'url'],
            'ordre_suite' => ['nullable', 'integer', 'min:1', 'max:25'],
            'saison' => ['nullable', 'integer', 'min:1', 'max:100'],
            'nbrEpisode' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'studio_id' => ['required'],
            'nouveau_studio' => ['nullable', 'string', 'max:30'],
            'auteur_id' => ['required'],
            'nouveau_auteur' => ['nullable', 'string', 'max:30'],
            'pays_id' => ['required', 'integer'],
            'sous_genres' => ['required', 'array', 'min:1'],
            'sous_genres.*' => ['integer'],
        ]);

        $isSerie = in_array($data['categorie'], ['Série', "Série d'Animation"], true) || ($data['categorie'] === 'Anime' && ($data['anime_type'] ?? '') === 'Série');
        if ($isSerie) {
            if (($data['saison'] ?? null) === null) {
                $data['saison'] = 1;
            }
            if (($data['nbrEpisode'] ?? null) === null) {
                return response()->json(['success' => false, 'error' => "Le nombre d'épisodes est requis pour une série."], 422);
            }
            $data['ordre_suite'] = null;
        } else {
            $data['saison'] = null;
            $data['nbrEpisode'] = null;
            if (($data['ordre_suite'] ?? null) === null) {
                $data['ordre_suite'] = 1;
            }
        }

        $existsQuery = DB::table('films')
            ->whereRaw('LOWER(nom_film) = LOWER(?)', [$data['nom_film']])
            ->where('date_sortie', (int) $data['date_sortie']);
        if ($isSerie) {
            $existsQuery->where('saison', (int) ($data['saison'] ?? 1))->whereNull('ordre_suite');
        } else {
            $existsQuery->where('ordre_suite', (int) ($data['ordre_suite'] ?? 1))->whereNull('saison');
        }
        if ($existsQuery->exists()) {
            return response()->json(['success' => false, 'error' => 'Ce film existe déjà (même titre et même année).'], 422);
        }

        $studioId = $this->resolveStudioId((string) $data['studio_id'], $data['nouveau_studio'] ?? null, $data['categorie']);
        $auteurId = $this->resolveAuteurId((string) $data['auteur_id'], $data['nouveau_auteur'] ?? null, $data['categorie']);
        $paysId = (int) $data['pays_id'];

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->storeFilmImage($request->file('image'), $data['nom_film'], (int) $data['date_sortie'], (int) ($data['ordre_suite'] ?? 1));
        } elseif (! empty($data['image_url'])) {
            $imagePath = $this->storeFilmImageFromUrl((string) $data['image_url'], $data['nom_film'], (int) $data['date_sortie'], (int) ($data['ordre_suite'] ?? 1));
        }
        if ($imagePath === null) {
            return response()->json(['success' => false, 'error' => "Impossible de sauvegarder l'image."], 500);
        }

        DB::beginTransaction();
        try {
            $filmId = DB::table('films')->insertGetId([
                'nom_film' => $data['nom_film'],
                'categorie' => $data['categorie'],
                'description' => $data['description'] ?? '',
                'image_path' => $imagePath,
                'date_sortie' => (int) $data['date_sortie'],
                'ordre_suite' => $data['ordre_suite'],
                'saison' => $data['saison'],
                'nbrEpisode' => $data['nbrEpisode'],
                'note_moyenne' => 0,
                'studio_id' => $studioId,
                'auteur_id' => $auteurId,
                'pays_id' => $paysId,
            ]);

            $rows = array_map(fn ($sid) => ['film_id' => $filmId, 'sous_genre_id' => (int) $sid], $data['sous_genres']);
            DB::table('films_sous_genres')->insert($rows);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'error' => "Erreur lors de l'ajout."], 500);
        }

        return response()->json(['success' => true, 'message' => 'Film ajouté avec succès.']);
    }

    public function syncSousGenresFromTmdb(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['success' => false, 'error' => 'Accès non autorisé'], 403);
        }
        $key = (string) env('TMDB_API_KEY', '');
        if ($key === '') {
            return response()->json(['success' => false, 'error' => 'Clé TMDb manquante'], 500);
        }
        try {
            $movie = Http::get('https://api.themoviedb.org/3/genre/movie/list', ['api_key' => $key, 'language' => 'fr-FR'])->json();
            $tv = Http::get('https://api.themoviedb.org/3/genre/tv/list', ['api_key' => $key, 'language' => 'fr-FR'])->json();
            $names = collect([(array) ($movie['genres'] ?? []), (array) ($tv['genres'] ?? [])])
                ->flatten(1)
                ->pluck('name')
                ->filter()
                ->map(fn ($n) => (string) $n)
                ->unique()
                ->values()
                ->all();
            $ignore = ['Animation', 'Film', 'Série', "Série d'Animation", 'Anime'];
            $final = array_values(array_filter($names, fn ($n) => ! in_array($n, $ignore, true)));
            $existing = DB::table('sous_genres')->pluck('nom')->map(fn ($n) => (string) $n)->all();
            $toInsert = array_values(array_diff($final, $existing));
            foreach ($toInsert as $nom) {
                DB::table('sous_genres')->insert(['nom' => $nom]);
            }
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Échec de synchronisation des sous-genres'], 502);
        }

        return response()->json(['success' => true, 'inserted' => $toInsert ?? []]);
    }

    public function approve(Request $request, int $id)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            abort(403);
        }
        if ($titre === 'Admin' && $this->hasRestriction($request, 'Admin Film Approuver Off')) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'error' => 'Action bloquée par restriction'], 403)
                : abort(403);
        }

        $data = $request->validate([
            'nom_film' => ['required', 'string', 'max:75'],
            'categorie' => ['required', 'in:Film,Animation,Anime,Série,Série d\'Animation'],
            'description' => ['nullable', 'string'],
            'date_sortie' => ['required', 'integer'],
            'ordre_suite' => ['nullable', 'integer'],
            'saison' => ['nullable', 'integer'],
            'nbrEpisode' => ['nullable', 'integer'],
            'commentaire_admin' => ['nullable', 'string'],
            'new_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $ft = DB::table('films_temp')->where('id', $id)->where('statut', 'en_attente')->first();
        if (! $ft) {
            return $request->expectsJson() ? response()->json(['success' => false, 'error' => 'Film introuvable'], 404) : back();
        }

        $isSerie = in_array($data['categorie'], ['Série', "Série d'Animation"], true) || (($data['saison'] ?? null) !== null);
        $existsQuery = DB::table('films')
            ->whereRaw('LOWER(nom_film) = LOWER(?)', [$data['nom_film']])
            ->where('date_sortie', (int) $data['date_sortie']);
        if ($isSerie) {
            $existsQuery->where('saison', (int) ($data['saison'] ?? 1))->whereNull('ordre_suite');
        } else {
            $existsQuery->where('ordre_suite', (int) ($data['ordre_suite'] ?? 1))->whereNull('saison');
        }
        if ($existsQuery->exists()) {
            return $request->expectsJson() ? response()->json(['success' => false, 'error' => 'Ce film existe déjà dans la base de données (même titre et même année).'], 422) : back();
        }

        $imageFinal = null;
        if ($request->hasFile('new_image')) {
            $imageFinal = $this->storeFilmImage($request->file('new_image'), $data['nom_film'], (int) $data['date_sortie'], (int) ($data['ordre_suite'] ?? 1));
            $this->deleteTempImage((string) ($ft->image_path ?? ''));
        } else {
            $imageFinal = $this->moveTempImageToPublicList($data['nom_film'], (int) $data['date_sortie'], (int) ($data['ordre_suite'] ?? 1), (string) ($ft->image_path ?? ''));
        }

        DB::beginTransaction();
        try {
            $studioId = $ft->studio_id !== null ? (int) $ft->studio_id : $this->resolveStudioId('autre', $ft->nouveau_studio ?? null, (string) $data['categorie']);
            $auteurId = $ft->auteur_id !== null ? (int) $ft->auteur_id : $this->resolveAuteurId('autre', $ft->nouveau_auteur ?? null, (string) $data['categorie']);

            $filmId = DB::table('films')->insertGetId([
                'nom_film' => $data['nom_film'],
                'categorie' => $data['categorie'],
                'description' => $data['description'] ?? $ft->description,
                'image_path' => $imageFinal ?: ($ft->image_path ?: ''),
                'date_sortie' => (int) $data['date_sortie'],
                'ordre_suite' => $data['ordre_suite'] ?? $ft->ordre_suite,
                'saison' => $data['saison'] ?? $ft->saison,
                'nbrEpisode' => $data['nbrEpisode'] ?? $ft->nbrEpisode,
                'saison_detaillee' => $ft->saison_detaillee ?? 1,
                'note_moyenne' => 0,
                'studio_id' => $studioId,
                'auteur_id' => $auteurId,
                'pays_id' => $ft->pays_id,
            ]);

            $sg = DB::table('films_temp_sous_genres')->where('film_temp_id', $id)->pluck('sous_genre_id')->all();
            if (! empty($sg)) {
                $rows = array_map(fn ($sid) => ['film_id' => $filmId, 'sous_genre_id' => $sid], $sg);
                DB::table('films_sous_genres')->insert($rows);
            }

            DB::table('films_temp_sous_genres')->where('film_temp_id', $id)->delete();
            DB::table('films_temp')->where('id', $id)->update([
                'statut' => 'approuve',
                'description' => null,
                'image_path' => null,
                'nouveau_studio' => null,
                'studio_id' => null,
                'nouveau_auteur' => null,
                'auteur_id' => null,
                'pays_id' => null,
                'commentaire_admin' => $data['commentaire_admin'] ?? ($ft->commentaire_admin ?? null),
            ]);

            $this->applyRewardsForApprovedFilms((int) $ft->propose_par);

            $this->notifyUser(
                (int) $ft->propose_par,
                'Film approuvé',
                'Ta proposition "'.$data['nom_film'].'" a été approuvée.',
                'film_approval'
            );
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return $request->expectsJson() ? response()->json(['success' => false, 'error' => 'Erreur lors de l’approbation'], 500) : back();
        }

        return $request->expectsJson() ? response()->json(['success' => true]) : redirect()->route('administration.propositions');
    }

    public function reject(Request $request, int $id)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            abort(403);
        }

        $data = $request->validate([
            'raison_rejet' => ['nullable', 'string', 'max:100'],
            'commentaire_admin' => ['nullable', 'string'],
        ]);

        $ft = DB::table('films_temp')->where('id', $id)->where('statut', 'en_attente')->first();
        if (! $ft) {
            return $request->expectsJson() ? response()->json(['success' => false, 'error' => 'Film introuvable'], 404) : back();
        }

        $raison = trim((string) ($data['raison_rejet'] ?? ''));
        if ($request->expectsJson() && $raison === '') {
            return response()->json(['success' => false, 'error' => 'Une raison de rejet est requise.'], 422);
        }

        DB::beginTransaction();
        try {
            $this->deleteTempImage((string) ($ft->image_path ?? ''));
            DB::table('films_temp_sous_genres')->where('film_temp_id', $id)->delete();
            DB::table('films_temp')->where('id', $id)->delete();

            $msg = 'Votre proposition de film pour "'.$ft->nom_film.'" n’a pas été approuvée';
            if ($raison !== '') {
                $msg .= ' pour la raison : '.$raison;
            }
            $comment = trim((string) ($data['commentaire_admin'] ?? ''));
            if ($comment !== '') {
                $msg .= '. Commentaire administrateur : '.$comment;
            }

            $this->notifyUser((int) $ft->propose_par, 'Film non approuvé', $msg, 'film_rejection');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return $request->expectsJson() ? response()->json(['success' => false, 'error' => 'Erreur lors du rejet'], 500) : back();
        }

        return $request->expectsJson() ? response()->json(['success' => true]) : redirect()->route('administration.propositions');
    }

    public function studiosByCategorie(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['success' => false], 403);
        }

        $categorie = trim((string) $request->query('categorie', ''));
        $q = DB::table('studios')->orderBy('nom');
        if ($categorie !== '') {
            $q->where('categorie', 'like', '%'.$categorie.'%');
        }
        $rows = $q->get(['id', 'nom']);

        return response()->json(['success' => true, 'studios' => $rows]);
    }

    public function auteursByCategorie(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['success' => false], 403);
        }

        $categorie = trim((string) $request->query('categorie', ''));
        $q = DB::table('auteurs')->orderBy('nom');
        if ($categorie !== '') {
            $q->where('categorie', 'like', '%'.$categorie.'%');
        }
        $rows = $q->get(['id', 'nom']);

        return response()->json(['success' => true, 'auteurs' => $rows]);
    }

    public function autocompleteStudios(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json([], 403);
        }

        $search = trim((string) $request->query('search', ''));
        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }
        $categorie = trim((string) $request->query('categorie', ''));

        $q = DB::table('studios')
            ->where('nom', 'like', '%'.$search.'%')
            ->orderBy('nom')
            ->limit(10);
        if ($categorie !== '') {
            $q->where('categorie', 'like', '%'.$categorie.'%');
        }

        return response()->json($q->pluck('nom')->all());
    }

    public function autocompleteAuteurs(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json([], 403);
        }

        $search = trim((string) $request->query('search', ''));
        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }
        $categorie = trim((string) $request->query('categorie', ''));

        $q = DB::table('auteurs')
            ->where('nom', 'like', '%'.$search.'%')
            ->orderBy('nom')
            ->limit(10);
        if ($categorie !== '') {
            $q->where('categorie', 'like', '%'.$categorie.'%');
        }

        return response()->json($q->pluck('nom')->all());
    }

    public function deleteFilm(Request $request, int $id)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        if ($titre === 'Admin' && $this->hasRestriction($request, 'Admin Film Supprimer Off')) {
            return response()->json(['error' => 'Action bloquée par restriction'], 403);
        }

        $film = DB::table('films')->where('id', $id)->first(['id', 'image_path']);
        if (! $film) {
            return response()->json(['error' => 'Film introuvable'], 404);
        }

        $imagePath = (string) ($film->image_path ?? '');

        DB::beginTransaction();
        try {
            DB::table('films_sous_genres')->where('film_id', $id)->delete();
            $deleted = DB::table('films')->where('id', $id)->delete();
            if ($deleted !== 1) {
                DB::rollBack();

                return response()->json(['error' => 'Film introuvable'], 404);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => 'Erreur lors de la suppression'], 500);
        }

        if ($imagePath !== '' && str_starts_with($imagePath, 'publiclisteimg/')) {
            $abs = public_path($imagePath);
            if (is_file($abs)) {
                @unlink($abs);
            }
        }

        return response()->noContent();
    }

    public function modifyFilm(Request $request, int $id)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['success' => false, 'error' => 'Accès non autorisé'], 403);
        }
        if ($titre === 'Admin' && $this->hasRestriction($request, 'Admin Film Modifier Off')) {
            return response()->json(['success' => false, 'error' => 'Action bloquée par restriction'], 403);
        }

        $data = $request->validate([
            'nom_film' => ['required', 'string', 'max:75'],
            'categorie' => ['required', 'in:Film,Animation,Anime,Série,Série d\'Animation'],
            'description' => ['nullable', 'string', 'max:400'],
            'ordre_suite' => ['nullable', 'integer', 'min:1', 'max:25'],
            'saison' => ['nullable', 'integer', 'min:1', 'max:100'],
            'nbrEpisode' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'date_sortie' => ['required', 'integer', 'min:1900', 'max:2099'],
            'studio_id' => ['required', 'integer'],
            'auteur_id' => ['required', 'integer'],
            'pays_id' => ['required', 'integer'],
            'sous_genres' => ['required', 'array', 'min:1'],
            'sous_genres.*' => ['integer'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
            'image_url' => ['nullable', 'url'],
        ]);

        $isSerie = in_array($data['categorie'], ['Série', "Série d'Animation"], true);
        if ($isSerie) {
            if (($data['saison'] ?? null) === null) {
                $data['saison'] = 1;
            }
            if (($data['nbrEpisode'] ?? null) === null) {
                return response()->json(['success' => false, 'error' => "Le nombre d'épisodes est requis pour une série."], 422);
            }
            $data['ordre_suite'] = null;
        } else {
            $data['saison'] = null;
            $data['nbrEpisode'] = null;
            if (($data['ordre_suite'] ?? null) === null) {
                $data['ordre_suite'] = 1;
            }
        }

        $existsQuery = DB::table('films')
            ->whereRaw('LOWER(nom_film) = LOWER(?)', [$data['nom_film']])
            ->where('date_sortie', (int) $data['date_sortie'])
            ->where('id', '<>', $id);
        if ($isSerie) {
            $existsQuery->where('saison', (int) ($data['saison'] ?? 1))->whereNull('ordre_suite');
        } else {
            $existsQuery->where('ordre_suite', (int) ($data['ordre_suite'] ?? 1))->whereNull('saison');
        }
        if ($existsQuery->exists()) {
            return response()->json(['success' => false, 'error' => 'Ce film existe déjà (même titre et même année).'], 422);
        }

        DB::beginTransaction();
        try {
            $update = [
                'nom_film' => $data['nom_film'],
                'categorie' => $data['categorie'],
                'description' => $data['description'] ?? '',
                'ordre_suite' => $data['ordre_suite'],
                'saison' => $data['saison'],
                'nbrEpisode' => $data['nbrEpisode'],
                'date_sortie' => (int) $data['date_sortie'],
                'studio_id' => (int) $data['studio_id'],
                'auteur_id' => (int) $data['auteur_id'],
                'pays_id' => (int) $data['pays_id'],
            ];

            $newImageRel = null;
            if ($request->hasFile('image')) {
                $old = DB::table('films')->where('id', $id)->value('image_path');
                if ($old && str_starts_with((string) $old, 'publiclisteimg/')) {
                    $oldAbs = public_path((string) $old);
                    if (is_file($oldAbs)) {
                        @unlink($oldAbs);
                    }
                }
                $ordre = $isSerie ? 1 : (int) ($data['ordre_suite'] ?? 1);
                $newImageRel = $this->storeFilmImage($request->file('image'), $data['nom_film'], (int) $data['date_sortie'], $ordre);
                if ($newImageRel) {
                    $update['image_path'] = $newImageRel;
                }
            } elseif (! empty($data['image_url'])) {
                $old = DB::table('films')->where('id', $id)->value('image_path');
                if ($old && str_starts_with((string) $old, 'publiclisteimg/')) {
                    $oldAbs = public_path((string) $old);
                    if (is_file($oldAbs)) {
                        @unlink($oldAbs);
                    }
                }
                $ordre = $isSerie ? 1 : (int) ($data['ordre_suite'] ?? 1);
                $newImageRel = $this->storeFilmImageFromUrl((string) $data['image_url'], $data['nom_film'], (int) $data['date_sortie'], $ordre);
                if ($newImageRel) {
                    $update['image_path'] = $newImageRel;
                } else {
                    DB::rollBack();

                    return response()->json(['success' => false, 'error' => "Impossible de sauvegarder l'image."], 500);
                }
            }

            DB::table('films')->where('id', $id)->update($update);

            DB::table('films_sous_genres')->where('film_id', $id)->delete();
            $rows = array_map(fn ($sid) => ['film_id' => $id, 'sous_genre_id' => (int) $sid], $data['sous_genres']);
            DB::table('films_sous_genres')->insert($rows);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'error' => 'Erreur lors de la modification'], 500);
        }

        return response()->json(['success' => true]);
    }

    public function filmsList(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['success' => false, 'error' => 'Accès non autorisé'], 403);
        }
        $rows = DB::table('films as f')
            ->leftJoin('studios as s', 'f.studio_id', '=', 's.id')
            ->leftJoin('auteurs as a', 'f.auteur_id', '=', 'a.id')
            ->leftJoin('pays as p', 'f.pays_id', '=', 'p.id')
            ->select(
                'f.id',
                'f.nom_film',
                'f.categorie',
                'f.image_path',
                'f.date_sortie',
                DB::raw("COALESCE(s.nom, 'Inconnu') as studio_nom"),
                DB::raw("COALESCE(a.nom, 'Inconnu') as auteur_nom"),
                DB::raw("COALESCE(p.nom, 'Inconnu') as pays_nom"),
            )
            ->orderByDesc('f.id')
            ->limit(500)
            ->get();

        $films = $rows->map(function ($r) {
            $img = (string) ($r->image_path ?? '');

            return [
                'id' => (int) $r->id,
                'nom_film' => (string) $r->nom_film,
                'categorie' => (string) $r->categorie,
                'studio_nom' => (string) $r->studio_nom,
                'pays_nom' => (string) $r->pays_nom,
                'date_sortie' => (int) $r->date_sortie,
                'image' => $img !== '' ? asset($img) : '',
            ];
        })->values();

        return response()->json(['success' => true, 'films' => $films]);
    }

    public function filmDetails(Request $request, int $id)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['success' => false, 'error' => 'Accès non autorisé'], 403);
        }
        $film = DB::table('films as f')
            ->leftJoin('studios as s', 'f.studio_id', '=', 's.id')
            ->leftJoin('auteurs as a', 'f.auteur_id', '=', 'a.id')
            ->leftJoin('pays as p', 'f.pays_id', '=', 'p.id')
            ->where('f.id', $id)
            ->select(
                'f.*',
                DB::raw("COALESCE(s.nom, 'Inconnu') as studio_nom"),
                DB::raw("COALESCE(a.nom, 'Inconnu') as auteur_nom"),
                DB::raw("COALESCE(p.nom, 'Inconnu') as pays_nom"),
            )
            ->first();
        if (! $film) {
            return response()->json(['success' => false, 'error' => 'Film introuvable'], 404);
        }
        $sg = DB::table('films_sous_genres as fsg')
            ->join('sous_genres as sg', 'fsg.sous_genre_id', '=', 'sg.id')
            ->where('fsg.film_id', $id)
            ->orderBy('sg.nom')
            ->get(['sg.id', 'sg.nom'])
            ->map(fn ($r) => ['id' => (int) $r->id, 'nom' => (string) $r->nom])
            ->values();
        $img = (string) ($film->image_path ?? '');

        return response()->json([
            'success' => true,
            'film' => [
                'id' => (int) $film->id,
                'nom_film' => (string) $film->nom_film,
                'categorie' => (string) $film->categorie,
                'description' => (string) ($film->description ?? ''),
                'ordre_suite' => $film->ordre_suite !== null ? (int) $film->ordre_suite : null,
                'saison' => $film->saison !== null ? (int) $film->saison : null,
                'nbrEpisode' => $film->nbrEpisode !== null ? (int) $film->nbrEpisode : null,
                'date_sortie' => (int) $film->date_sortie,
                'studio_id' => (int) $film->studio_id,
                'auteur_id' => (int) $film->auteur_id,
                'pays_id' => (int) $film->pays_id,
                'studio_nom' => (string) $film->studio_nom,
                'auteur_nom' => (string) $film->auteur_nom,
                'pays_nom' => (string) $film->pays_nom,
                'image' => $img !== '' ? asset($img) : '',
                'sous_genres' => $sg,
            ],
        ]);
    }

    public function sendNotification(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }
        if ($titre === 'Admin' && $this->hasRestriction($request, 'Admin Notif Off')) {
            return response()->json(['success' => false, 'message' => 'Action bloquée par restriction.'], 403);
        }

        $data = $request->validate([
            'recipient_type' => ['required', 'in:all,title,specific'],
            'notification_title' => ['required', 'string', 'max:100'],
            'notification_message' => ['required', 'string', 'max:500'],
            'user_title' => ['nullable', 'string'],
            'search_type' => ['nullable', 'in:username,email'],
            'user_search' => ['nullable', 'string'],
        ]);

        $userIds = [];
        if ($data['recipient_type'] === 'all') {
            $userIds = DB::table('membres')->pluck('id')->all();
        }
        if ($data['recipient_type'] === 'title') {
            if (! ($data['user_title'] ?? null)) {
                return response()->json(['success' => false, 'message' => "Veuillez sélectionner un titre d'utilisateur."], 422);
            }
            $userIds = DB::table('membres')->where('titre', $data['user_title'])->pluck('id')->all();
        }
        if ($data['recipient_type'] === 'specific') {
            if (! ($data['search_type'] ?? null) || ! ($data['user_search'] ?? null)) {
                return response()->json(['success' => false, 'message' => "Veuillez spécifier le type de recherche et l'utilisateur."], 422);
            }
            $q = DB::table('membres');
            if ($data['search_type'] === 'username') {
                $q->where('username', $data['user_search']);
            } else {
                $q->where('email', $data['user_search']);
            }
            $id = $q->value('id');
            if (! $id) {
                return response()->json(['success' => false, 'message' => 'Aucun utilisateur trouvé.'], 404);
            }
            $userIds = [(int) $id];
        }

        if (empty($userIds)) {
            return response()->json(['success' => false, 'message' => 'Aucun destinataire trouvé.'], 404);
        }

        $success = 0;
        $error = 0;
        foreach ($userIds as $uid) {
            try {
                DB::table('notifications')->insert([
                    'user_id' => (int) $uid,
                    'type' => 'admin_notification',
                    'message' => $data['notification_message'],
                    'titre' => $data['notification_title'],
                    'lu' => false,
                    'date_creation' => now(),
                ]);
                $success++;
            } catch (\Throwable $e) {
                $error++;
            }
        }

        $total = count($userIds);
        if ($success === $total) {
            return response()->json(['success' => true, 'message' => "Notification envoyée avec succès à {$success} utilisateur(s)."]);
        }
        if ($success > 0) {
            return response()->json(['success' => true, 'message' => "Notification envoyée à {$success} utilisateur(s) sur {$total}. {$error} échec(s).", 'partial' => true]);
        }

        return response()->json(['success' => false, 'message' => "Échec de l'envoi de toutes les notifications."], 500);
    }

    public function sendEmail(Request $request)
    {
        $titre = $request->session()->get('titre');
        if ($titre !== 'Super-Admin') {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        $data = $request->validate([
            'recipient_type' => ['required', 'in:all,title,specific'],
            'email_subject' => ['required', 'string', 'max:120'],
            'email_message' => ['required', 'string'],
            'user_title' => ['nullable', 'string'],
            'search_type' => ['nullable', 'in:username,email'],
            'user_search' => ['nullable', 'string'],
        ]);

        $fromAddress = (string) config('mail.from.address');
        if ($fromAddress === '') {
            return response()->json([
                'success' => false,
                'message' => 'MAIL_FROM_ADDRESS manquant. Configure le SMTP dans .env avant.',
            ], 422);
        }

        $userIds = [];
        if ($data['recipient_type'] === 'all') {
            $userIds = DB::table('membres')
                ->whereNotNull('email')
                ->where('email', '<>', '')
                ->pluck('id')
                ->all();
        }
        if ($data['recipient_type'] === 'title') {
            if (! ($data['user_title'] ?? null)) {
                return response()->json(['success' => false, 'message' => "Veuillez sélectionner un titre d'utilisateur."], 422);
            }
            $userIds = DB::table('membres')
                ->where('titre', $data['user_title'])
                ->whereNotNull('email')
                ->where('email', '<>', '')
                ->pluck('id')
                ->all();
        }
        if ($data['recipient_type'] === 'specific') {
            if (! ($data['search_type'] ?? null) || ! ($data['user_search'] ?? null)) {
                return response()->json(['success' => false, 'message' => "Veuillez spécifier le type de recherche et l'utilisateur."], 422);
            }
            $q = DB::table('membres')->whereNotNull('email')->where('email', '<>', '');
            if ($data['search_type'] === 'username') {
                $q->where('username', $data['user_search']);
            } else {
                $q->where('email', $data['user_search']);
            }
            $id = $q->value('id');
            if (! $id) {
                return response()->json(['success' => false, 'message' => 'Aucun utilisateur trouvé (ou pas d’email).'], 404);
            }
            $userIds = [(int) $id];
        }

        if (empty($userIds)) {
            return response()->json(['success' => false, 'message' => 'Aucun destinataire trouvé.'], 404);
        }

        $emails = DB::table('membres')
            ->whereIn('id', array_map('intval', $userIds))
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->pluck('email')
            ->map(fn ($e) => (string) $e)
            ->unique()
            ->values()
            ->all();

        if (empty($emails)) {
            return response()->json(['success' => false, 'message' => 'Aucun destinataire trouvé (emails vides).'], 404);
        }

        $subject = (string) $data['email_subject'];
        $bodyText = (string) $data['email_message'];
        $html = '<div style="font-family:Arial,sans-serif;line-height:1.5">'
            .nl2br(e($bodyText))
            .'</div>';

        $success = 0;
        $error = 0;
        foreach (array_chunk($emails, 50) as $chunk) {
            try {
                Mail::html($html, function ($message) use ($fromAddress, $subject, $chunk) {
                    $message->to($fromAddress)->bcc($chunk)->subject($subject);
                });
                $success += count($chunk);
            } catch (\Throwable) {
                $error += count($chunk);
            }
        }

        $total = count($emails);
        if ($success === $total) {
            return response()->json(['success' => true, 'message' => "Email envoyé avec succès à {$success} destinataire(s)."]);
        }
        if ($success > 0) {
            return response()->json(['success' => true, 'message' => "Email envoyé à {$success} destinataire(s) sur {$total}. {$error} échec(s).", 'partial' => true]);
        }

        return response()->json(['success' => false, 'message' => "Échec de l'envoi de tous les emails."], 500);
    }

    public function publishPrivacyPolicy(Request $request)
    {
        $titre = $request->session()->get('titre');
        if ($titre !== 'Super-Admin') {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $now = now();
        $nowStr = $now->toDateTimeString();

        $currentVersionRaw = DB::table('site_settings')->where('key', 'privacy_policy_version')->value('value');
        $currentVersion = is_numeric($currentVersionRaw) ? (int) $currentVersionRaw : 0;
        $nextVersion = $currentVersion + 1;

        $message = trim((string) ($data['message'] ?? ''));
        if ($message === '') {
            $message = 'La politique de confidentialité a été mise à jour.';
        }

        DB::table('site_settings')->upsert([
            ['key' => 'privacy_policy_version', 'value' => (string) $nextVersion, 'updated_at' => $now],
            ['key' => 'privacy_policy_message', 'value' => $message, 'updated_at' => $now],
            ['key' => 'privacy_policy_updated_at', 'value' => $nowStr, 'updated_at' => $now],
        ], ['key'], ['value', 'updated_at']);

        return response()->json([
            'success' => true,
            'version' => $nextVersion,
            'updated_at' => $nowStr,
        ]);
    }

    public function studioConversions(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['success' => false, 'error' => 'Accès non autorisé'], 403);
        }
        if ($titre === 'Admin' && $this->hasRestriction($request, 'Admin Conversions Off')) {
            return response()->json(['success' => false, 'error' => 'Action bloquée par restriction'], 403);
        }

        $action = (string) ($request->input('action') ?? $request->query('action') ?? '');
        $payload = $request->json()->all();
        if ($action === '' && isset($payload['action'])) {
            $action = (string) $payload['action'];
        }

        if ($action === 'list' || $action === 'get_conversions') {
            return response()->json(['success' => true, 'conversions' => $this->loadConversions()]);
        }
        if ($action === 'list_studios') {
            $rows = DB::table('studios')->orderBy('nom')->get(['id', 'nom']);

            return response()->json(['success' => true, 'studios' => $rows]);
        }
        if ($action === 'get_studio_conversions') {
            $studioId = (int) ($payload['studio_id'] ?? $request->input('studio_id', 0));
            $studio = DB::table('studios')->where('id', $studioId)->first(['id', 'nom']);
            if (! $studio) {
                return response()->json(['success' => false, 'error' => 'Studio introuvable'], 404);
            }
            $conversions = $this->loadConversions();
            $key = Str::of((string) $studio->nom)->slug('-')->toString();
            $entry = $conversions[$key] ?? ['patterns' => [], 'target' => (string) $studio->nom];

            return response()->json(['success' => true, 'key' => $key, 'conversion' => $entry]);
        }
        if ($action === 'add_conversion') {
            $key = (string) ($payload['key'] ?? $request->input('key', ''));
            $patterns = $payload['patterns'] ?? $request->input('patterns', []);
            $target = (string) ($payload['target'] ?? $request->input('target', ''));
            if ($key === '' || ! is_array($patterns) || empty($patterns) || $target === '') {
                return response()->json(['success' => false, 'error' => 'Paramètres manquants'], 422);
            }
            $conversions = $this->loadConversions();
            $conversions[$key] = [
                'patterns' => array_values(array_filter(array_map('strval', $patterns))),
                'target' => $target,
            ];
            $ok = $this->saveConversions($conversions);

            return response()->json(['success' => $ok]);
        }
        if ($action === 'add_pattern') {
            $studioId = (int) ($payload['studio_id'] ?? $request->input('studio_id', 0));
            $pattern = Str::lower(trim((string) ($payload['pattern'] ?? $request->input('pattern', ''))));
            if ($studioId <= 0 || $pattern === '') {
                return response()->json(['success' => false, 'error' => 'Paramètres manquants'], 422);
            }
            $studio = DB::table('studios')->where('id', $studioId)->first(['id', 'nom']);
            if (! $studio) {
                return response()->json(['success' => false, 'error' => 'Studio introuvable'], 404);
            }
            $key = Str::of((string) $studio->nom)->slug('-')->toString();
            $conversions = $this->loadConversions();
            $entry = $conversions[$key] ?? ['patterns' => [], 'target' => (string) $studio->nom];
            $patterns = array_map(fn ($p) => Str::lower(trim((string) $p)), (array) ($entry['patterns'] ?? []));
            if (! in_array($pattern, $patterns, true)) {
                $patterns[] = $pattern;
            }
            $entry['patterns'] = array_values(array_filter($patterns, fn ($p) => $p !== ''));
            $entry['target'] = (string) $studio->nom;
            $conversions[$key] = $entry;
            $ok = $this->saveConversions($conversions);

            return response()->json(['success' => $ok, 'conversion' => $entry]);
        }
        if ($action === 'remove_pattern') {
            $studioId = (int) ($payload['studio_id'] ?? $request->input('studio_id', 0));
            $pattern = Str::lower(trim((string) ($payload['pattern'] ?? $request->input('pattern', ''))));
            if ($studioId <= 0 || $pattern === '') {
                return response()->json(['success' => false, 'error' => 'Paramètres manquants'], 422);
            }
            $studio = DB::table('studios')->where('id', $studioId)->first(['id', 'nom']);
            if (! $studio) {
                return response()->json(['success' => false, 'error' => 'Studio introuvable'], 404);
            }
            $key = Str::of((string) $studio->nom)->slug('-')->toString();
            $conversions = $this->loadConversions();
            $entry = $conversions[$key] ?? ['patterns' => [], 'target' => (string) $studio->nom];
            $patterns = array_map(fn ($p) => Str::lower(trim((string) $p)), (array) ($entry['patterns'] ?? []));
            $patterns = array_values(array_filter($patterns, fn ($p) => $p !== $pattern));
            $entry['patterns'] = $patterns;
            $entry['target'] = (string) $studio->nom;
            $conversions[$key] = $entry;
            $ok = $this->saveConversions($conversions);

            return response()->json(['success' => $ok, 'conversion' => $entry]);
        }
        if ($action === 'remove_conversion') {
            $key = (string) ($payload['key'] ?? $request->input('key', ''));
            if ($key === '') {
                return response()->json(['success' => false, 'error' => 'Clé manquante'], 422);
            }
            $conversions = $this->loadConversions();
            unset($conversions[$key]);
            $ok = $this->saveConversions($conversions);

            return response()->json(['success' => $ok]);
        }
        if ($action === 'merge_studios') {
            $keepId = (int) ($payload['keep_id'] ?? $request->input('keep_id', 0));
            $replaceId = (int) ($payload['replace_id'] ?? $request->input('replace_id', 0));
            if ($keepId <= 0 || $replaceId <= 0 || $keepId === $replaceId) {
                return response()->json(['success' => false, 'error' => 'Paramètres invalides'], 422);
            }
            $keep = DB::table('studios')->where('id', $keepId)->first(['id', 'nom']);
            $replace = DB::table('studios')->where('id', $replaceId)->first(['id', 'nom']);
            if (! $keep || ! $replace) {
                return response()->json(['success' => false, 'error' => 'Studios introuvables'], 404);
            }

            DB::beginTransaction();
            try {
                DB::table('films')->where('studio_id', $replaceId)->update(['studio_id' => $keepId]);
                DB::table('films_temp')->where('studio_id', $replaceId)->update(['studio_id' => $keepId]);
                $conversions = $this->loadConversions();
                $keepKey = Str::of((string) $keep->nom)->slug('-')->toString();
                $replaceKey = Str::of((string) $replace->nom)->slug('-')->toString();
                $keepEntry = $conversions[$keepKey] ?? ['patterns' => [], 'target' => (string) $keep->nom];
                $replaceEntry = $conversions[$replaceKey] ?? ['patterns' => [], 'target' => (string) $replace->nom];
                $mergedPatterns = array_map(fn ($p) => Str::lower(trim((string) $p)), array_merge(
                    (array) ($keepEntry['patterns'] ?? []),
                    (array) ($replaceEntry['patterns'] ?? []),
                    [(string) $replace->nom]
                ));
                $mergedPatterns = array_values(array_unique(array_filter($mergedPatterns, fn ($p) => $p !== '')));
                $keepEntry['patterns'] = $mergedPatterns;
                $keepEntry['target'] = (string) $keep->nom;
                $conversions[$keepKey] = $keepEntry;
                unset($conversions[$replaceKey]);
                $ok = $this->saveConversions($conversions);
                DB::table('studios')->where('id', $replaceId)->delete();
                DB::commit();

                return response()->json(['success' => $ok, 'keep' => $keepEntry]);
            } catch (\Throwable $e) {
                DB::rollBack();

                return response()->json(['success' => false, 'error' => 'Erreur fusion'], 500);
            }
        }
        if ($action === 'convert_studio') {
            $name = (string) ($payload['studio_name'] ?? $request->input('studio_name', ''));
            if ($name === '') {
                return response()->json(['success' => false, 'error' => 'Nom de studio manquant'], 422);
            }
            $converted = $this->convertStudioName($name);

            return response()->json(['success' => true, 'original' => $name, 'converted' => $converted]);
        }

        return response()->json(['success' => false, 'error' => 'Action non reconnue'], 422);
    }

    public function database(Request $request)
    {
        $titre = (string) $request->session()->get('titre', '');
        if ($titre !== 'Super-Admin') {
            abort(403);
        }

        $remoteAddr = (string) $request->server('REMOTE_ADDR', '');

        $trustedProxyIps = array_values(array_filter(array_map(
            static fn (string $v) => trim($v),
            explode(',', (string) env('TRUSTED_PROXY_IPS', '127.0.0.1,::1'))
        ), static fn (string $v) => $v !== ''));

        $trustForwardedHeaders = in_array('*', $trustedProxyIps, true) || ($remoteAddr !== '' && in_array($remoteAddr, $trustedProxyIps, true));

        $ip = $remoteAddr;
        if ($trustForwardedHeaders) {
            $cfIp = trim((string) $request->header('CF-Connecting-IP', ''));
            if ($cfIp !== '') {
                $ip = $cfIp;
            } else {
                $xff = trim((string) $request->header('X-Forwarded-For', ''));
                if ($xff !== '') {
                    $first = trim((string) strtok($xff, ','));
                    if ($first !== '') {
                        $ip = $first;
                    }
                }
            }
        }

        $allowed = array_values(array_filter(array_map(
            static fn (string $v) => trim($v),
            explode(',', (string) env('DATABASE_LOCAL_IPS', '127.0.0.1,::1,176.146.130.89'))
        ), static fn (string $v) => $v !== ''));

        $isPrivate = false;
        if ($ip !== '') {
            $isValidIp = filter_var($ip, FILTER_VALIDATE_IP) !== false;
            if ($isValidIp) {
                $isPrivate = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
            }
        }

        $isLocal = ($ip !== '' && in_array($ip, $allowed, true)) || $isPrivate;

        return view('Database.index', [
            'title' => 'Base de données',
            'isLocal' => $isLocal,
            'ip' => $ip,
        ]);
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

    private function hasRestriction(Request $request, string $restriction): bool
    {
        return in_array($restriction, $this->currentRestrictions($request), true);
    }

    protected function notifyUser(int $userId, string $titre, string $message, string $type): void
    {
        DB::table('notifications')->insert([
            'user_id' => $userId,
            'titre' => $titre,
            'message' => $message,
            'type' => $type,
            'lu' => false,
            'date_creation' => now(),
        ]);
    }

    protected function applyRewardsForApprovedFilms(int $userId): void
    {
        $paliers = [
            10 => 1,
            20 => 1,
            35 => 1,
            50 => 1,
            75 => 1,
            100 => 1,
            150 => 1,
            200 => 1,
            300 => 1,
            400 => 1,
            500 => 3,
            1000 => 5,
        ];

        $user = DB::table('membres')
            ->where('id', $userId)
            ->lockForUpdate()
            ->first(['id', 'max_films_approuves_atteint']);

        if (! $user) {
            return;
        }

        $currentCount = (int) DB::table('films_temp')
            ->where('propose_par', $userId)
            ->where('statut', 'approuve')
            ->count();

        $maxAtteint = is_numeric($user->max_films_approuves_atteint) ? (int) $user->max_films_approuves_atteint : 0;

        foreach ($paliers as $palier => $recompenses) {
            if ($currentCount < $palier || $maxAtteint >= $palier) {
                continue;
            }

            $recompenses = (int) $recompenses;
            if ($recompenses > 0) {
                DB::table('membres')->where('id', $userId)->update([
                    'recompenses' => DB::raw('recompenses + '.$recompenses),
                ]);

                $this->notifyUser(
                    $userId,
                    'Nouvelle récompense !',
                    "🏆 Bravo ! $palier de vos films proposés ont été approuvés ! Vous recevez $recompenses récompense".($recompenses > 1 ? 's' : '').' !',
                    'reward'
                );
            }

            if (in_array($palier, [500, 1000], true)) {
                $this->notifyUser(
                    $userId,
                    $palier >= 1000 ? 'Exploit Légendaire !' : 'Accomplissement Spécial !',
                    $palier === 500
                        ? "🏅 ACCOMPLISSEMENT LÉGENDAIRE !\n\n500 films approuvés ! Vous êtes devenu une référence de la communauté !"
                        : "👑 MAÎTRE SUPRÊME DU CINÉMA !\n\n1000 films approuvés ! Votre contribution à la communauté est exceptionnelle !",
                    'special_achievement'
                );
            }

            $maxAtteint = $palier;
        }

        if ($maxAtteint > (is_numeric($user->max_films_approuves_atteint) ? (int) $user->max_films_approuves_atteint : 0)) {
            DB::table('membres')->where('id', $userId)->update([
                'max_films_approuves_atteint' => $maxAtteint,
                'date_derniere_verification' => now(),
            ]);
        }
    }

    protected function moveTempImageToPublicList(string $nom, int $annee, int $ordre, string $tempPath): ?string
    {
        if ($tempPath === '' || ! str_starts_with($tempPath, 'img-temp/')) {
            return null;
        }
        $source = public_path($tempPath);
        if (! is_file($source)) {
            return null;
        }
        $ext = pathinfo($source, PATHINFO_EXTENSION);
        $safe = Str::of($nom)->ascii()->replaceMatches('/[^a-zA-Z0-9]/', '_')->replaceMatches('/_+/', '_')->trim('_')->toString();
        $destDir = public_path('publiclisteimg');
        if (! is_dir($destDir)) {
            @mkdir($destDir, 0775, true);
        }
        $destRel = 'publiclisteimg/'.$annee.'-'.$safe.'_'.$ordre.'.'.$ext;
        $destAbs = public_path($destRel);
        @rename($source, $destAbs);

        return $destRel;
    }

    protected function deleteTempImage(string $tempPath): void
    {
        if ($tempPath === '' || ! str_starts_with($tempPath, 'img-temp/')) {
            return;
        }
        $abs = public_path($tempPath);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    protected function storeFilmImage($file, string $nom, int $annee, int $ordre): ?string
    {
        if (! $file) {
            return null;
        }
        $ext = Str::lower($file->getClientOriginalExtension());
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $ext = 'png';
        }
        $safe = Str::of($nom)->ascii()->replaceMatches('/[^a-zA-Z0-9]/', '_')->replaceMatches('/_+/', '_')->trim('_')->toString();
        $dir = public_path('publiclisteimg');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $rel = 'publiclisteimg/'.$annee.'-'.$safe.'_'.$ordre.'.'.$ext;
        $file->move($dir, basename($rel));

        return $rel;
    }

    protected function storeFilmImageFromUrl(string $url, string $nom, int $annee, int $ordre): ?string
    {
        $url = trim($url);
        if ($url === '' || ! str_starts_with($url, 'http')) {
            return null;
        }
        try {
            $res = Http::timeout(10)->get($url);
            if (! $res->ok()) {
                return null;
            }
            $body = $res->body();
            if ($body === '' || $body === null) {
                return null;
            }
            $ext = 'jpg';
            if (preg_match('/image\\/(jpeg|jpg|png|gif|webp)/i', (string) $res->header('Content-Type'))) {
                $ext = Str::lower(preg_replace('/^image\\//i', '', (string) $res->header('Content-Type')));
            } else {
                if (str_ends_with(Str::lower($url), '.png')) {
                    $ext = 'png';
                } elseif (str_ends_with(Str::lower($url), '.webp')) {
                    $ext = 'webp';
                }
            }
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                $ext = 'jpg';
            }
            $safe = Str::of($nom)->ascii()->replaceMatches('/[^a-zA-Z0-9]/', '_')->replaceMatches('/_+/', '_')->trim('_')->toString();
            $dir = public_path('publiclisteimg');
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $rel = 'publiclisteimg/'.$annee.'-'.$safe.'_'.$ordre.'.'.$ext;
            $abs = public_path($rel);
            @file_put_contents($abs, $body);

            return $rel;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function getOptions(string $table, bool $withInconnuFirst): array
    {
        $options = [];

        if ($withInconnuFirst) {
            $row = DB::table($table)->where('nom', 'Inconnu')->first(['id', 'nom']);
            if ($row) {
                $options[(int) $row->id] = (string) $row->nom;
            }
        }

        $q = DB::table($table)->orderBy('nom');
        if ($withInconnuFirst) {
            $q->where('nom', '!=', 'Inconnu');
        }
        $rows = $q->get(['id', 'nom']);
        foreach ($rows as $r) {
            $nom = (string) $r->nom;
            if (in_array(mb_strtolower(trim($nom)), ['animation familiale', 'animation familiales'], true)) {
                $nom = 'Familial';
            }
            $options[(int) $r->id] = $nom;
        }

        return $options;
    }

    protected function resolveStudioId(string $studioId, ?string $nouveauStudio, string $categorie): int
    {
        if ($studioId !== 'autre') {
            return (int) $studioId;
        }

        $nom = trim((string) $nouveauStudio);
        $nom = $this->convertStudioName($nom);
        if ($nom === '') {
            return 1;
        }

        $row = DB::table('studios')->whereRaw('LOWER(nom) = LOWER(?)', [$nom])->first(['id', 'categorie']);
        if ($row) {
            $existing = (string) ($row->categorie ?? '');
            if ($categorie !== '' && ! Str::of($existing)->contains($categorie)) {
                $new = trim($existing === '' ? $categorie : $existing.','.$categorie, ',');
                DB::table('studios')->where('id', (int) $row->id)->update(['categorie' => $new]);
            }

            return (int) $row->id;
        }

        return (int) DB::table('studios')->insertGetId(['nom' => $nom, 'categorie' => $categorie]);
    }

    protected function resolveAuteurId(string $auteurId, ?string $nouveauAuteur, string $categorie): int
    {
        if ($auteurId !== 'autre') {
            return (int) $auteurId;
        }

        $nom = trim((string) $nouveauAuteur);
        if ($nom === '') {
            return 1;
        }

        $row = DB::table('auteurs')->whereRaw('LOWER(nom) = LOWER(?)', [$nom])->first(['id', 'categorie']);
        if ($row) {
            $existing = (string) ($row->categorie ?? '');
            if ($categorie !== '' && ! Str::of($existing)->contains($categorie)) {
                $new = trim($existing === '' ? $categorie : $existing.','.$categorie, ',');
                DB::table('auteurs')->where('id', (int) $row->id)->update(['categorie' => $new]);
            }

            return (int) $row->id;
        }

        return (int) DB::table('auteurs')->insertGetId(['nom' => $nom, 'categorie' => $categorie]);
    }

    protected function conversionsPath(): string
    {
        return public_path('studio-conversions.json');
    }

    protected function loadConversions(): array
    {
        $path = $this->conversionsPath();
        if (! is_file($path)) {
            return [];
        }
        $json = json_decode(file_get_contents($path), true);
        if (! is_array($json)) {
            return [];
        }
        if (isset($json['conversions']) && is_array($json['conversions'])) {
            return $json['conversions'];
        }

        $out = [];
        foreach ($json as $from => $to) {
            if (! is_string($from)) {
                continue;
            }
            $key = Str::of($from)->slug('-')->toString();
            $out[$key] = [
                'patterns' => [(string) $from],
                'target' => is_string($to) ? $to : (string) $to,
            ];
        }

        return $out;
    }

    protected function saveConversions(array $conversions): bool
    {
        $path = $this->conversionsPath();
        $data = ['conversions' => $conversions];
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        return file_put_contents($path, $json) !== false;
    }

    protected function convertStudioName(string $nom): string
    {
        $nom = trim($nom);
        if ($nom === '') {
            return $nom;
        }

        $conversions = $this->loadConversions();
        $normalized = Str::lower($nom);
        foreach ($conversions as $conversion) {
            $patterns = $conversion['patterns'] ?? [];
            $target = $conversion['target'] ?? null;
            if (! is_array($patterns) || ! is_string($target)) {
                continue;
            }
            foreach ($patterns as $p) {
                if (Str::lower(trim((string) $p)) === $normalized) {
                    return $target;
                }
            }
        }

        return $nom;
    }
}
