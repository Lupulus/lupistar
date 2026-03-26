@extends('layouts.auth')

@section('content')
    <div class="form-container">
        @if($valid)
            <h2>Nouveau mot de passe</h2>
            <p style="text-align: center; color: var(--text-medium-gray); margin-bottom: 25px; font-size: 14px; line-height: 1.5;">
                Choisissez un nouveau mot de passe sécurisé pour votre compte.
            </p>

            <form action="{{ route('password.reset.perform') }}" method="post">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label for="password">Nouveau mot de passe :</label>
                    <input type="password" id="password" name="password" placeholder="Minimum 8 caractères" required minlength="8">
                </div>
                <div>
                    <label for="password_confirmation">Confirmer le mot de passe :</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Retapez votre mot de passe" required minlength="8">
                </div>
                <div>
                    <button type="submit">Changer le mot de passe</button>
                </div>
            </form>
        @else
            <h2>Lien invalide</h2>
            <p style="text-align: center; color: var(--error-red); margin-bottom: 25px; font-size: 14px; line-height: 1.5;">
                Ce lien de réinitialisation est invalide ou a expiré. Les liens sont valides pendant 2 heures seulement.
            </p>
        @endif

        <div class="links">
            <a href="{{ route('login.show') }}">Retour à la connexion</a>
            <a href="{{ route('password.forgot.show') }}">Demander un nouveau lien</a>
        </div>

        @if(session('error'))
            <div id="message-container" class="error"><p>{{ session('error') }}</p></div>
        @endif
        @if(session('status'))
            <div id="message-container" class="success"><p>{{ session('status') }}</p></div>
        @endif
    </div>
@endsection

