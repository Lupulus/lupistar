@extends('layouts.auth')

@section('content')
    <div class="form-container">
        <h2>Enregistrement</h2>

        <form action="{{ route('register.perform') }}" method="post">
            @csrf
            <div>
                <label for="username">Nom d'utilisateur :</label>
                <input type="text" id="username" name="username" placeholder="Nom d'utilisateur (Pseudo)" required value="{{ old('username') }}">
                @error('username')
                    <div id="message-container" class="error"><p>{{ $message }}</p></div>
                @enderror
            </div>
            <div>
                <label for="email">Adresse e-mail (facultatif) :</label>
                <input type="email" id="email" name="email" placeholder="votre@email.com" value="{{ old('email') }}">
                <div class="email-info">
                    Il est recommandé de fournir un e-mail pour pouvoir récupérer votre mot de passe en cas d'oubli.
                </div>
                @error('email')
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
            <div class="checkbox-container">
                <input type="checkbox" id="politique_acceptee" name="politique_acceptee" required>
                <label for="politique_acceptee">
                    J'accepte la <a href="{{ url('/confidentialite.php') }}" target="_blank"> politique de confidentialité</a> *
                </label>
                @error('politique_acceptee')
                    <div id="message-container" class="error"><p>{{ $message }}</p></div>
                @enderror
            </div>
            <div>
                <button type="submit" id="submit-btn" disabled>S'enregistrer</button>
            </div>
        </form>

        <div class="links">
            <a href="{{ route('login.show') }}">Se connecter</a>
            <a href="{{ route('accueil') }}">Retour à l'accueil</a>
        </div>

        @if(session('status'))
            <div id="message-container" class="success"><p>{{ session('status') }}</p></div>
        @endif
    </div>

    <script>
        function toggleSubmitButton() {
            const checkbox = document.getElementById('politique_acceptee');
            const submitBtn = document.getElementById('submit-btn');

            if (checkbox.checked) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            } else {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
                submitBtn.style.cursor = 'not-allowed';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const checkbox = document.getElementById('politique_acceptee');
            toggleSubmitButton();
            checkbox.addEventListener('change', toggleSubmitButton);
        });
    </script>
@endsection

