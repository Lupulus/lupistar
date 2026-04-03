(() => {
  function getStatusEl() {
    let el = document.getElementById('tmdb-autofill-status');
    if (!el) {
      el = document.createElement('div');
      el.id = 'tmdb-autofill-status';
      el.className = 'tmdb-autofill-status';
    }
    return el;
  }

  function setStatus(state, message) {
    const el = getStatusEl();
    el.classList.remove('loading', 'success', 'error');
    if (state) el.classList.add(state);
    el.textContent = message || '';
    if (!el.parentElement) return;
    el.style.display = message ? 'block' : 'none';
  }

  function mapCategoryToType(cat, animeType) {
    if (cat === 'Série' || cat === "Série d'Animation") return 'tv';
    if (cat === 'Anime' && animeType === 'Série') return 'tv';
    return 'movie';
  }

  function selectByText(select, text) {
    if (!select || !text) return false;
    const norm = (s) => String(s).toLowerCase().trim();
    const target = norm(text);
    for (const opt of select.options) {
      if (norm(opt.textContent) === target) {
        select.value = opt.value;
        return true;
      }
    }
    return false;
  }

  function ensureOther(selectId, inputId, value) {
    const sel = document.getElementById(selectId);
    const inp = document.getElementById(inputId);
    if (!sel || !inp) return;
    sel.value = 'autre';
    inp.value = value || '';
    const event = new Event('change');
    sel.dispatchEvent(event);
    const groupId = selectId === 'studio' ? 'nouveau_studio_group'
                  : selectId === 'auteur' ? 'nouveau_auteur_group'
                  : null;
    if (groupId) {
      const grp = document.getElementById(groupId);
      if (grp) grp.style.display = '';
    }
    try {
      if (selectId === 'studio' && typeof window.toggleAutreStudio === 'function') {
        window.toggleAutreStudio();
      } else if (selectId === 'auteur' && typeof window.toggleAutreAuteur === 'function') {
        window.toggleAutreAuteur();
      }
    } catch (e) {}
  }

  function setSousGenres(names) {
    if (!Array.isArray(names) || names.length === 0) return;
    const norm = (s) => String(s)
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/\s+/g, ' ')
      .trim();

    const normalizeGenreKey = (s) => norm(s)
      .replace(/[^a-z0-9&]+/g, ' ')
      .replace(/\s*&\s*/g, ' & ')
      .replace(/\s+/g, ' ')
      .trim();

    const SIMPLE_MAP = {
      'action': 'Action',
      'adventure': 'Aventure',
      'aventure': 'Aventure',
      'comedie': 'Comédie',
      'comedy': 'Comédie',
      'drama': 'Drame',
      'drame': 'Drame',
      'fantasy': 'Fantastique',
      'fantastique': 'Fantastique',
      'sci fi': 'Science-fiction',
      'scifi': 'Science-fiction',
      'science fiction': 'Science-fiction',
      'science-fiction': 'Science-fiction',
      'western': 'Western',
      'mystery': 'Mystère',
      'mystere': 'Mystère',
      'thriller': 'Thriller',
      'romance': 'Romance',
      'horror': 'Horreur',
      'horreur': 'Horreur',
      'war': 'Guerre',
      'guerre': 'Guerre',
      'history': 'Historique',
      'historique': 'Historique',
      'crime': 'Policier',
      'policier': 'Policier',
      'superhero': 'Super-héros',
      'super heros': 'Super-héros',
      'super heros': 'Super-héros',
      'super-heros': 'Super-héros',
      'super-heros': 'Super-héros',
      'documentary': 'Documentaire',
      'documentaire': 'Documentaire',
      'sport': 'Sport',
      'slice of life': 'Slice of life',
      'mecha': 'Mecha',
      'cyberpunk': 'Cyberpunk',
      'conte': 'Conte',
      'animation musicale': 'Animation musicale',
      'animation fantastique': 'Animation fantastique',
      'familial': 'Familial',
      'animation familiale': 'Familial',
    };

    const COMBINED_MAP = {
      'action & adventure': ['Action', 'Aventure'],
      'action and adventure': ['Action', 'Aventure'],
      'action et aventure': ['Action', 'Aventure'],
      'science fiction & fantastique': ['Science-fiction', 'Fantastique'],
      'sci fi & fantasy': ['Science-fiction', 'Fantastique'],
      'sci fi and fantasy': ['Science-fiction', 'Fantastique'],
      'science fiction and fantasy': ['Science-fiction', 'Fantastique'],
      'science fiction et fantastique': ['Science-fiction', 'Fantastique'],
    };

    const expandGenres = (arr) => {
      const out = [];
      const push = (v) => {
        const s = String(v || '').trim();
        if (s !== '') out.push(s);
      };

      for (const g of arr) {
        const raw = String(g || '').trim();
        if (raw === '') continue;

        const combinedKey = normalizeGenreKey(raw).replace(/\s*&\s*/g, ' & ');
        const mapped = COMBINED_MAP[combinedKey];
        if (Array.isArray(mapped)) {
          mapped.forEach(push);
          continue;
        }

        if (combinedKey.includes(' & ') || combinedKey.includes(' and ') || combinedKey.includes(' et ') || combinedKey.includes('/')) {
          const parts = raw
            .replace(/\s*&\s*/g, ' & ')
            .replace(/\s+and\s+/gi, ' & ')
            .replace(/\s+et\s+/gi, ' & ')
            .split(/\s*&\s*|\/|,/g)
            .map((p) => p.trim())
            .filter(Boolean);

          if (parts.length > 1) {
            for (const p of parts) {
              const k = normalizeGenreKey(p).replace(/\s*&\s*/g, ' & ');
              const simple = SIMPLE_MAP[k];
              if (simple) push(simple);
              else push(p);
            }
            continue;
          }
        }

        const simpleKey = normalizeGenreKey(raw);
        const simple = SIMPLE_MAP[simpleKey];
        if (simple) push(simple);
        else push(raw);
      }

      return out;
    };

    const expanded = expandGenres(names);
    const set = new Set(expanded.map(norm));
    if (set.has('animation familiale')) set.add('familial');
    if (set.has('familial')) set.add('animation familiale');
    const ignore = new Set(['animation','film','serie',"serie d'animation",'anime'].map(norm));
    const labels = document.querySelectorAll('#sous-genres-container label.checkbox-label');
    labels.forEach(label => {
      const text = norm(label.textContent || '');
      const cb = label.querySelector('input[type="checkbox"]');
      if (cb && set.has(text) && !ignore.has(text)) {
        cb.checked = true;
      }
    });
  }

  function normalize(s) {
    return String(s || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^\w\s-]/g, '')
      .toLowerCase()
      .trim();
  }

  const ISO_TO_LOCAL = {
    'US': ['etats unis','etat unis','usa','etats-unis'],
    'GB': ['royaume uni','angleterre','uk','grande bretagne'],
    'FR': ['france'],
    'JP': ['japon'],
    'KR': ['coree du sud','coree'],
    'CN': ['chine'],
    'DE': ['allemagne'],
    'ES': ['espagne'],
    'IT': ['italie'],
    'CA': ['canada'],
    'AU': ['australie'],
    'IN': ['inde'],
    'RU': ['russie'],
    'BR': ['bresil'],
    'MX': ['mexique'],
  };

  const NAME_TO_LOCAL = {
    'united states of america': ISO_TO_LOCAL['US'],
    'united kingdom': ISO_TO_LOCAL['GB'],
    'france': ISO_TO_LOCAL['FR'],
    'japan': ISO_TO_LOCAL['JP'],
    'south korea': ISO_TO_LOCAL['KR'],
    'korea, republic of': ISO_TO_LOCAL['KR'],
    'china': ISO_TO_LOCAL['CN'],
    'germany': ISO_TO_LOCAL['DE'],
    'spain': ISO_TO_LOCAL['ES'],
    'italy': ISO_TO_LOCAL['IT'],
    'canada': ISO_TO_LOCAL['CA'],
    'australia': ISO_TO_LOCAL['AU'],
    'india': ISO_TO_LOCAL['IN'],
    'russia': ISO_TO_LOCAL['RU'],
    'brazil': ISO_TO_LOCAL['BR'],
    'mexico': ISO_TO_LOCAL['MX'],
  };

  function selectCountry(paysSelect, countries, countriesIso) {
    if (!paysSelect) return;
    const options = Array.from(paysSelect.options);
    const optionTexts = options.map(o => ({ opt: o, text: normalize(o.textContent || '') }));

    const tryMatchTexts = (texts) => {
      for (const t of texts || []) {
        const nt = normalize(t);
        const found = optionTexts.find(x => x.text.includes(nt) || x.text === nt);
        if (found) {
          paysSelect.value = found.opt.value;
          return true;
        }
      }
      return false;
    };

    if (Array.isArray(countriesIso) && countriesIso.length > 0) {
      for (const iso of countriesIso) {
        const list = ISO_TO_LOCAL[String(iso).toUpperCase()];
        if (list && tryMatchTexts(list)) return true;
      }
    }

    if (Array.isArray(countries) && countries.length > 0) {
      for (const name of countries) {
        const synonyms = NAME_TO_LOCAL[normalize(name)];
        if (synonyms && tryMatchTexts(synonyms)) return true;
        if (tryMatchTexts([name])) return true;
      }
    }

    return false;
  }
  async function fetchAutofill(query, type, year, seasonNumber) {
    const seasonPart = seasonNumber ? `&season_number=${encodeURIComponent(seasonNumber)}` : '';
    const url = (window.tmdbAutofillRoute || '/api/tmdb/autofill') +
      `?title=${encodeURIComponent(query)}&type=${encodeURIComponent(type)}${year ? `&year=${year}` : ''}${seasonPart}`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    return await res.json();
  }

  async function runAutofill(context) {
    const nom = document.getElementById('nom_film')?.value || '';
    const cat = document.getElementById('categorie')?.value || '';
    const animeType = document.getElementById('anime_type')?.value || '';
    const saisonDetaillee = !!document.getElementById('saison_detaillee')?.checked;
    const numSaison = String(document.getElementById('num_saison')?.value || '').trim();
    const yearInput = document.getElementById('date_sortie');
    if (!nom || !cat || !String(yearInput?.value || '').trim()) {
      setStatus('error', 'Veuillez remplir les champs ci-dessus pour tenter le remplissage automatique.');
      return;
    }
    if (cat === 'Anime' && !String(animeType || '').trim()) {
      setStatus('error', 'Veuillez sélectionner le type d\'anime avant le remplissage automatique.');
      return;
    }
    const isSerie = cat === 'Série' || cat === "Série d'Animation" || (cat === 'Anime' && animeType === 'Série');
    if (isSerie && saisonDetaillee && !numSaison) {
      setStatus('error', 'Veuillez renseigner le numéro de la saison avant le remplissage automatique.');
      return;
    }

    const type = mapCategoryToType(cat, animeType);
    const year = Number(yearInput?.value || 0) || 0;
    const seasonNumber = type === 'tv' && isSerie && saisonDetaillee ? Number(numSaison || 0) || 0 : 0;

    setStatus('loading', 'Recherche TMDb en cours…');
    let out;
    try {
      out = await fetchAutofill(nom, type, year, seasonNumber);
    } catch (e) {
      setStatus('error', 'Impossible de contacter TMDb.');
      return;
    }
    if (!out?.success || !out?.data) {
      setStatus('error', out?.error || 'Aucun résultat TMDb.');
      return;
    }
    const d = out.data || {};

    if (d.title && document.getElementById('nom_film')) {
      document.getElementById('nom_film').value = d.title;
    }
    if (d.year && document.getElementById('date_sortie')) {
      document.getElementById('date_sortie').value = d.year;
    }

    const desc = document.getElementById('description');
    if (desc && d.overview) {
      const truncated = String(d.overview).slice(0, 400);
      const current = String(desc.value || '').trim();
      if (!current) {
        desc.value = truncated;
        desc.dataset.source = 'tmdb';
        if (typeof window.updateCharCount === 'function') {
          window.updateCharCount();
        }
      } else if (desc.dataset && desc.dataset.source === 'tmdb') {
        desc.value = truncated;
        if (typeof window.updateCharCount === 'function') {
          window.updateCharCount();
        }
      }
    }

    const pays = document.getElementById('pays');
    const countries = Array.isArray(d.countries) ? d.countries : [];
    const countriesIso = Array.isArray(d.countries_iso) ? d.countries_iso : [];
    selectCountry(pays, countries, countriesIso);

    if (isSerie) {
      const saisonInput = document.getElementById('saison');
      const nbrEpisodeInput = document.getElementById('nbrEpisode');

      if (saisonDetaillee) {
        const seasonEp = d.season_episode_count;
        if (seasonEp && nbrEpisodeInput && !String(nbrEpisodeInput.value || '').trim()) {
          nbrEpisodeInput.value = seasonEp;
        }
      } else {
        if (d.number_of_seasons && saisonInput && !String(saisonInput.value || '').trim()) {
          saisonInput.value = d.number_of_seasons;
        }
        if (d.number_of_episodes && nbrEpisodeInput && !String(nbrEpisodeInput.value || '').trim()) {
          nbrEpisodeInput.value = d.number_of_episodes;
        }
      }
    }

    if (d.poster_url) {
      const imgUrlInput = document.getElementById('image_url');
      if (imgUrlInput) imgUrlInput.value = d.poster_url;
    }

    if (d.studio) {
      if (!selectByText(document.getElementById('studio'), d.studio)) {
        ensureOther('studio', 'nouveau_studio', d.studio);
      }
    }

    if (d.auteur) {
      if (!selectByText(document.getElementById('auteur'), d.auteur)) {
        ensureOther('auteur', 'nouveau_auteur', d.auteur);
      }
    }

    setSousGenres(d.genres || []);
    setStatus('success', 'Champs remplis automatiquement (TMDb).');
    setTimeout(() => setStatus('', ''), 4000);
  }

  function injectButton(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    if (form.querySelector('.tmdb-autofill-row2') || form.querySelector('.tmdb-autofill-row3')) return;

    const titleInput = form.querySelector('#nom_film');
    const categorieSelect = form.querySelector('#categorie');
    const dateInput = form.querySelector('#date_sortie');
    if (!titleInput || !categorieSelect || !dateInput) return;

    const dateGroup = dateInput.closest('.form-group');
    if (!dateGroup) return;

    const anchor = document.getElementById('season-detail-section') || dateGroup;

    const row2 = document.createElement('div');
    row2.className = 'tmdb-autofill-row2';

    const row3 = document.createElement('div');
    row3.className = 'tmdb-autofill-row3';

    const prereq = document.createElement('div');
    prereq.className = 'tmdb-autofill-prereq';
    prereq.textContent = 'Veuillez remplir les champs ci-dessus pour tenter le remplissage automatique.';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = 'Remplissage auto';
    btn.className = 'btn-autofill';
    const status = getStatusEl();
    status.style.display = 'none';

    const animeTypeSelect = form.querySelector('#anime_type');
    const saisonDetaillee = form.querySelector('#saison_detaillee');
    const numSaison = form.querySelector('#num_saison');

    const updatePrereqs = () => {
      const baseOk =
        String(titleInput.value || '').trim().length > 0 &&
        String(categorieSelect.value || '').trim().length > 0 &&
        String(dateInput.value || '').trim().length > 0;

      const cat = String(categorieSelect.value || '').trim();
      const animeType = String(animeTypeSelect?.value || '').trim();
      const needAnimeType = cat === 'Anime';
      const animeOk = !needAnimeType || animeType.length > 0;

      const detailChecked = !!saisonDetaillee?.checked;
      const isSerie = cat === 'Série' || cat === "Série d'Animation" || (cat === 'Anime' && animeType === 'Série');
      const needSeasonNum = isSerie && detailChecked;
      const seasonOk = !needSeasonNum || String(numSaison?.value || '').trim().length > 0;

      const ok = baseOk && animeOk && seasonOk;
      btn.disabled = !ok;
      prereq.style.display = ok ? 'none' : 'block';
      if (!ok) {
        setStatus('', '');
      }
    };

    btn.addEventListener('click', async () => {
      btn.disabled = true;
      try {
        await runAutofill(formId);
      } finally {
        updatePrereqs();
      }
    });
    row2.appendChild(btn);
    row3.appendChild(prereq);
    row3.appendChild(status);

    anchor.insertAdjacentElement('afterend', row2);
    row2.insertAdjacentElement('afterend', row3);

    titleInput.addEventListener('input', updatePrereqs);
    categorieSelect.addEventListener('change', updatePrereqs);
    dateInput.addEventListener('input', updatePrereqs);
    dateInput.addEventListener('change', updatePrereqs);
    if (animeTypeSelect) animeTypeSelect.addEventListener('change', updatePrereqs);
    if (saisonDetaillee) saisonDetaillee.addEventListener('change', updatePrereqs);
    if (numSaison) {
      numSaison.addEventListener('input', updatePrereqs);
      numSaison.addEventListener('change', updatePrereqs);
    }
    updatePrereqs();
  }

  document.addEventListener('DOMContentLoaded', () => {
    window.tmdbAutofillRoute = window.tmdbAutofillRoute || '/api/tmdb/autofill';
    if (document.getElementById('filmForm')) {
      injectButton('filmForm');
    }
  });
})();
