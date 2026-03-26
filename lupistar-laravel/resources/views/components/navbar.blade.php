<nav class="navbar">
    <img src="{{ asset('gif/logogif.GIF') }}" alt="" class="gif">
    <ul class="menu">
        <a class="btn {{ request()->routeIs('accueil') ? 'active' : '' }}" id="btn1" href="{{ route('accueil') }}">Accueil</a>
        <a class="btn {{ request()->routeIs('liste') ? 'active' : '' }}" id="btn2" href="{{ route('liste') }}">Liste</a>
        <a class="btn {{ request()->routeIs('ma-liste') ? 'active' : '' }}" id="btn3" href="{{ route('ma-liste') }}">Ma Liste</a>
        <a class="btn {{ request()->routeIs('forum') ? 'active' : '' }}" id="btn4" href="{{ route('forum') }}">Forum</a>
    </ul>
    <div class="profil" id="profil">
        <x-profile-image imgId="profilImg" />
        <div class="menu-deroulant" id="deroulant">
            <x-profile-menu />
        </div>
    </div>
</nav>

