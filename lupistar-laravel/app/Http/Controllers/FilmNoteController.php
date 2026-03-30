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

        $this->applyRewardsForPersonalList($userId);

        return response()->json([
            'success' => true,
            'nouvelle_note_moyenne' => $avg,
        ]);
    }

    private function notifyUser(int $userId, string $titre, string $message, string $type): void
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

    private function applyRewardsForPersonalList(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $paliers = [
                10 => 1,
                100 => 1,
                250 => 1,
                500 => 1,
            ];

            $user = DB::table('membres')
                ->where('id', $userId)
                ->lockForUpdate()
                ->first(['id', 'max_films_liste_atteint']);

            if (! $user) {
                return;
            }

            $currentCount = (int) DB::table('membres_films_list')
                ->where('membres_id', $userId)
                ->where('note', '>', 0)
                ->count();

            $maxAtteint = is_numeric($user->max_films_liste_atteint) ? (int) $user->max_films_liste_atteint : 0;

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
                        "🎉 Félicitations ! Vous avez atteint $palier films notés dans votre liste personnelle ! Vous recevez $recompenses récompense".($recompenses > 1 ? 's' : '').' !',
                        'reward'
                    );
                }

                $maxAtteint = $palier;
            }

            if ($currentCount >= 500) {
                $paliersSupp = (int) floor(($currentCount - 500) / 100);
                $maxPaliersSupp = $maxAtteint >= 500 ? (int) floor(($maxAtteint - 500) / 100) : -1;

                for ($i = $maxPaliersSupp + 1; $i <= $paliersSupp; $i++) {
                    $palierActuel = 500 + ($i * 100);
                    if ($palierActuel <= 500) {
                        continue;
                    }

                    DB::table('membres')->where('id', $userId)->update([
                        'recompenses' => DB::raw('recompenses + 1'),
                    ]);

                    $this->notifyUser(
                        $userId,
                        'Nouvelle récompense !',
                        "🌟 Incroyable ! $palierActuel films notés dans votre liste ! Vous recevez 1 récompense bonus !",
                        'reward'
                    );

                    $maxAtteint = $palierActuel;
                }
            }

            if ($currentCount >= 1000) {
                $milliersActuels = (int) floor($currentCount / 1000);
                $milliersMax = (int) floor($maxAtteint / 1000);
                if ($milliersActuels > $milliersMax) {
                    for ($i = $milliersMax + 1; $i <= $milliersActuels; $i++) {
                        $palierActuel = $i * 1000;
                        $this->notifyUser(
                            $userId,
                            'Accomplissement Spécial !',
                            "🎊 EXPLOIT EXTRAORDINAIRE ! 🎊\n\nVous avez atteint $palierActuel films notés dans votre liste personnelle ! Vous êtes un véritable passionné de cinéma !",
                            'special_achievement'
                        );
                    }
                }
            }

            if ($maxAtteint > (is_numeric($user->max_films_liste_atteint) ? (int) $user->max_films_liste_atteint : 0)) {
                DB::table('membres')->where('id', $userId)->update([
                    'max_films_liste_atteint' => $maxAtteint,
                    'date_derniere_verification' => now(),
                ]);
            }
        });
    }
}
