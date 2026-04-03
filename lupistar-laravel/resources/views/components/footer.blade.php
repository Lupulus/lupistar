<footer>
    <div class="footer-top">
        <p class="footer-copyright">&copy; {{ date('Y') }} lupistar.fr — Tous droits réservés.</p>
        <img src="{{ asset('gif/logogif.GIF') }}" alt="Lupistar" class="footer-gif">
    </div>
    <p>Les illustrations sont la propriété de leurs auteurs et éditeurs respectifs.</p>
    <div class="tmdb-credit">
        <p>Les données peuvent être fournies par TMDb. Ce site n'est pas affilié à TMDb.</p>
        <img src="/img/tmdb-logo.svg" alt="TMDb">
    </div>
    <nav>
        <a href="{{ route('mentions-legales') }}">Mentions légales</a> |
        <a href="{{ route('confidentialite') }}">Politique de confidentialité</a>
    </nav>
</footer>
