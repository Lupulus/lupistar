<nav class="navbar">
    <img src="{{ asset('gif/logogif.GIF') }}" alt="" class="gif">
    @php
        $titre = session('titre');
        $isAdmin = $titre === 'Admin';
        $isSuperAdmin = $titre === 'Super-Admin';
        $isAdminArea = request()->routeIs('administration*') || request()->routeIs('membres') || request()->routeIs('database');
        $useAdminNavbar = ($isAdmin || $isSuperAdmin) && $isAdminArea;
    @endphp
    <ul class="menu">
        <a class="btn {{ request()->routeIs('accueil') ? 'active' : '' }}" id="btn1" href="{{ route('accueil') }}">Accueil</a>
        @if($useAdminNavbar)
            <a class="btn {{ request()->routeIs('administration*') ? 'active' : '' }}" id="btn2" href="{{ route('administration') }}">Administration</a>
            <a class="btn {{ request()->routeIs('membres') ? 'active' : '' }}" id="btn3" href="{{ route('membres') }}">Membres</a>
            @if($isSuperAdmin)
                <a class="btn {{ request()->routeIs('database') ? 'active' : '' }}" id="btn4" href="{{ route('database') }}">Base de données</a>
            @endif
        @else
            <a class="btn {{ request()->routeIs('liste') ? 'active' : '' }}" id="btn2" href="{{ route('liste') }}">Liste</a>
            <a class="btn {{ request()->routeIs('ma-liste') ? 'active' : '' }}" id="btn3" href="{{ route('ma-liste') }}">Ma Liste</a>
            <a class="btn {{ request()->routeIs('forum') ? 'active' : '' }}" id="btn4" href="{{ route('forum') }}">Forum</a>
        @endif
    </ul>
    <div class="profil" id="profil">
        <x-profile-image imgId="profilImg" />
        <div class="menu-deroulant" id="deroulant">
            <x-profile-menu />
        </div>
    </div>
</nav>

