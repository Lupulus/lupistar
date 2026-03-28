document.addEventListener('DOMContentLoaded', () => {
    const searchBar = document.getElementById('search-bar');
    const studioFilter = document.getElementById('studio-filter');
    const anneeFilter = document.getElementById('annee-filter');
    const noteFilter = document.getElementById('note-filter');
    const paysFilter = document.getElementById('pays-filter');
    const typeFilter = document.getElementById('type-filter');
    const episodesFilter = document.getElementById('episodes-filter');
    const searchBtn = document.querySelector('.search-btn');
    const resetBtn = document.querySelector('.reset-btn');
    const filmsContainer = document.getElementById('films-container');
    const paginationContainer = document.getElementById('pagination-container');
    const tabsContainer = document.querySelector('.tab');

    if (!searchBar || !studioFilter || !anneeFilter || !noteFilter || !searchBtn || !resetBtn || !filmsContainer || !paginationContainer) {
        return;
    }

    const isMyList = window.location.pathname.includes('/ma-liste');
    const apiBase = isMyList ? '/api/ma-liste' : '/api/liste';

    const setActiveCategory = (category) => {
        const buttons = document.querySelectorAll('.tab .tablinks');
        buttons.forEach((b) => b.classList.remove('active'));

        const target = Array.from(buttons).find((b) => (b.getAttribute('data-category') || '').trim() === category);
        if (target) target.classList.add('active');
    };

    const getCategory = () => {
        const activeTab = document.querySelector('.tablinks.active');
        return activeTab ? (activeTab.textContent || '').trim() : 'Animation';
    };

    const setCategorySpecificFiltersVisibility = (category) => {
        const paysGroup = document.getElementById('pays-filter-group');
        const typeGroup = document.getElementById('type-filter-group');
        const episodesGroup = document.getElementById('episodes-filter-group');
        if (!typeGroup || !episodesGroup) return;

        if (category === 'Anime') {
            if (paysGroup) paysGroup.style.display = 'none';
            typeGroup.style.display = 'flex';
            episodesGroup.style.display = 'flex';
            return;
        }

        if (category === "Série d'Animation" || category === 'Série') {
            if (paysGroup) paysGroup.style.display = 'flex';
            typeGroup.style.display = 'none';
            episodesGroup.style.display = 'flex';
            return;
        }

        if (paysGroup) paysGroup.style.display = 'flex';
        typeGroup.style.display = 'none';
        episodesGroup.style.display = 'none';
    };

    const buildQuery = (page) => {
        const params = new URLSearchParams();
        params.set('categorie', getCategory());

        const recherche = (searchBar.value || '').trim();
        if (recherche !== '') params.set('recherche', recherche);

        const studio = (studioFilter.value || '').trim();
        if (studio !== '') params.set('studio', studio);

        const annee = (anneeFilter.value || '').trim();
        if (annee !== '') params.set('annee', annee);

        const note = (noteFilter.value || '').trim();
        if (note !== '') params.set('note', note);

        if (paysFilter) {
            const pays = (paysFilter.value || '').trim();
            if (pays !== '') params.set('pays', pays);
        }

        if (typeFilter) {
            const type = (typeFilter.value || '').trim();
            if (type !== '') params.set('type', type);
        }

        if (episodesFilter) {
            const episodes = (episodesFilter.value || '').trim();
            if (episodes !== '') params.set('episodes', episodes);
        }

        params.set('page', String(page));
        return params;
    };

    const updateUrl = (page) => {
        const params = buildQuery(page);
        const basePath = isMyList ? '/ma-liste' : '/liste';
        history.pushState(null, '', `${basePath}?${params.toString()}`);
    };

    const refreshFilters = async () => {
        const category = getCategory();
        try {
            const res = await fetch(`${apiBase}/filters?categorie=${encodeURIComponent(category)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;

            const studios = Array.isArray(data.studios) ? data.studios : [];
            const years = Array.isArray(data.years) ? data.years : [];
            const pays = Array.isArray(data.pays) ? data.pays : [];

            studioFilter.innerHTML = '<option value="">Tous les studios</option>';
            studios.forEach((s) => {
                const opt = document.createElement('option');
                opt.value = s;
                opt.textContent = s;
                studioFilter.appendChild(opt);
            });

            anneeFilter.innerHTML = '<option value="">Toutes les années</option>';
            years.forEach((y) => {
                const opt = document.createElement('option');
                opt.value = String(y);
                opt.textContent = String(y);
                anneeFilter.appendChild(opt);
            });

            if (paysFilter) {
                paysFilter.innerHTML = '<option value="">Tous les pays</option>';
                pays.forEach((p) => {
                    const opt = document.createElement('option');
                    opt.value = p;
                    opt.textContent = p;
                    paysFilter.appendChild(opt);
                });
            }
        } catch {
        }
    };

    const refreshStats = async () => {
        const category = getCategory();
        try {
            const res = await fetch(`${apiBase}/stats?categorie=${encodeURIComponent(category)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;

            const stats = data.stats || {};
            const total = typeof stats.total_films === 'number' ? stats.total_films : null;
            const topStudios = Array.isArray(stats.top_studios) ? stats.top_studios : [];
            const bestDecade = stats.best_decade ?? null;

            const totalEl = document.getElementById('stat-animation');
            const studiosEl = document.getElementById('stat-studios');
            const decadeEl = document.getElementById('stat-decade');

            if (totalEl) totalEl.textContent = total === null ? '-' : String(total);
            if (studiosEl) studiosEl.textContent = topStudios.length === 0 ? '-' : topStudios.map((s) => `${s.studio} (${s.total})`).join(', ');
            if (decadeEl) decadeEl.textContent = bestDecade === null ? '-' : `${bestDecade}s`;
        } catch {
        }
    };

    const refreshFilms = async (page) => {
        const params = buildQuery(page);
        const category = getCategory();

        setCategorySpecificFiltersVisibility(category);

        try {
            const res = await fetch(`${apiBase}/films?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;

            filmsContainer.innerHTML = data.html || '';
            paginationContainer.innerHTML = data.pagination_html || '';

            updateUrl(page);
        } catch {
        }
    };

    const attachPaginationEvents = () => {
        paginationContainer.addEventListener('click', (event) => {
            const link = event.target.closest('a[data-page]');
            if (!link) return;
            event.preventDefault();

            const page = link.getAttribute('data-page');
            if (!page || isNaN(page)) return;

            refreshFilms(parseInt(page, 10));
        });
    };

    const attachTabEvents = () => {
        if (!tabsContainer) return;

        tabsContainer.addEventListener('click', async (event) => {
            const btn = event.target.closest('button.tablinks[data-category]');
            if (!btn) return;
            const category = (btn.getAttribute('data-category') || '').trim();
            if (category === '') return;

            setActiveCategory(category);

            searchBar.value = '';
            studioFilter.value = '';
            anneeFilter.value = '';
            noteFilter.value = '';
            if (paysFilter) paysFilter.value = '';
            if (typeFilter) typeFilter.value = '';
            if (episodesFilter) episodesFilter.value = '';

            setCategorySpecificFiltersVisibility(category);
            await refreshFilters();
            await refreshStats();
            await refreshFilms(1);
        });
    };

    let debounceTimer = null;
    const scheduleRefresh = () => {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        debounceTimer = setTimeout(() => {
            refreshFilms(1);
        }, 300);
    };

    searchBtn.addEventListener('click', (e) => {
        e.preventDefault();
        refreshFilms(1);
    });

    resetBtn.addEventListener('click', () => {
        searchBar.value = '';
        studioFilter.value = '';
        anneeFilter.value = '';
        noteFilter.value = '';
        if (paysFilter) paysFilter.value = '';
        if (typeFilter) typeFilter.value = '';
        if (episodesFilter) episodesFilter.value = '';
        refreshFilms(1);
    });

    searchBar.addEventListener('input', scheduleRefresh);
    studioFilter.addEventListener('change', scheduleRefresh);
    anneeFilter.addEventListener('change', scheduleRefresh);
    noteFilter.addEventListener('change', scheduleRefresh);
    if (paysFilter) paysFilter.addEventListener('change', scheduleRefresh);
    if (typeFilter) typeFilter.addEventListener('change', scheduleRefresh);
    if (episodesFilter) episodesFilter.addEventListener('change', scheduleRefresh);

    attachPaginationEvents();
    attachTabEvents();

    const params = new URLSearchParams(window.location.search);
    const initialCategory = (params.get('categorie') || '').trim();
    if (initialCategory !== '') setActiveCategory(initialCategory);

    setCategorySpecificFiltersVisibility(getCategory());
    refreshFilters();
    refreshStats();
});
