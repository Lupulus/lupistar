@extends('layouts.auth')

@section('content')
    <div class="form-container">
        <h2>Récupération de mot de passe</h2>
        <p style="text-align: center; color: var(--text-medium-gray); margin-bottom: 25px; font-size: 14px; line-height: 1.5;">
            Entrez votre nom d'utilisateur ou votre adresse e-mail pour recevoir un lien de réinitialisation de votre mot de passe.
        </p>

        <form action="{{ route('password.forgot.perform') }}" method="post">
            @csrf
            <div>
                <label for="identifier">Nom d'utilisateur ou E-mail :</label>
                <input type="text" id="identifier" name="identifier" placeholder="Pseudo ou adresse e-mail" required value="{{ old('identifier') }}">
                @error('identifier')
                    <div id="message-container" class="error"><p>{{ $message }}</p></div>
                @enderror
            </div>
            <div>
                <button type="submit">Envoyer le lien de récupération</button>
            </div>
        </form>

        <div class="links">
            <a href="{{ route('login.show') }}">Retour à la connexion</a>
            <a href="{{ route('register.show') }}">Créer un compte</a>
            <a href="{{ route('accueil') }}">Retour à l'accueil</a>
        </div>

        @if(session('status'))
            <div id="message-container" class="success"><p>{{ session('status') }}</p></div>
        @endif
        @if(session('reset_link'))
            <div id="message-container" class="warning"><p>Lien de test (dev): <a href="{{ session('reset_link') }}">{{ session('reset_link') }}</a></p></div>
        @endif
    </div>
@endsection

