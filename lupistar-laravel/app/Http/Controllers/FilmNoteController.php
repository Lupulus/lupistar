<?php

namespace App\Http\Controllers;

use App\Models\Film;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilmNoteController extends Controller
{
    public function update(Request $request, Film $film)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        if (! $userId) {
            return response()->json(['success' => false, 'error' => 'Utilisateur non connecté.'], 403);
        }

        $note = $request->input('note');
        if (! is_numeric($note)) {
            return response()->json(['success' => false, 'error' => 'Note invalide.'], 400);
        }

        $note = (float) $note;
        if ($note < 0 || $note > 10) {
            return response()->json(['success' => false, 'error' => 'Note invalide.'], 400);
        }

        DB::table('membres_films_list')->updateOrInsert(
            ['membres_id' => $userId, 'films_id' => $film->id],
            ['note' => $note]
        );

        $avg = DB::table('membres_films_list')->where('films_id', $film->id)->avg('note');
        $avg = $avg !== null ? round((float) $avg, 2) : null;

        $film->note_moyenne = $avg;
        $film->save();

        return response()->json([
            'success' => true,
            'nouvelle_note_moyenne' => $avg,
        ]);
    }
}
