<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function showLogin(Request $request)
    {
        if ($request->session()->get('loggedin') === true) {
            return redirect()->route('accueil');
        }

        $referer = $request->headers->get('referer');
        $previous = $request->session()->get('initial_previous_page');

        if (! $previous && $referer) {
            $host = parse_url($referer, PHP_URL_HOST);
            $path = parse_url($referer, PHP_URL_PATH);
            $basename = $path ? basename($path) : null;

            if ($host === $request->getHost() && ! in_array($basename, ['login', 'register'], true)) {
                $request->session()->put('initial_previous_page', $referer);
            }
        }

        return view('Auth.login', [
            'title' => 'Connexion',
            'previousPage' => $request->session()->get('initial_previous_page', route('accueil')),
        ]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'previous_page' => ['nullable', 'string'],
        ]);

        $this->authService->attemptLogin($data['username'], $data['password'], $request);

        $redirectTo = $data['previous_page'] ?? $request->session()->get('initial_previous_page') ?? route('accueil');
        $request->session()->forget('initial_previous_page');

        $host = parse_url($redirectTo, PHP_URL_HOST);
        if ($host && $host !== $request->getHost()) {
            $redirectTo = route('accueil');
        }

        return redirect()->to($redirectTo)->with('status', 'Connexion réussie.');
    }

    public function showRegister(Request $request)
    {
        if ($request->session()->get('loggedin') === true) {
            return redirect()->route('accueil');
        }

        return view('Auth.register', [
            'title' => 'Enregistrement',
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:membres,username'],
            'email' => ['nullable', 'email', 'unique:membres,email'],
            'password' => ['required', 'string', 'min:8'],
            'politique_acceptee' => ['accepted'],
        ]);

        $this->authService->register($data);

        return redirect()->route('login.show')->with('status', 'Enregistrement réussi.');
    }

    public function logout(Request $request)
    {
        $request->session()->forget([
            'loggedin',
            'username',
            'user_id',
            'titre',
            'photo_profil',
        ]);

        $request->session()->regenerate();

        return redirect()->route('accueil')->with('status', 'Déconnexion réussie.');
    }
}
