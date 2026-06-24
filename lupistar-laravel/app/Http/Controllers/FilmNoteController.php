<?php

namespace App\Http\Controllers;

use App\Models\Film;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur responsable de la NOTATION d'un film par un membre.
 *
 * Couche "Contrôleur" de l'architecture MVC : il reçoit la requête HTTP,
 * vérifie les droits, valide les données, délègue l'écriture en base,
 * puis renvoie une réponse JSON (appel AJAX, sans rechargement de page).
 */
class FilmNoteController extends Controller
{
    /**
     * Enregistre (ou met à jour) la note d'un membre pour un film donné.
     *
     * Route : POST /films/{film}/note
     * $film est automatiquement récupéré par Laravel à partir de l'URL
     * (injection de modèle : "route model binding").
     */
    public function update(Request $request, Film $film)
    {
        // 1) SÉCURITÉ — on récupère l'identifiant du membre stocké en session
        //    lors de la connexion, et on s'assure qu'il s'agit bien d'un entier.
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        // Si personne n'est connecté, on refuse l'action (HTTP 403 = interdit).
        if (! $userId) {
            return response()->json(['success' => false, 'error' => 'Utilisateur non connecté.'], 403);
        }

        // 2) VALIDATION — la note doit être numérique...
        $note = $request->input('note');
        if (! is_numeric($note)) {
            return response()->json(['success' => false, 'error' => 'Note invalide.'], 400);
        }

        // ...et comprise entre 0 et 10 (sinon HTTP 400 = requête invalide).
        $note = (float) $note;
        if ($note < 0 || $note > 10) {
            return response()->json(['success' => false, 'error' => 'Note invalide.'], 400);
        }

        // 3) ENREGISTREMENT — updateOrInsert met à jour la ligne si le couple
        //    (membre, film) existe déjà, sinon il la crée. Couplé à la clé
        //    primaire composite de la table, cela garantit UNE seule note par
        //    membre et par film (pas de doublon).
        DB::table('membres_films_list')->updateOrInsert(
            ['membres_id' => $userId, 'films_id' => $film->id],
            ['note' => $note]
        );

        // 4) RECALCUL — on recalcule la note moyenne du film à partir de
        //    toutes les notes des membres (fonction d'agrégation SQL AVG),
        //    arrondie à 2 décimales.
        $avg = DB::table('membres_films_list')->where('films_id', $film->id)->avg('note');
        $avg = $avg !== null ? round((float) $avg, 2) : null;

        // On mémorise cette moyenne sur le film pour un affichage rapide.
        $film->note_moyenne = $avg;
        $film->save();

        // 5) GAMIFICATION — on attribue d'éventuelles récompenses au membre.
        $this->applyRewardsForPersonalList($userId);

        // 6) RÉPONSE — on renvoie le résultat en JSON ; le JavaScript met
        //    alors l'affichage à jour sans recharger la page.
        return response()->json([
            'success' => true,
            'nouvelle_note_moyenne' => $avg,
        ]);
    }

    /**
     * Crée une notification en base pour un membre (message affiché dans
     * son espace). Méthode privée : usage interne au contrôleur uniquement.
     */
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

    /**
     * Attribue des récompenses au membre selon le nombre de films notés
     * (paliers à 10, 100, 250, 500 films, puis paliers supplémentaires).
     *
     * Tout est exécuté dans une TRANSACTION : l'ensemble des écritures
     * réussit ou est annulé en bloc (cohérence des données). Le verrou
     * lockForUpdate évite les incohérences en cas d'accès simultanés.
     */
    private function applyRewardsForPersonalList(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            // Paliers de récompense : nombre de films notés => récompenses gagnées.
            $paliers = [
                10 => 1,
                100 => 1,
                250 => 1,
                500 => 1,
            ];

            // On verrouille la ligne du membre le temps de la mise à jour.
            $user = DB::table('membres')
                ->where('id', $userId)
                ->lockForUpdate()
                ->first(['id', 'max_films_liste_atteint']);

            if (! $user) {
                return;
            }

            // Nombre de films réellement notés (note > 0) par ce membre.
            $currentCount = (int) DB::table('membres_films_list')
                ->where('membres_id', $userId)
                ->where('note', '>', 0)
                ->count();

            // Plus haut palier déjà atteint (pour ne pas récompenser deux fois).
            $maxAtteint = is_numeric($user->max_films_liste_atteint) ? (int) $user->max_films_liste_atteint : 0;

            // Parcours des paliers : on récompense ceux qui sont franchis pour
            // la première fois.
            foreach ($paliers as $palier => $recompenses) {
                if ($currentCount < $palier || $maxAtteint >= $palier) {
                    continue; // palier non atteint, ou déjà récompensé
                }

                $recompenses = (int) $recompenses;
                if ($recompenses > 0) {
                    // Incrément atomique du compteur de récompenses en base.
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

            // Au-delà de 500 films : un palier bonus tous les 100 films.
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

            // Au-delà de 1000 films : une notification spéciale par millier.
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

            // On mémorise le nouveau plus haut palier atteint (et la date),
            // pour éviter de redonner les mêmes récompenses plus tard.
            if ($maxAtteint > (is_numeric($user->max_films_liste_atteint) ? (int) $user->max_films_liste_atteint : 0)) {
                DB::table('membres')->where('id', $userId)->update([
                    'max_films_liste_atteint' => $maxAtteint,
                    'date_derniere_verification' => now(),
                ]);
            }
        });
    }
}
