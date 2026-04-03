<?php

namespace App\Http\Controllers;

use App\Models\Film;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilmPersonalListController extends Controller
{
    public function update(Request $request, Film $film)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être connecté pour ajouter un film à votre liste.',
            ], 403);
        }

        $action = $request->input('action');
        if (! in_array($action, ['add', 'remove'], true)) {
            return response()->json(['success' => false, 'message' => 'Action invalide.'], 400);
        }

        if ($action === 'add') {
            $exists = DB::table('membres_films_list')
                ->where('membres_id', $userId)
                ->where('films_id', $film->id)
                ->exists();

            if (! $exists) {
                DB::table('membres_films_list')->insert([
                    'membres_id' => $userId,
                    'films_id' => $film->id,
                    'note' => null,
                ]);
            }
        }

        if ($action === 'remove') {
            DB::table('membres_films_list')
                ->where('membres_id', $userId)
                ->where('films_id', $film->id)
                ->delete();
        }

        $avg = DB::table('membres_films_list')->where('films_id', $film->id)->avg('note');
        $avg = $avg !== null ? round((float) $avg, 2) : null;

        $film->note_moyenne = $avg;
        $film->save();

        return response()->json([
            'success' => true,
            'message' => $action === 'add' ? 'Film ajouté à votre liste !' : 'Film supprimé de votre liste !',
            'nouvelle_note_moyenne' => $avg,
        ]);
    }
}
