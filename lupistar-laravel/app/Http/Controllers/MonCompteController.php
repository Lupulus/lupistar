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

        $bestAuthor = DB::table('films as f')
            ->join('membres_films_list as mfl', function ($join) use ($userId) {
                $join->on('f.id', '=', 'mfl.films_id')->where('mfl.membres_id', '=', $userId);
            })
            ->leftJoin('auteurs as a', 'f.auteur_id', '=', 'a.id')
            ->selectRaw('a.nom as nom, COUNT(*) as c')
            ->groupBy('a.nom')
            ->orderByDesc('c')
            ->limit(1)
            ->first();

        $avgRating = DB::table('membres_films_list')->where('membres_id', $userId)->avg('note');
        $avgRating = $avgRating !== null ? (float) $avgRating : null;

        $approvedFilms = (int) DB::table('films_temp')->where('propose_par', $userId)->where('statut', 'approuve')->count();

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
            'best_author' => $bestAuthor?->nom ?? null,
            'avg_rating' => $avgRating,
            'approved_films' => $approvedFilms,
            'recompenses' => (int) ($user->recompenses ?? 0),
            'avertissements' => (int) ($user->avertissements ?? 0),
            'stats_categories' => $statsCategories,
        ]);
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

    private function defaultCategories(): array
    {
        return ['Film', 'Animation', 'Anime', 'Série', "Série d'Animation"];
    }
}
