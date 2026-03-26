<?php

namespace App\Http\Controllers;

use App\Models\Film;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilmNotesController extends Controller
{
    public function show(Request $request, Film $film)
    {
        $intervals = [
            '0-1',
            '1-2',
            '2-3',
            '3-4',
            '4-5',
            '5-6',
            '6-7',
            '7-8',
            '8-9',
            '9-10',
        ];

        $votesParIntervalle = array_fill_keys($intervals, 0);

        $notes = DB::table('membres_films_list')
            ->where('films_id', $film->id)
            ->pluck('note');

        foreach ($notes as $noteValue) {
            $note = (float) $noteValue;

            foreach ($intervals as $interval) {
                [$min, $max] = array_map('floatval', explode('-', $interval));

                if (($note >= $min && $note < $max) || ($note === 10.0 && $max === 10.0)) {
                    $votesParIntervalle[$interval]++;
                    break;
                }
            }
        }

        return response()->json([
            'success' => true,
            'votes_par_intervalle' => $votesParIntervalle,
        ]);
    }
}
