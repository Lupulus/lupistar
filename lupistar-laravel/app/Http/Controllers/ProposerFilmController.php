<?php

namespace App\Http\Controllers;

use App\Models\Auteur;
use App\Models\FilmTemp;
use App\Models\Pays;
use App\Models\SousGenre;
use App\Models\Studio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProposerFilmController extends Controller
{
    public function show(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return redirect()->route('login.show');
        }

        $titresHierarchie = [
            'Membre' => 1,
            'Amateur' => 2,
            'Fan' => 3,
            'NoLife' => 4,
            'Admin' => 5,
            'Super-Admin' => 6,
        ];
        $userTitle = (string) $request->session()->get('titre', '');
        $userLevel = $titresHierarchie[$userTitle] ?? 0;
        if ($userLevel <= 1) {
            return redirect()->route('accueil');
        }

        $sousGenres = SousGenre::query()->orderBy('nom')->pluck('nom', 'id')->toArray();
        $studios = Studio::query()->orderBy('nom')->pluck('nom', 'id')->toArray();
        $auteurs = Auteur::query()->orderBy('nom')->pluck('nom', 'id')->toArray();
        $pays = Pays::query()->orderBy('nom')->pluck('nom', 'id')->toArray();

        return view('ProposerFilm.index', [
            'title' => 'Proposer un film',
            'sousGenres' => $sousGenres,
            'studios' => $studios,
            'auteurs' => $auteurs,
            'pays' => $pays,
        ]);
    }

    public function studios(Request $request)
    {
        $categorie = trim((string) $request->query('categorie', ''));
        if ($categorie === '') {
            $rows = Studio::query()->orderBy('nom')->get(['id', 'nom']);

            return response()->json(['success' => true, 'studios' => $rows]);
        }

        $rows = DB::table('studios as s')
            ->leftJoin('films as f', function ($join) use ($categorie) {
                $join->on('s.id', '=', 'f.studio_id')->where('f.categorie', '=', $categorie);
            })
            ->where('s.categorie', 'like', '%'.$categorie.'%')
            ->groupBy('s.id', 's.nom')
            ->orderByDesc(DB::raw('COUNT(f.id)'))
            ->orderBy('s.nom')
            ->get(['s.id', 's.nom']);

        return response()->json(['success' => true, 'studios' => $rows]);
    }

    public function auteurs(Request $request)
    {
        $categorie = trim((string) $request->query('categorie', ''));
        if ($categorie === '') {
            $rows = Auteur::query()->orderBy('nom')->get(['id', 'nom']);

            return response()->json(['success' => true, 'auteurs' => $rows]);
        }

        $rows = DB::table('auteurs as a')
            ->leftJoin('films as f', function ($join) use ($categorie) {
                $join->on('a.id', '=', 'f.auteur_id')->where('f.categorie', '=', $categorie);
            })
            ->where('a.categorie', 'like', '%'.$categorie.'%')
            ->groupBy('a.id', 'a.nom')
            ->orderByDesc(DB::raw('COUNT(f.id)'))
            ->orderBy('a.nom')
            ->get(['a.id', 'a.nom']);

        return response()->json(['success' => true, 'auteurs' => $rows]);
    }

    public function store(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return redirect()->route('login.show');
        }

        $titresHierarchie = [
            'Membre' => 1,
            'Amateur' => 2,
            'Fan' => 3,
            'NoLife' => 4,
            'Admin' => 5,
            'Super-Admin' => 6,
        ];
        $userTitle = (string) $request->session()->get('titre', '');
        $userLevel = $titresHierarchie[$userTitle] ?? 0;
        if ($userLevel <= 1) {
            return redirect()->route('accueil');
        }

        $data = $request->validate([
            'nom_film' => ['required', 'string', 'max:50'],
            'categorie' => ['required', 'in:Film,Animation,Anime,Série,Série d\'Animation'],
            'anime_type' => ['nullable', 'in:Film,Série'],
            'date_sortie' => ['required', 'integer', 'between:1900,2099'],
            'description' => ['nullable', 'string', 'max:400'],
            'ordre_suite' => ['nullable', 'integer', 'min:1', 'max:25'],
            'saison' => ['nullable', 'integer', 'min:1', 'max:100'],
            'nbrEpisode' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'studio_id' => ['nullable', 'string'],
            'nouveau_studio' => ['nullable', 'string', 'max:30'],
            'auteur_id' => ['nullable', 'string'],
            'nouveau_auteur' => ['nullable', 'string', 'max:30'],
            'pays_id' => ['required', 'integer'],
            'sous_genres' => ['required', 'array', 'min:1'],
            'sous_genres.*' => ['integer'],
            'image' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $nomFilm = $data['nom_film'];
        $categorie = $data['categorie'];
        $description = $data['description'] ?? null;
        $dateSortie = (int) $data['date_sortie'];
        $animeType = (string) ($data['anime_type'] ?? '');

        $isSerie = in_array($categorie, ['Série', "Série d'Animation"], true) || ($categorie === 'Anime' && $animeType === 'Série');
        if ($isSerie) {
            $saison = $data['saison'] ?? 1;
            $nbrEpisode = $data['nbrEpisode'] ?? null;
            if ($nbrEpisode === null) {
                return back()->withErrors(['nbrEpisode' => "Le nombre d'épisodes est requis pour une série."])->withInput();
            }
            $ordreSuite = null;
        } else {
            $saison = null;
            $nbrEpisode = null;
            $ordreSuite = $data['ordre_suite'] ?? 1;
        }

        $existsFilms = DB::table('films')->whereRaw('LOWER(nom_film) = ?', [Str::lower($nomFilm)])->exists();
        if ($existsFilms) {
            return back()->withErrors(['nom_film' => 'Ce film existe déjà dans la base de données.'])->withInput();
        }
        $existsTemp = DB::table('films_temp')->whereRaw('LOWER(nom_film) = ?', [Str::lower($nomFilm)])->where('statut', 'en_attente')->exists();
        if ($existsTemp) {
            return back()->withErrors(['nom_film' => 'Ce film a déjà été proposé et est en attente.'])->withInput();
        }

        $studioSelectValue = $request->has('studio_id') ? $request->input('studio_id') : $request->input('studio_select');
        $auteurSelectValue = $request->has('auteur_id') ? $request->input('auteur_id') : $request->input('auteur_select');
        if (! is_string($studioSelectValue) || trim($studioSelectValue) === '') {
            return back()->withErrors(['studio_id' => 'Veuillez sélectionner un studio.'])->withInput();
        }
        if (! is_string($auteurSelectValue) || trim($auteurSelectValue) === '') {
            return back()->withErrors(['auteur_id' => 'Veuillez sélectionner un auteur.'])->withInput();
        }
        $paysId = $request->has('pays_id') ? (int) $request->input('pays_id') : (int) $this->getDefaultOrInsert('pays', $request->string('pays_select')->toString(), $request->string('nouveau_pays')->toString());

        $studioId = $this->getDefaultOrInsert('studios', is_string($studioSelectValue) ? $studioSelectValue : null, $request->string('nouveau_studio')->toString(), $categorie);
        $auteurId = $this->getDefaultOrInsert('auteurs', is_string($auteurSelectValue) ? $auteurSelectValue : null, $request->string('nouveau_auteur')->toString(), $categorie);

        $imagePath = null;
        $uploaded = $request->file('image');
        $ext = Str::lower($uploaded->getClientOriginalExtension());
        $safeName = $this->sanitizeFileBase($nomFilm);
        $num = $isSerie && $saison ? $saison : ($ordreSuite ?? 1);
        $timestamp = time();
        $relative = "img-temp/{$dateSortie}-{$safeName}_{$num}_user{$userId}_{$timestamp}.{$ext}";
        $uploaded->move(public_path('img-temp'), basename($relative));
        $imagePath = $relative;

        $filmTemp = new FilmTemp;
        $filmTemp->nom_film = $nomFilm;
        $filmTemp->categorie = $categorie;
        $filmTemp->description = $description;
        $filmTemp->image_path = $imagePath;
        $filmTemp->ordre_suite = $ordreSuite;
        $filmTemp->saison = $saison;
        $filmTemp->nbrEpisode = $nbrEpisode;
        $filmTemp->date_sortie = $dateSortie;
        $filmTemp->studio_id = $studioId;
        $filmTemp->auteur_id = $auteurId;
        $filmTemp->pays_id = $paysId;
        $filmTemp->propose_par = $userId;
        $filmTemp->statut = 'en_attente';
        $filmTemp->save();

        $ids = array_map('intval', $data['sous_genres']);
        $rows = array_map(fn ($id) => ['film_temp_id' => $filmTemp->id, 'sous_genre_id' => $id], $ids);
        DB::table('films_temp_sous_genres')->insert($rows);

        return redirect()->route('proposer-film.show')->with('status', 'Proposition envoyée. Merci !');
    }

    protected function getDefaultOrInsert(string $table, ?string $selectValue, ?string $inputValue, ?string $categorie = null): ?int
    {
        $selectValue = trim((string) $selectValue);
        $inputValue = trim((string) $inputValue);
        if ($selectValue === '' || $selectValue === 'Sélectionnez un ...') {
            return $this->getOrInsertId($table, 'Inconnu');
        }
        if ($selectValue === 'autre') {
            if ($inputValue === '') {
                return $this->getOrInsertId($table, 'Inconnu');
            }

            return $this->getOrInsertId($table, $inputValue, $categorie);
        }
        if (is_numeric($selectValue)) {
            return (int) $selectValue;
        }

        return $this->getOrInsertId($table, $selectValue, $categorie);
    }

    protected function getOrInsertId(string $table, string $nom, ?string $categorie = null): int
    {
        $nom = trim($nom);
        if ($table === 'studios') {
            $nom = $this->convertStudio($nom);
            $row = DB::table('studios')->where('nom', $nom)->first();
            if ($row) {
                $id = (int) $row->id;
                $existing = (string) ($row->categorie ?? '');
                if ($categorie && ! Str::of($existing)->contains($categorie)) {
                    $new = trim($existing === '' ? $categorie : $existing.','.$categorie, ',');
                    DB::table('studios')->where('id', $id)->update(['categorie' => $new]);
                }

                return $id;
            }

            return (int) DB::table('studios')->insertGetId(['nom' => $nom, 'categorie' => $categorie ?? '']);
        }
        if ($table === 'auteurs') {
            $row = DB::table('auteurs')->where('nom', $nom)->first();
            if ($row) {
                $id = (int) $row->id;
                $existing = (string) ($row->categorie ?? '');
                if ($categorie && ! Str::of($existing)->contains($categorie)) {
                    $new = trim($existing === '' ? $categorie : $existing.','.$categorie, ',');
                    DB::table('auteurs')->where('id', $id)->update(['categorie' => $new]);
                }

                return $id;
            }

            return (int) DB::table('auteurs')->insertGetId(['nom' => $nom, 'categorie' => $categorie ?? '']);
        }
        if ($table === 'pays') {
            $row = DB::table('pays')->where('nom', $nom)->first();
            if ($row) {
                return (int) $row->id;
            }

            return (int) DB::table('pays')->insertGetId(['nom' => $nom]);
        }

        return 0;
    }

    protected function convertStudio(string $nom): string
    {
        $path = public_path('studio-conversions.json');
        if (! is_file($path)) {
            return $nom;
        }
        $json = json_decode(file_get_contents($path), true);
        if (! is_array($json)) {
            return $nom;
        }
        if (isset($json['conversions']) && is_array($json['conversions'])) {
            $normalized = Str::lower(trim($nom));
            foreach ($json['conversions'] as $conversion) {
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
        foreach ($json as $from => $to) {
            if (Str::lower(trim((string) $from)) === Str::lower(trim($nom))) {
                return is_string($to) ? $to : (string) $to;
            }
        }

        return $nom;
    }

    protected function sanitizeFileBase(string $nom): string
    {
        $s = iconv('UTF-8', 'ASCII//TRANSLIT', $nom);
        $s = preg_replace('/[^a-zA-Z0-9]/', '_', (string) $s);
        $s = preg_replace('/_+/', '_', (string) $s);

        return trim((string) $s, '_');
    }
}
