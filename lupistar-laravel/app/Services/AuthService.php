<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function attemptLogin(string $username, string $password, Request $request): void
    {
        $user = DB::table('membres')->where('username', $username)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => "Nom d'utilisateur ou mot de passe incorrect.",
            ]);
        }

        $request->session()->put('loggedin', true);
        $request->session()->put('username', $user->username);
        $request->session()->put('user_id', $user->id);
        $request->session()->put('titre', $user->titre);
        $request->session()->put('photo_profil', $user->photo_profil);
    }

    public function register(array $data): int
    {
        $now = now();

        $id = DB::table('membres')->insertGetId([
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'email' => $data['email'] ?? null,
            'titre' => 'Membre',
            'politique_acceptee' => true,
            'date_creation' => $now,
        ]);

        DB::table('notifications')->insert([
            'user_id' => $id,
            'type' => 'welcome',
            'message' => '🎉 Bienvenue sur Lupistar ! Nous sommes ravis de vous compter parmi nous. Explorez notre collection de films et profitez de votre expérience cinématographique !',
            'titre' => 'Bienvenue !',
            'date_creation' => $now,
            'lu' => false,
        ]);

        return (int) $id;
    }
}
