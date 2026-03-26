<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function showForgot()
    {
        return view('Auth.forgot-password', ['title' => 'Récupération de mot de passe']);
    }

    public function sendForgot(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $user = DB::table('membres')
            ->where(function ($q) use ($data) {
                $q->where('username', $data['identifier'])->orWhere('email', $data['identifier']);
            })
            ->whereNotNull('email')
            ->first();

        if ($user) {
            DB::table('password_resets')->where('user_id', $user->id)->delete();
            $token = Str::random(64);
            $expiresAt = now()->addHours(2);
            DB::table('password_resets')->insert([
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => $expiresAt,
                'created_at' => now(),
            ]);

            $link = route('password.reset.show', ['token' => $token]);

            return back()->with('status', 'Un lien de réinitialisation a été généré.')
                ->with('reset_link', $link);
        }

        return back()->with('status', 'Si ce compte existe, un lien a été envoyé.');
    }

    public function showReset(Request $request, string $token)
    {
        $row = DB::table('password_resets')
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        return view('Auth.reset-password', [
            'title' => 'Nouveau mot de passe',
            'token' => $token,
            'valid' => (bool) $row,
        ]);
    }

    public function performReset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $row = DB::table('password_resets')
            ->where('token', $data['token'])
            ->where('expires_at', '>', now())
            ->first();

        if (! $row) {
            return back()->with('error', 'Ce lien est invalide ou expiré.');
        }

        DB::table('membres')->where('id', $row->user_id)->update([
            'password' => Hash::make($data['password']),
        ]);

        DB::table('password_resets')->where('user_id', $row->user_id)->delete();

        return redirect()->route('login.show')->with('status', 'Mot de passe modifié avec succès.');
    }
}
