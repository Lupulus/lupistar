<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MonCompteController extends Controller
{
    public function show(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        if (! $userId) {
            return redirect()->route('login.show');
        }

        $user = DB::table('membres')->where('id', $userId)->first();

        $totalFilms = (int) DB::table('membres_films_list')->where('membres_id', $userId)->count();

        $avgRating = DB::table('membres_films_list')
            ->where('membres_id', $userId)
            ->whereNotNull('note')
            ->avg('note');
        $avgRating = $avgRating !== null ? (float) $avgRating : null;

        $ratedFilms = (int) DB::table('membres_films_list')
            ->where('membres_id', $userId)
            ->whereNotNull('note')
            ->count();

        $approvedFilms = (int) DB::table('films_temp')
            ->where('propose_par', $userId)
            ->where('statut', 'approuve')
            ->count();

        $statsCategories = DB::table('films as f')
            ->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('f.id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            })
            ->selectRaw('f.categorie, COUNT(*) as c')
            ->groupBy('f.categorie')
            ->pluck('c', 'f.categorie')
            ->toArray();

        return view('MonCompte.index', [
            'title' => 'Mon Compte',
            'username' => $request->session()->get('username'),
            'titre' => $request->session()->get('titre'),
            'photo_profil' => $request->session()->get('photo_profil', 'img/img-profile/profil.png'),
            'email' => $user->email ?? '',
            'total_films' => $totalFilms,
            'avg_rating' => $avgRating,
            'rated_films' => $ratedFilms,
            'approved_films' => $approvedFilms,
            'recompenses' => (int) ($user->recompenses ?? 0),
            'avertissements' => (int) ($user->avertissements ?? 0),
            'stats_categories' => $statsCategories,
        ]);
    }

    public function demanderPromotion(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
        }

        $result = DB::transaction(function () use ($userId) {
            $user = DB::table('membres')
                ->where('id', $userId)
                ->lockForUpdate()
                ->first(['id', 'titre', 'recompenses', 'demande_promotion']);

            if (! $user) {
                return ['ok' => false, 'status' => 404, 'message' => 'Utilisateur non trouvé'];
            }

            if ((int) ($user->demande_promotion ?? 0) === 1) {
                return ['ok' => false, 'status' => 422, 'message' => 'Une demande de promotion est déjà en cours'];
            }

            $titreActuel = (string) ($user->titre ?? '');
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
                return ['ok' => false, 'status' => 422, 'message' => 'Ce titre ne peut pas être promu automatiquement'];
            }

            if ($recompenses < $cout) {
                $manquant = $cout - $recompenses;
                return ['ok' => false, 'status' => 422, 'message' => "Il vous manque $manquant récompense(s) pour demander cette promotion"];
            }

            DB::table('membres')->where('id', $userId)->update([
                'demande_promotion' => 1,
            ]);

            $this->notifyUser(
                $userId,
                'Demande de promotion',
                "Votre demande de promotion vers le titre \"$titreSuivant\" a été soumise. Elle sera examinée par un administrateur.",
                'promotion_request'
            );

            return ['ok' => true];
        });

        if (! ($result['ok'] ?? false)) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Erreur'], (int) ($result['status'] ?? 500));
        }

        return response()->json(['success' => true, 'message' => 'Demande de promotion soumise avec succès']);
    }

    public function updateEmail(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return redirect()->route('login.show');
        }

        $data = $request->validate([
            'new_email' => ['required', 'email:rfc,dns'],
        ]);

        DB::table('membres')->where('id', $userId)->update([
            'email' => $data['new_email'],
        ]);

        return back()->with('status', 'Adresse e-mail mise à jour');
    }

    public function updatePassword(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return redirect()->route('login.show');
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        $user = DB::table('membres')->where('id', $userId)->first();
        if (! $user || ! Hash::check($data['current_password'], (string) $user->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect'])->withInput();
        }

        DB::table('membres')->where('id', $userId)->update([
            'password' => Hash::make($data['new_password']),
        ]);

        return back()->with('status', 'Mot de passe mis à jour');
    }

    public function uploadCroppedPhoto(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Non connecté'], 401);
        }

        if (! $request->hasFile('cropped_image')) {
            return response()->json(['success' => false, 'message' => 'Aucune image'], 400);
        }
        $file = $request->file('cropped_image');
        $ext = Str::lower($file->getClientOriginalExtension());
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            $ext = 'png';
        }
        $dir = public_path('img/img-profile');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $filename = 'user_'.$userId.'.'.$ext;
        $file->move($dir, $filename);
        $rel = 'img/img-profile/'.$filename;

        DB::table('membres')->where('id', $userId)->update(['photo_profil' => $rel]);
        $request->session()->put('photo_profil', $rel);

        return response()->json(['success' => true, 'path' => asset($rel)]);
    }

    public function getCategoriesOrder(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return response()->json(['success' => true, 'order' => $this->defaultCategories()]);
        }

        $row = DB::table('user_preferences')
            ->where('user_id', $userId)
            ->where('preference_type', 'categories_order')
            ->first();

        if (! $row) {
            return response()->json(['success' => true, 'order' => $this->defaultCategories()]);
        }
        $arr = json_decode((string) $row->preference_value, true);
        if (! is_array($arr)) {
            $arr = $this->defaultCategories();
        }

        return response()->json(['success' => true, 'order' => array_values($arr)]);
    }

    public function saveCategoriesOrder(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Non connecté'], 401);
        }

        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['string'],
        ]);

        $value = json_encode(array_values($data['order']), JSON_UNESCAPED_UNICODE);

        $exists = DB::table('user_preferences')
            ->where('user_id', $userId)
            ->where('preference_type', 'categories_order')
            ->exists();

        if ($exists) {
            DB::table('user_preferences')
                ->where('user_id', $userId)
                ->where('preference_type', 'categories_order')
                ->update(['preference_value' => $value, 'updated_at' => now()]);
        } else {
            DB::table('user_preferences')->insert([
                'user_id' => $userId,
                'preference_type' => 'categories_order',
                'preference_value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function privacyPolicyStatus(Request $request)
    {
        try {
            $currentVersionRaw = DB::table('site_settings')->where('key', 'privacy_policy_version')->value('value');
            $currentVersion = is_numeric($currentVersionRaw) ? (int) $currentVersionRaw : 0;
            $message = (string) (DB::table('site_settings')->where('key', 'privacy_policy_message')->value('value') ?? '');
            $updatedAt = (string) (DB::table('site_settings')->where('key', 'privacy_policy_updated_at')->value('value') ?? '');
        } catch (\Throwable) {
            return response()->json([
                'success' => true,
                'should_show' => false,
                'current_version' => 0,
                'ack_version' => 0,
            ]);
        }

        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        $ackVersion = 0;
        if ($userId) {
            $raw = DB::table('user_preferences')
                ->where('user_id', $userId)
                ->where('preference_type', 'privacy_policy_ack')
                ->value('preference_value');
            $ackVersion = is_numeric($raw) ? (int) $raw : 0;
        } else {
            $cookieValue = $request->cookie('pp_ack');
            $ackVersion = is_numeric($cookieValue) ? (int) $cookieValue : 0;
        }

        return response()->json([
            'success' => true,
            'should_show' => $currentVersion > 0 && $ackVersion < $currentVersion,
            'current_version' => $currentVersion,
            'ack_version' => $ackVersion,
            'message' => $message,
            'updated_at' => $updatedAt,
            'policy_url' => route('confidentialite'),
        ]);
    }

    public function acknowledgePrivacyPolicy(Request $request)
    {
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:0'],
        ]);

        $version = (int) $data['version'];
        $cookie = cookie('pp_ack', (string) $version, 60 * 24 * 365);

        $userId = $request->session()->get('user_id');
        $userId = is_numeric($userId) ? (int) $userId : null;

        if ($userId) {
            $exists = DB::table('user_preferences')
                ->where('user_id', $userId)
                ->where('preference_type', 'privacy_policy_ack')
                ->exists();

            if ($exists) {
                DB::table('user_preferences')
                    ->where('user_id', $userId)
                    ->where('preference_type', 'privacy_policy_ack')
                    ->update(['preference_value' => (string) $version, 'updated_at' => now()]);
            } else {
                DB::table('user_preferences')->insert([
                    'user_id' => $userId,
                    'preference_type' => 'privacy_policy_ack',
                    'preference_value' => (string) $version,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json(['success' => true])->cookie($cookie);
    }

    private function defaultCategories(): array
    {
        return ['Film', 'Série', 'Animation', "Série d'Animation", 'Anime'];
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
