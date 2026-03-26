<?php

namespace App\Http\Controllers;

use App\Models\Film;
use App\Services\AccueilService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilmModalController extends Controller
{
    public function __construct(private readonly AccueilService $accueilService) {}

    public function show(Request $request, Film $film)
    {
        $film->load(['studio', 'pays', 'auteur', 'sousGenres']);

        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        $filmDansListe = false;
        $userNote = null;

        if ($userId) {
            $row = DB::table('membres_films_list')
                ->select(['note'])
                ->where('films_id', $film->id)
                ->where('membres_id', $userId)
                ->first();

            if ($row) {
                $filmDansListe = true;
                $userNote = $row->note;
            }
        }

        $imagePath = $this->accueilService->toPublicAssetPath($film->image_path);

        return view('components.modals.film-details', [
            'film' => $film,
            'imagePath' => $imagePath,
            'filmDansListe' => $filmDansListe,
            'userNote' => $userNote,
            'isLoggedIn' => (bool) $userId,
        ]);
    }
}
