@extends('layouts.auth')

@section('content')
    <div class="form-container">
        <h2>Connexion</h2>

        <form action="{{ route('login.perform') }}" method="post">
            @csrf
            <div>
                <label for="username">Nom d'utilisateur :</label>
                <input type="text" id="username" name="username" placeholder="Nom d'utilisateur (Pseudo)" required value="{{ old('username') }}">
                @error('username')
                    <div id="message-container" class="error"><p>{{ $message }}</p></div>
                @enderror
            </div>
            <div>
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" placeholder="Mot de passe" required>
                @error('password')
                    <div id="message-container" class="error"><p>{{ $message }}</p></div>
                @enderror
            </div>
            <input type="hidden" name="previous_page" value="{{ $previousPage ?? route('accueil') }}">
            <div>
                <button type="submit">Se connecter</button>
            </div>
        </form>

        <div class="links">
            <a href="{{ route('register.show') }}">Créer un compte</a>
            <a href="{{ route('accueil') }}">Retour à l'accueil</a>
        </div>

        @if(session('status'))
            <div id="message-container" class="success"><p>{{ session('status') }}</p></div>
        @endif
    </div>
@endsection

