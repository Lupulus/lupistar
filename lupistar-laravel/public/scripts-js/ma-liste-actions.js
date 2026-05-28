document.addEventListener('DOMContentLoaded', () => {
    const searchBar = document.getElementById('search-bar');
    const studioFilter = document.getElementById('studio-filter');
    const anneeFilter = document.getElementById('annee-filter');
    const noteFilter = document.getElementById('note-filter');
    const statutFilter = document.getElementById('statut-filter');
    const paysFilter = document.getElementById('pays-filter');
    const typeFilter = document.getElementById('type-filter');
    const episodesFilter = document.getElementById('episodes-filter');
    const searchBtn = document.querySelector('.search-btn');
    const resetBtn = document.querySelector('.reset-btn');
    const filmsContainer = document.getElementById('films-container');
    const paginationContainer = document.getElementById('pagination-container');
    const resultsFeedback = document.getElementById('results-feedback');
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

        if (statutFilter) {
            const statut = (statutFilter.value || '').trim();
            if (statut !== '') params.set('statut', statut);
        }

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
            const studiosCounts = Array.isArray(data.studios_counts) ? data.studios_counts : null;
            const yearsCounts = Array.isArray(data.years_counts) ? data.years_counts : null;
            const paysCounts = Array.isArray(data.pays_counts) ? data.pays_counts : null;
            const noteCounts = data.note_counts && typeof data.note_counts === 'object' ? data.note_counts : null;
            const totalFilms = noteCounts && typeof noteCounts.total === 'number' ? noteCounts.total : null;

            {
                const current = (studioFilter.value || '').trim();
                const header = totalFilms !== null ? `Tous les studios (${totalFilms})` : 'Tous les studios';
                studioFilter.innerHTML = `<option value="">${header}</option>`;
                if (studiosCounts) {
                    studiosCounts.forEach((row) => {
                        const label = String(row?.label || '').trim();
                        const count = Number(row?.total ?? 0) || 0;
                        if (!label) return;
                        const opt = document.createElement('option');
                        opt.value = label;
                        opt.textContent = `${label} (${count})`;
                        studioFilter.appendChild(opt);
                    });
                } else {
                    studios.forEach((s) => {
                        const opt = document.createElement('option');
                        opt.value = s;
                        opt.textContent = s;
                        studioFilter.appendChild(opt);
                    });
                }
                if (current !== '') studioFilter.value = current;
            }

            {
                const current = (anneeFilter.value || '').trim();
                const header = totalFilms !== null ? `Toutes les années (${totalFilms})` : 'Toutes les années';
                anneeFilter.innerHTML = `<option value="">${header}</option>`;
                if (yearsCounts) {
                    yearsCounts.forEach((row) => {
                        const label = String(row?.label || '').trim();
                        const count = Number(row?.total ?? 0) || 0;
                        if (!label) return;
                        const opt = document.createElement('option');
                        opt.value = label;
                        opt.textContent = `${label} (${count})`;
                        anneeFilter.appendChild(opt);
                    });
                } else {
                    years.forEach((y) => {
                        const opt = document.createElement('option');
                        opt.value = String(y);
                        opt.textContent = String(y);
                        anneeFilter.appendChild(opt);
                    });
                }
                if (current !== '') anneeFilter.value = current;
            }

            if (paysFilter) {
                const current = (paysFilter.value || '').trim();
                const header = totalFilms !== null ? `Tous les pays (${totalFilms})` : 'Tous les pays';
                paysFilter.innerHTML = `<option value="">${header}</option>`;
                if (paysCounts) {
                    paysCounts.forEach((row) => {
                        const label = String(row?.label || '').trim();
                        const count = Number(row?.total ?? 0) || 0;
                        if (!label) return;
                        const opt = document.createElement('option');
                        opt.value = label;
                        opt.textContent = `${label} (${count})`;
                        paysFilter.appendChild(opt);
                    });
                } else {
                    pays.forEach((p) => {
                        const opt = document.createElement('option');
                        opt.value = p;
                        opt.textContent = p;
                        paysFilter.appendChild(opt);
                    });
                }
                if (current !== '') paysFilter.value = current;
            }

            if (noteFilter) {
                const current = (noteFilter.value || '').trim();
                const parts = [];

                const allLabel = totalFilms !== null ? `Toutes les notes (${totalFilms})` : 'Toutes les notes';
                parts.push(`<option value="">${allLabel}</option>`);

                if (noteCounts) {
                    const sans = Number(noteCounts.sans_note ?? 0) || 0;
                    parts.push(`<option value="sans_note">Sans note (${sans})</option>`);

                    for (let i = 0; i <= 9; i++) {
                        const min = i;
                        const max = i + 1;
                        const key = `${min}-${max}`;
                        const c = Number(noteCounts?.ranges?.[key] ?? 0) || 0;
                        const label = `Entre ${min} et ${max}`;
                        parts.push(`<option value="${key}">${label} (${c})</option>`);
                    }

                    const superstar = Number(noteCounts.superstar ?? 0) || 0;
                    parts.push(`<option value="10">SuperStar (10) (${superstar})</option>`);
                } else {
                    parts.push('<option value="sans_note">Sans note</option>');
                    for (let i = 0; i <= 9; i++) {
                        const min = i;
                        const max = i + 1;
                        const key = `${min}-${max}`;
                        const label = `Entre ${min} et ${max}`;
                        parts.push(`<option value="${key}">${label}</option>`);
                    }
                    parts.push('<option value="10">SuperStar (10)</option>');
                }

                noteFilter.innerHTML = parts.join('');
                if (current !== '') noteFilter.value = current;
            }

            if (statutFilter) {
                const current = (statutFilter.value || '').trim();
                const sc = data.statut_counts && typeof data.statut_counts === 'object' ? data.statut_counts : null;
                const total = sc && typeof sc.total === 'number' ? sc.total : totalFilms;
                const inCount = sc && typeof sc.in === 'number' ? sc.in : 0;
                const outCount = sc && typeof sc.out === 'number' ? sc.out : 0;
                const loggedIn = !!data.logged_in;

                const header = typeof total === 'number' ? `Tous les films (${total})` : 'Tous les films';
                statutFilter.innerHTML = `<option value="">${header}</option>`;

                const optIn = document.createElement('option');
                optIn.value = 'in';
                optIn.textContent = loggedIn ? `Dans ma liste (${inCount})` : 'Dans ma liste';
                optIn.disabled = !loggedIn;
                statutFilter.appendChild(optIn);

                const optOut = document.createElement('option');
                optOut.value = 'out';
                optOut.textContent = loggedIn ? `Hors ma liste (${outCount})` : 'Hors ma liste';
                optOut.disabled = !loggedIn;
                statutFilter.appendChild(optOut);

                if (current !== '') statutFilter.value = current;
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

    const scrollToFilmsTop = () => {
        const anchor = document.getElementById('tabcontent') || filmsContainer;
        if (!anchor) return;

        const header = document.querySelector('header');
        const headerOffset = header ? Math.ceil(header.getBoundingClientRect().height) : 0;
        const rect = anchor.getBoundingClientRect();
        const top = window.scrollY + rect.top - headerOffset - 12;
        window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
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

            const p = data.pagination || {};
            const currentPage = typeof p.current_page === 'number' ? p.current_page : page;

            if (resultsFeedback) {
                const total = typeof p.total === 'number' ? p.total : null;
                const lastPage = typeof p.last_page === 'number' ? p.last_page : null;

                if (total === null) {
                    resultsFeedback.textContent = '';
                } else if (total === 0) {
                    resultsFeedback.textContent = 'Aucun résultat pour ces filtres.';
                } else {
                    const pageInfo = lastPage ? ` • Page ${currentPage}/${lastPage}` : '';
                    resultsFeedback.textContent = `${total} résultat(s) trouvé(s)${pageInfo}`;
                }
            }

            updateUrl(currentPage);
            return true;
        } catch {
            return false;
        }
    };

    const attachPaginationEvents = () => {
        paginationContainer.addEventListener('click', async (event) => {
            const link = event.target.closest('a[data-page]');
            if (link) {
                event.preventDefault();

                const page = link.getAttribute('data-page');
                if (!page || isNaN(page)) return;

                const ok = await refreshFilms(parseInt(page, 10));
                if (ok) scrollToFilmsTop();
                return;
            }

            const goBtn = event.target.closest('button.pagination-go-btn');
            if (!goBtn) return;

            const paginationRoot = paginationContainer.querySelector('.pagination');
            const input = paginationRoot?.querySelector('input.pagination-go-input');
            const raw = (input?.value || '').trim();
            const wanted = parseInt(raw, 10);
            if (!raw || isNaN(wanted)) return;

            const last = paginationRoot?.getAttribute('data-last-page');
            const lastPage = last && !isNaN(last) ? parseInt(last, 10) : null;
            const clamped = lastPage ? Math.max(1, Math.min(wanted, lastPage)) : Math.max(1, wanted);
            const ok = await refreshFilms(clamped);
            if (ok) scrollToFilmsTop();
        });

        paginationContainer.addEventListener('keydown', async (event) => {
            const input = event.target.closest('input.pagination-go-input');
            if (!input) return;
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const raw = (input.value || '').trim();
            const wanted = parseInt(raw, 10);
            if (!raw || isNaN(wanted)) return;

            const paginationRoot = input.closest('.pagination');
            const last = paginationRoot?.getAttribute('data-last-page');
            const lastPage = last && !isNaN(last) ? parseInt(last, 10) : null;
            const clamped = lastPage ? Math.max(1, Math.min(wanted, lastPage)) : Math.max(1, wanted);
            const ok = await refreshFilms(clamped);
            if (ok) scrollToFilmsTop();
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
            if (statutFilter) statutFilter.value = '';
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
        if (statutFilter) statutFilter.value = '';
        if (paysFilter) paysFilter.value = '';
        if (typeFilter) typeFilter.value = '';
        if (episodesFilter) episodesFilter.value = '';
        refreshFilms(1);
    });

    searchBar.addEventListener('input', scheduleRefresh);
    studioFilter.addEventListener('change', scheduleRefresh);
    anneeFilter.addEventListener('change', scheduleRefresh);
    noteFilter.addEventListener('change', scheduleRefresh);
    if (statutFilter) statutFilter.addEventListener('change', scheduleRefresh);
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
