<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MembresController extends Controller
{
    public function index(Request $request)
    {
        $titre = (string) $request->session()->get('titre', '');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            abort(403);
        }

        $membres = Membre::query()
            ->select(['id', 'username', 'email', 'titre', 'restriction', 'avertissements', 'recompenses', 'photo_profil', 'demande_promotion'])
            ->orderBy('id')
            ->get();

        return view('Membres.index', [
            'title' => 'Membres',
            'membres' => $membres,
        ]);
    }

    public function updateTitle(Request $request)
    {
        [$isAdmin, $isSuperAdmin] = $this->getAdminFlags($request);

        $id = is_numeric($request->input('id')) ? (int) $request->input('id') : 0;
        $newTitle = (string) $request->input('newTitle', '');
        $validTitles = ['Membre', 'Amateur', 'Fan', 'NoLife', 'Admin', 'Super-Admin'];

        if ($id <= 0 || ! in_array($newTitle, $validTitles, true)) {
            return response()->json(['success' => false, 'message' => 'Titre invalide'], 422);
        }

        $user = Membre::query()->whereKey($id)->first(['id', 'titre']);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
        }

        $currentTitle = (string) $user->titre;

        if ($isAdmin) {
            if (in_array($currentTitle, ['Super-Admin', 'Admin'], true)) {
                return response()->json(['success' => false, 'message' => 'Vous n\'avez pas les permissions pour modifier ce titre'], 403);
            }
            if (in_array($newTitle, ['Super-Admin', 'Admin'], true)) {
                return response()->json(['success' => false, 'message' => 'Vous n\'avez pas les permissions pour attribuer ce titre'], 403);
            }
        }

        if ($isSuperAdmin && $currentTitle === 'Super-Admin') {
            return response()->json(['success' => false, 'message' => 'Vous ne pouvez pas modifier le titre d\'un Super-Admin'], 403);
        }

        DB::table('membres')->where('id', $id)->update(['titre' => $newTitle]);

        $this->notifyUser(
            $id,
            'Titre modifié',
            'Votre titre a été modifié par un administrateur : '.$newTitle,
            'title_change_admin'
        );

        return response()->json(['success' => true, 'newValue' => $newTitle, 'message' => 'Titre mis à jour avec succès']);
    }

    public function updateRestriction(Request $request)
    {
        [$isAdmin] = $this->getAdminFlags($request);

        $id = is_numeric($request->input('id')) ? (int) $request->input('id') : 0;
        $newRestriction = (string) $request->input('newRestriction', '');
        $validRestrictions = ['Aucune', 'Salon Général', 'Salon Anime', 'Salon Films', 'Salon Séries', 'Modération Complète'];

        if ($id <= 0 || ! in_array($newRestriction, $validRestrictions, true)) {
            return response()->json(['success' => false, 'message' => 'Restriction invalide'], 422);
        }

        $user = Membre::query()->whereKey($id)->first(['id', 'titre']);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
        }

        if ($isAdmin && in_array((string) $user->titre, ['Super-Admin', 'Admin'], true)) {
            return response()->json(['success' => false, 'message' => 'Vous n\'avez pas les permissions pour modifier cette restriction'], 403);
        }

        DB::table('membres')->where('id', $id)->update(['restriction' => $newRestriction]);

        return response()->json(['success' => true, 'newValue' => $newRestriction, 'message' => 'Restriction mise à jour avec succès']);
    }

    public function updateEmail(Request $request)
    {
        [$isAdmin] = $this->getAdminFlags($request);

        $id = is_numeric($request->input('id')) ? (int) $request->input('id') : 0;
        $newEmail = (string) $request->input('newEmail', '');
        if ($id <= 0 || ! filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'Format d\'email invalide'], 422);
        }

        $user = Membre::query()->whereKey($id)->first(['id', 'titre']);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
        }

        if ($isAdmin && in_array((string) $user->titre, ['Super-Admin', 'Admin'], true)) {
            return response()->json(['success' => false, 'message' => 'Vous n\'avez pas les permissions pour modifier cet email'], 403);
        }

        DB::table('membres')->where('id', $id)->update(['email' => $newEmail]);

        $this->notifyUser(
            $id,
            'Email modifié',
            'Votre adresse email a été modifiée par un administrateur.',
            'email_change_admin'
        );

        return response()->json(['success' => true, 'newValue' => $newEmail, 'message' => 'Email mis à jour avec succès']);
    }

    public function updateUsername(Request $request)
    {
        [$isAdmin] = $this->getAdminFlags($request);

        $id = is_numeric($request->input('id')) ? (int) $request->input('id') : 0;
        $newUsername = trim((string) $request->input('newUsername', ''));
        if ($id <= 0 || $newUsername === '') {
            return response()->json(['success' => false, 'message' => 'Le pseudo ne peut pas être vide'], 422);
        }

        $user = Membre::query()->whereKey($id)->first(['id', 'titre']);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
        }

        if ($isAdmin && in_array((string) $user->titre, ['Super-Admin', 'Admin'], true)) {
            return response()->json(['success' => false, 'message' => 'Vous n\'avez pas les permissions pour modifier ce pseudo'], 403);
        }

        $exists = DB::table('membres')->where('username', $newUsername)->where('id', '!=', $id)->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Ce pseudo est déjà utilisé'], 422);
        }

        DB::table('membres')->where('id', $id)->update(['username' => $newUsername]);

        $this->notifyUser(
            $id,
            'Pseudo modifié',
            'Votre pseudo a été modifié par un administrateur : '.$newUsername,
            'username_change_admin'
        );

        return response()->json(['success' => true, 'newValue' => $newUsername, 'message' => 'Pseudo mis à jour avec succès']);
    }

    public function updateWarningReward(Request $request)
    {
        [$isAdmin] = $this->getAdminFlags($request);

        $id = is_numeric($request->input('id')) ? (int) $request->input('id') : 0;
        $type = (string) $request->input('type', '');
        $increment = is_numeric($request->input('increment')) ? (int) $request->input('increment') : 0;
        $reason = trim((string) $request->input('reason', ''));

        if ($id <= 0 || ! in_array($type, ['avertissements', 'recompenses'], true) || ! in_array($increment, [-1, 1], true)) {
            return response()->json(['success' => false, 'message' => 'Paramètres invalides'], 422);
        }

        $user = Membre::query()->whereKey($id)->first(['id', 'titre', $type]);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Membre non trouvé'], 404);
        }

        if ($isAdmin && in_array((string) $user->titre, ['Super-Admin', 'Admin'], true)) {
            return response()->json(['success' => false, 'message' => 'Vous n\'avez pas les permissions pour modifier ces valeurs'], 403);
        }

        $current = is_numeric($user->{$type}) ? (int) $user->{$type} : 0;
        $newValue = max(0, $current + $increment);

        DB::table('membres')->where('id', $id)->update([$type => $newValue]);

        if ($type === 'avertissements') {
            $message = $increment > 0
                ? '⚠️ Vous avez reçu un avertissement'.($reason !== '' ? ' : '.$reason : '').'. (Total: '.$newValue.' avertissement'.($newValue > 1 ? 's' : '').')'
                : '✅ Un de vos avertissements a été retiré'.($reason !== '' ? ' : '.$reason : '').'. (Total: '.$newValue.' avertissement'.($newValue > 1 ? 's' : '').')';

            $this->notifyUser($id, 'Avertissement', $message, 'warning_admin');
        } else {
            $message = $increment > 0
                ? '🎁 Vous avez reçu '.abs($increment).' récompense'.(abs($increment) > 1 ? 's' : '').($reason !== '' ? ' : '.$reason : '').'. (Total: '.$newValue.')'
                : '🎁 '.abs($increment).' récompense'.(abs($increment) > 1 ? 's' : '').' a été retirée'.($reason !== '' ? ' : '.$reason : '').'. (Total: '.$newValue.')';

            $this->notifyUser($id, 'Récompenses', $message, 'reward_admin');
        }

        return response()->json(['success' => true, 'newValue' => $newValue]);
    }

    public function traiterPromotion(Request $request)
    {
        $titre = (string) $request->session()->get('titre', '');
        if (! in_array($titre, ['Admin', 'Super-Admin'], true)) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $userId = is_numeric($request->input('user_id')) ? (int) $request->input('user_id') : 0;
        $action = (string) $request->input('action', '');
        if ($userId <= 0 || ! in_array($action, ['approve', 'reject'], true)) {
            return response()->json(['success' => false, 'message' => 'Paramètres manquants'], 422);
        }

        try {
            if ($action === 'reject') {
                $updated = DB::table('membres')->where('id', $userId)->where('demande_promotion', 1)->update(['demande_promotion' => 0]);
                if (! $updated) {
                    return response()->json(['success' => false, 'message' => 'Aucune demande de promotion trouvée pour cet utilisateur'], 404);
                }

                return response()->json(['success' => true, 'message' => 'Demande de promotion rejetée']);
            }

            $result = DB::transaction(function () use ($userId) {
                $user = DB::table('membres')
                    ->where('id', $userId)
                    ->lockForUpdate()
                    ->first(['id', 'demande_promotion', 'titre', 'recompenses']);

                if (! $user || (int) $user->demande_promotion !== 1) {
                    return ['ok' => false, 'status' => 404, 'message' => 'Aucune demande de promotion trouvée pour cet utilisateur'];
                }

                $titreActuel = (string) $user->titre;
                $recompenses = is_numeric($user->recompenses) ? (int) $user->recompenses : 0;

                $coutParTitre = [
                    'Membre' => 3,
                    'Amateur' => 6,
                    'Fan' => 9,
                    'NoLife' => 12,
                ];
                $ordre = ['Membre', 'Amateur', 'Fan', 'NoLife'];
                $index = array_search($titreActuel, $ordre, true);
                $titreSuivant = ($index !== false && $index < count($ordre) - 1) ? $ordre[$index + 1] : $titreActuel;

                $cout = $coutParTitre[$titreActuel] ?? 0;
                if ($cout <= 0 || $titreSuivant === $titreActuel) {
                    return ['ok' => false, 'status' => 422, 'message' => 'Promotion impossible pour ce titre'];
                }
                if ($recompenses < $cout) {
                    return ['ok' => false, 'status' => 422, 'message' => 'Récompenses insuffisantes pour approuver cette promotion'];
                }

                DB::table('membres')->where('id', $userId)->update([
                    'titre' => $titreSuivant,
                    'recompenses' => $recompenses - $cout,
                    'demande_promotion' => 0,
                ]);

                $this->notifyUser(
                    $userId,
                    '🎉 Promotion acceptée !',
                    "Félicitations ! Votre demande de promotion a été acceptée avec succès ! Vous êtes maintenant $titreSuivant. $cout récompenses ont été déduites de votre compte.",
                    'promotion'
                );

                return ['ok' => true, 'new_title' => $titreSuivant];
            });

            if (! ($result['ok'] ?? false)) {
                return response()->json(['success' => false, 'message' => $result['message'] ?? 'Erreur lors de l’approbation de la promotion'], (int) ($result['status'] ?? 500));
            }

            return response()->json([
                'success' => true,
                'message' => 'Promotion approuvée avec succès',
                'new_title' => $result['new_title'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erreur interne du serveur'], 500);
        }
    }

    private function getAdminFlags(Request $request): array
    {
        $titre = (string) $request->session()->get('titre', '');
        $isAdmin = $titre === 'Admin';
        $isSuperAdmin = $titre === 'Super-Admin';

        if (! $isAdmin && ! $isSuperAdmin) {
            abort(403);
        }

        return [$isAdmin, $isSuperAdmin];
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
}
