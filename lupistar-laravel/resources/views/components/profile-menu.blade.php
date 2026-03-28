@php
    $loggedIn = session('loggedin') === true;
    $status = session('titre', 'Membre');
    $userId = session('user_id');
    $canProposeFilm = ($status === 'Membre');
@endphp

@if($loggedIn)
    <a class="deco" href="{{ route('logout') }}">Se déconnecter</a><br>

    <a class="mon-compte" href="{{ route('mon-compte') }}">Mon compte
        @if($userId)
            <div class="notification-badge hidden" id="menuNotificationBadge">0</div>
        @endif
    </a><br>

    <div class="deroulant-notif">
        @if(!$canProposeFilm)
            <a class="demande-film dernier-element" href="{{ route('proposer-film.show') }}">Proposer un film</a><br>
        @else
            <div class="film-restriction-container">
                <a class="demande-film dernier-element disabled" href="#" disabled>Proposer un film</a>
                <div class="restriction-tooltip">
                    <span class="tooltip-text">Titre "Amateur" requis</span>
                    <span class="tooltip-arrow">◀</span>
                </div>
            </div>
        @endif
    </div>

    @if($status === 'Super-Admin' || $status === 'Admin')
        <a class="admin element" href="{{ route('administration') }}">Administration</a><br>
    @endif
@else
    <a class="co" href="{{ route('login.show') }}">Se connecter</a><br>
    <a class="co" href="{{ route('register.show') }}">Créer un compte</a><br>
    <a class="co dernier-element" href="{{ route('password.forgot.show') }}">Mot de passe oublié</a><br>
@endif
