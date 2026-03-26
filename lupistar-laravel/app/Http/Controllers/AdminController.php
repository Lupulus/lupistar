<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            abort(403);
        }

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
            ->select('ft.*', 's.nom as studio_nom', 'a.nom as auteur_nom', 'p.nom as pays_nom')
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

            return [
                'id' => (int) $r->id,
                'nom_film' => (string) $r->nom_film,
                'categorie' => (string) $r->categorie,
                'description' => (string) ($r->description ?? ''),
                'date_sortie' => (int) $r->date_sortie,
                'ordre_suite' => (int) ($r->ordre_suite ?? 0),
                'saison' => $r->saison !== null ? (int) $r->saison : null,
                'nbrEpisode' => $r->nbrEpisode !== null ? (int) $r->nbrEpisode : null,
                'studio_nom' => $r->studio_nom !== null ? (string) $r->studio_nom : 'Inconnu',
                'auteur_nom' => $r->auteur_nom !== null ? (string) $r->auteur_nom : 'Inconnu',
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
            'nom_film' => ['required', 'string', 'max:50'],
            'categorie' => ['required', 'in:Film,Animation,Anime,Série,Série d\'Animation'],
            'anime_type' => ['nullable', 'in:Film,Série'],
            'description' => ['nullable', 'string', 'max:400'],
            'date_sortie' => ['required', 'integer', 'min:1900', 'max:2099'],
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
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

        $studioId = $this->resolveStudioId((string) $data['studio_id'], $data['nouveau_studio'] ?? null, $data['categorie']);
        $auteurId = $this->resolveAuteurId((string) $data['auteur_id'], $data['nouveau_auteur'] ?? null, $data['categorie']);
        $paysId = (int) $data['pays_id'];

        $imagePath = $this->storeFilmImage($request->file('image'), $data['nom_film'], (int) $data['date_sortie'], (int) ($data['ordre_suite'] ?? 1));
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

    public function approve(Request $request, int $id)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            abort(403);
        }

        $data = $request->validate([
            'nom_film' => ['required', 'string', 'max:50'],
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

        $exists = DB::table('films')->whereRaw('LOWER(nom_film) = LOWER(?)', [$data['nom_film']])->exists();
        if ($exists) {
            return $request->expectsJson() ? response()->json(['success' => false, 'error' => 'Ce film existe déjà dans la base de données.'], 422) : back();
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
            $filmId = DB::table('films')->insertGetId([
                'nom_film' => $data['nom_film'],
                'categorie' => $data['categorie'],
                'description' => $data['description'] ?? $ft->description,
                'image_path' => $imageFinal ?: ($ft->image_path ?: ''),
                'date_sortie' => (int) $data['date_sortie'],
                'ordre_suite' => $data['ordre_suite'] ?? $ft->ordre_suite,
                'saison' => $data['saison'] ?? $ft->saison,
                'nbrEpisode' => $data['nbrEpisode'] ?? $ft->nbrEpisode,
                'note_moyenne' => 0,
                'studio_id' => $ft->studio_id,
                'auteur_id' => $ft->auteur_id,
                'pays_id' => $ft->pays_id,
            ]);

            $sg = DB::table('films_temp_sous_genres')->where('film_temp_id', $id)->pluck('sous_genre_id')->all();
            if (! empty($sg)) {
                $rows = array_map(fn ($sid) => ['film_id' => $filmId, 'sous_genre_id' => $sid], $sg);
                DB::table('films_sous_genres')->insert($rows);
            }

            DB::table('films_temp_sous_genres')->where('film_temp_id', $id)->delete();
            DB::table('films_temp')->where('id', $id)->delete();

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

        $q = DB::table('auteurs')
            ->where('nom', 'like', '%'.$search.'%')
            ->orderBy('nom')
            ->limit(10);

        return response()->json($q->pluck('nom')->all());
    }

    public function deleteFilm(Request $request, int $id)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
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

        $data = $request->validate([
            'nom_film' => ['required', 'string', 'max:50'],
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

        DB::beginTransaction();
        try {
            DB::table('films')->where('id', $id)->update([
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
            ]);

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

    public function sendNotification(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
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

    public function studioConversions(Request $request)
    {
        $titre = $request->session()->get('titre');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['success' => false, 'error' => 'Accès non autorisé'], 403);
        }

        $action = (string) ($request->input('action') ?? $request->query('action') ?? '');
        $payload = $request->json()->all();
        if ($action === '' && isset($payload['action'])) {
            $action = (string) $payload['action'];
        }

        if ($action === 'list' || $action === 'get_conversions') {
            return response()->json(['success' => true, 'conversions' => $this->loadConversions()]);
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
            $options[(int) $r->id] = (string) $r->nom;
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

        $row = DB::table('studios')->where('nom', $nom)->first(['id', 'categorie']);
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

        $row = DB::table('auteurs')->where('nom', $nom)->first(['id', 'categorie']);
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
