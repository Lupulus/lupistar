document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('home-reco-container');
    const refreshBtn = document.getElementById('home-reco-refresh');
    if (!container || !refreshBtn) return;

    const includeSeen = document.getElementById('home-reco-include-seen');
    const apiUrl = container.getAttribute('data-api-url') || '/api/accueil/recommendations';

    const setLoading = (loading) => {
        refreshBtn.disabled = loading;
        container.classList.toggle('is-loading', loading);
    };

    const fetchRecommendations = async () => {
        setLoading(true);
        try {
            const url = new URL(apiUrl, window.location.origin);
            if (includeSeen && includeSeen.checked) url.searchParams.set('include_seen', '1');
            url.searchParams.set('seed', String(Date.now() % 1000000));

            const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const data = await res.json();
            if (!data?.success) return;
            container.innerHTML = data.html || '';
        } catch {
        } finally {
            setLoading(false);
        }
    };

    refreshBtn.addEventListener('click', fetchRecommendations);
    includeSeen?.addEventListener('change', fetchRecommendations);

    document.addEventListener('click', (event) => {
        const card = event.target.closest('.home-reco-card, .home-voyage-card');
        if (!card) return;
        const filmId = card.getAttribute('data-id');
        if (!filmId) return;
        if (typeof window.openFilmModalForFilmId === 'function') {
            window.openFilmModalForFilmId(filmId);
        }
    });
});
