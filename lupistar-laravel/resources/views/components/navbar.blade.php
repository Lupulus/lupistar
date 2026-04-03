<nav class="navbar">
    <a class="navbar-brand" href="{{ route('accueil') }}" aria-label="Accueil">
        <img src="{{ asset('logo-lupistar.png') }}" alt="Logo Lupistar" class="navbar-logo">
    </a>
    @php
        $titre = session('titre');
        $isAdmin = $titre === 'Admin';
        $isSuperAdmin = $titre === 'Super-Admin';
        $userId = session('user_id');
        $restrictionList = [];
        if (is_numeric($userId)) {
            $raw = (string) (\DB::table('membres')->where('id', (int) $userId)->value('restriction') ?? '');
            $restrictionList = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), explode(',', $raw)), static fn ($v) => $v !== '' && $v !== 'Aucune'));
        }
        $forumAccessOff = in_array('Forum Accès Off', $restrictionList, true);
        $membersAccessOff = $isAdmin && in_array('Admin Membres Off', $restrictionList, true);
        $isAdminArea = request()->routeIs('administration*') || request()->routeIs('membres') || request()->routeIs('database');
        $useAdminNavbar = ($isAdmin || $isSuperAdmin) && $isAdminArea;
    @endphp
    <ul class="menu">
        <a class="btn {{ request()->routeIs('accueil') ? 'active' : '' }}" id="btn1" href="{{ route('accueil') }}">Accueil</a>
        @if($useAdminNavbar)
            <a class="btn {{ request()->routeIs('administration*') ? 'active' : '' }}" id="btn2" href="{{ route('administration') }}">Administration</a>
            @if(! $membersAccessOff)
                <a class="btn {{ request()->routeIs('membres') ? 'active' : '' }}" id="btn3" href="{{ route('membres') }}">Membres</a>
            @endif
            @if($isSuperAdmin)
                <a class="btn {{ request()->routeIs('database') ? 'active' : '' }}" id="btn4" href="{{ route('database') }}">Base de données</a>
            @endif
        @else
            <a class="btn {{ request()->routeIs('liste') ? 'active' : '' }}" id="btn2" href="{{ route('liste') }}">Liste</a>
            <a class="btn {{ request()->routeIs('ma-liste') ? 'active' : '' }}" id="btn3" href="{{ route('ma-liste') }}">Ma Liste</a>
            @if(! $forumAccessOff)
                <a class="btn {{ request()->routeIs('forum') ? 'active' : '' }}" id="btn4" href="{{ route('forum') }}">Forum</a>
            @endif
        @endif
    </ul>
    <div class="profil" id="profil">
        <x-profile-image imgId="profilImg" />
        <div class="menu-deroulant" id="deroulant">
            <x-profile-menu />
        </div>
    </div>
</nav>

