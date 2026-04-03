@extends('layouts.site')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/style-proposer.css') }}">
@endsection

@section('content')
    @php
        $categories = ['Film', 'Animation', 'Anime', 'Série', "Série d'Animation"];
        $sousGenresKeys = array_keys($sousGenres);
        $totalSousGenres = count($sousGenresKeys);
        $colonnes = 6;
        $lignes = (int) ceil($totalSousGenres / $colonnes);
        $oldSousGenres = old('sous_genres', []);
        if (! is_array($oldSousGenres)) {
            $oldSousGenres = [];
        }
    @endphp

    <main>
        <h2>Proposer un film</h2>
        <div class="container">
            <p class="info-text">Proposez un film à ajouter à la base de données. Votre proposition sera examinée par les administrateurs avant publication.</p>

            <form id="filmForm" action="{{ route('proposer-film.store') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="form-section three-columns">
                    <div class="form-group">
                        <label id="nom_film_label" for="nom_film">Nom du film :</label>
                        <input type="text" id="nom_film" name="nom_film" placeholder="Nom du film (max 75 caractères)" maxlength="75" required value="{{ old('nom_film') }}">
                        @error('nom_film')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                    </div>
                    <div class="form-group">
                        <label id="categorie_label" for="categorie">Catégorie :</label>
                        <select id="categorie" name="categorie" required>
                            <option value="">Sélectionnez une catégorie</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" @selected(old('categorie') === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                        @error('categorie')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                    </div>
                    <div class="form-group">
                        <label id="date_sortie_label" for="date_sortie">Année de sortie :</label>
                        <input type="number" id="date_sortie" name="date_sortie" min="1900" max="2099" step="1" value="{{ old('date_sortie', date('Y')) }}" required>
                        @error('date_sortie')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                    </div>
                </div>

                <div id="season-detail-section" class="form-section three-columns" style="display:none;">
                    <div class="form-group" id="anime_type_group">
                        <label for="anime_type">Type d'Anime :</label>
                        <select id="anime_type" name="anime_type" onchange="handleAnimeTypeChange()">
                            <option value="">Sélectionnez le type</option>
                            <option value="Film" @selected(old('anime_type') === 'Film')>Film</option>
                            <option value="Série" @selected(old('anime_type') === 'Série')>Série</option>
                        </select>
                        @error('anime_type')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                    </div>

                    <div class="form-group inline" id="saison_detaillee_group" style="display:none;">
                        <label for="saison_detaillee">Saison détaillés</label>
                        <input type="checkbox" id="saison_detaillee" name="saison_detaillee" value="1" @checked(old('saison_detaillee'))>
                        @error('saison_detaillee')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                    </div>

                    <div class="form-group" id="num_saison_group" style="display:none;">
                        <label for="num_saison">Numéro de la saison :</label>
                        <input type="number" id="num_saison" name="num_saison" min="1" max="100" placeholder="1" value="{{ old('num_saison') }}">
                        @error('num_saison')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                    </div>
                </div>

                <div class="form-section proposer-description-layout">
                    <div class="form-group">
                        <label id="description_label" for="description">Description :</label>
                        <textarea id="description" name="description" rows="4" cols="50" placeholder="Pas de description" maxlength="400" oninput="updateCharCount()">{{ old('description') }}</textarea>
                        <span id="charCount" class="description-compteur">0 / 400</span>
                        @error('description')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                    </div>
                    <div class="stacked-fields">
                        <div class="form-group">
                            <label id="pays_label" for="pays">Pays :</label>
                            <select id="pays" name="pays_id" required onchange="handlePaysChange()">
                                <option value="">Sélectionnez un pays</option>
                                @foreach($pays as $id => $nom)
                                    <option value="{{ $id }}" @selected((string) old('pays_id') === (string) $id)>{{ $nom }}</option>
                                @endforeach
                            </select>
                            <div id="japan-notification" class="japan-notification" style="display: none;">
                                <span class="notification-icon">ℹ️</span>
                                <span class="notification-text">Les films et séries d'animation japonaises appartiennent à la catégorie "Anime".</span>
                            </div>
                            @error('pays_id')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                        </div>
                        <div class="form-group">
                            <label id="image_label" for="image">Image du film :</label>
                            <input type="file" id="image" name="image" accept="image/*">
                            @error('image')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                            <div class="or-separator">ou</div>
                            <input type="url" id="image_url" class="image-url-input" name="image_url" placeholder="Lien de l'image (http...)" value="{{ old('image_url') }}">
                            @error('image_url')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                        </div>
                        <div class="form-group">
                            <label id="ordre_suite_label" for="ordre_suite">Ordre (Suite?) :</label>
                            <input type="number" id="ordre_suite" name="ordre_suite" min="1" max="25" step="1" placeholder="1" value="{{ old('ordre_suite') }}">
                            @error('ordre_suite')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-section two-columns">
                    <div class="form-group">
                        <label id="saison_label" for="saison" style="display:none;">Numéro de saison :</label>
                        <input type="number" id="saison" name="saison" min="1" max="100" placeholder="1" style="display:none;" value="{{ old('saison') }}">
                        @error('saison')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                    </div>
                    <div class="form-group">
                        <label id="nbrEpisode_label" for="nbrEpisode" style="display:none;">Nombre d'épisodes :</label>
                        <input type="number" id="nbrEpisode" name="nbrEpisode" min="1" max="9999" placeholder="10" style="display:none;" value="{{ old('nbrEpisode') }}">
                        @error('nbrEpisode')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                    </div>
                </div>

                <div class="form-section two-columns">
                    <div class="form-group">
                        <label id="studio_label" for="studio">Studio :</label>
                        <select id="studio" name="studio_id" required onchange="toggleAutreStudio()">
                            <option value="">Sélectionnez un studio</option>
                            <option value="autre" @selected(old('studio_id') === 'autre')>Autre</option>
                            <option value="1" @selected((string) old('studio_id') === '1')>Inconnu</option>
                        </select>
                        @error('studio_id')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                    </div>
                    <div class="form-group">
                        <label id="auteur_label" for="auteur">Auteur :</label>
                        <select id="auteur" name="auteur_id" required>
                            <option value="">Sélectionnez un auteur</option>
                            <option value="autre" @selected(old('auteur_id') === 'autre')>Autre</option>
                            <option value="1" @selected((string) old('auteur_id') === '1')>Inconnu</option>
                            @foreach($auteurs as $id => $nom)
                                @continue($nom === 'Inconnu')
                                <option value="{{ $id }}" @selected((string) old('auteur_id') === (string) $id)>{{ $nom }}</option>
                            @endforeach
                        </select>
                        @error('auteur_id')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                    </div>
                </div>

                <div class="form-section two-columns other-inputs-row">
                    <div class="form-group" id="nouveau_studio_group" style="display:none;">
                        <label for="nouveau_studio">Studio (autre) :</label>
                        <input type="text" id="nouveau_studio" name="nouveau_studio" placeholder="Nom du studio" maxlength="30" value="{{ old('nouveau_studio') }}">
                        @error('nouveau_studio')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                    </div>
                    <div class="form-group" id="nouveau_auteur_group" style="display:none;">
                        <label for="nouveau_auteur">Auteur (autre) :</label>
                        <input type="text" id="nouveau_auteur" name="nouveau_auteur" placeholder="Nom de l'auteur" maxlength="30" value="{{ old('nouveau_auteur') }}">
                        @error('nouveau_auteur')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                    </div>
                </div>

                <div class="form-section full-width">
                    <div class="form-group">
                        <label id="sous-genres_label">Sous-genres :</label>
                        <div id="sous-genres-container">
                            <table>
                                <tbody>
                                    @for($i = 0; $i < $lignes; $i++)
                                        <tr>
                                            @for($j = 0; $j < $colonnes; $j++)
                                                @php $index = ($i * $colonnes) + $j; @endphp
                                                @if($index < $totalSousGenres)
                                                    @php
                                                        $id = $sousGenresKeys[$index];
                                                        $nom = $sousGenres[$id];
                                                        $checked = in_array((string) $id, array_map('strval', $oldSousGenres), true);
                                                    @endphp
                                                    <td>
                                                        <label class="checkbox-label">
                                                            <input type="checkbox" name="sous_genres[]" value="{{ $id }}" @checked($checked)> {{ $nom }}
                                                        </label>
                                                    </td>
                                                @else
                                                    <td></td>
                                                @endif
                                            @endfor
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                        <p style="color: red; display: none;" id="sous-genre-warning">⚠️ Sélectionnez au moins un sous-genre.</p>
                        @error('sous_genres')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
                    </div>
                </div>

                <input id="Bouton-proposer" type="submit" value="Proposer le film">
            </form>

            <div id="notification">
                @if(session('status'))
                    <div id="message-container" class="success"><p>{{ session('status') }}</p></div>
                @endif
            </div>
        </div>
    </main>
@endsection

@section('scripts')
    <script src="{{ asset('scripts-js/tmdb-autofill.js') }}" defer></script>
    <script>
        const proposerRoutes = {
            studios: @json(route('proposer-film.studios')),
            auteurs: @json(route('proposer-film.auteurs')),
            tmdbAutofill: @json(route('api.tmdb.autofill')),
        };
        window.tmdbAutofillRoute = proposerRoutes.tmdbAutofill;
        let lastSaisonDetaillee = null;

        document.addEventListener('DOMContentLoaded', function () {
            updateCharCount();

            document.getElementById('auteur').addEventListener('change', function () {
                toggleAutreAuteur();
            });

            document.getElementById('categorie').addEventListener('change', function () {
                handleCategoryChange();
                updateNomFilmLabel();
                updateStudios();
                updateAuteurs();
            });

            const saisonDetaillee = document.getElementById('saison_detaillee');
            if (saisonDetaillee) {
                saisonDetaillee.addEventListener('change', function () {
                    handleCategoryChange();
                });
            }

            const numSaison = document.getElementById('num_saison');
            if (numSaison) {
                numSaison.addEventListener('input', function () {
                    handleCategoryChange();
                });
                numSaison.addEventListener('change', function () {
                    handleCategoryChange();
                });
            }

            const form = document.getElementById('filmForm');
            form.addEventListener('submit', function (e) {
                const sousGenresCoches = document.querySelectorAll('input[name="sous_genres[]"]:checked');
                if (sousGenresCoches.length === 0) {
                    e.preventDefault();
                    document.getElementById("sous-genre-warning").style.display = "block";
                    document.getElementById("sous-genre-warning").textContent = "⚠️ Vous devez sélectionner au moins un sous-genre.";
                } else {
                    document.getElementById("sous-genre-warning").style.display = "none";
                }
            });

            handleCategoryChange();
            updateNomFilmLabel();
            toggleAutreStudio();
            toggleAutreAuteur();
            handlePaysChange();
            updateStudios();
            updateAuteurs();
            @if(session('status'))
                (function () {
                    const f = document.getElementById('filmForm');
                    if (f) f.reset();
                    updateCharCount();
                    handleCategoryChange();
                    updateNomFilmLabel();
                    toggleAutreStudio();
                    toggleAutreAuteur();
                    handlePaysChange();
                    updateStudios();
                    updateAuteurs();
                })();
            @endif
        });

        function updateCharCount() {
            const desc = document.getElementById("description");
            const counter = document.getElementById("charCount");
            if (!desc || !counter) return;

            if (desc.value.length > 400) {
                desc.value = desc.value.slice(0, 400);
            }
            counter.textContent = `${desc.value.length} / 400`;
            counter.style.color = desc.value.length >= 390 ? "red" : "";
        }

        function handlePaysChange() {
            const paysSelect = document.getElementById('pays');
            const categorieSelect = document.getElementById('categorie');
            const japanNotification = document.getElementById('japan-notification');
            if (!paysSelect || !categorieSelect || !japanNotification) return;

            const selectedOption = paysSelect.options[paysSelect.selectedIndex];
            const selectedText = selectedOption ? selectedOption.text : '';
            const isJapanSelected = paysSelect.value === '2' || selectedText.includes('Japon');
            if (isJapanSelected) {
                const currentCategory = categorieSelect.value;
                if (currentCategory === 'Animation' || currentCategory === "Série d'Animation") {
                    japanNotification.style.display = 'block';
                    categorieSelect.value = 'Anime';
                    updateStudios();
                    updateAuteurs();
                }
            } else {
                japanNotification.style.display = 'none';
            }
        }

        function handleAnimeTypeChange() {
            handleCategoryChange();
            updateNomFilmLabel();
            updateStudios();
            updateAuteurs();
        }

        function handleCategoryChange() {
            const categorieSelect = document.getElementById('categorie');
            const seasonDetailSection = document.getElementById('season-detail-section');
            const animeTypeGroup = document.getElementById('anime_type_group');
            const animeTypeSelect = document.getElementById('anime_type');
            const saisonDetailleeGroup = document.getElementById('saison_detaillee_group');
            const saisonDetailleeCheckbox = document.getElementById('saison_detaillee');
            const numSaisonGroup = document.getElementById('num_saison_group');
            const numSaisonInput = document.getElementById('num_saison');
            const ordreSuiteLabel = document.getElementById("ordre_suite_label");
            const ordreSuiteInput = document.getElementById("ordre_suite");
            const saisonLabel = document.getElementById("saison_label");
            const saisonInput = document.getElementById("saison");
            const nbrEpisodeLabel = document.getElementById("nbrEpisode_label");
            const nbrEpisodeInput = document.getElementById("nbrEpisode");

            const categorie = categorieSelect.value;
            const isAnime = categorie === 'Anime';
            const isSerieCategorie = categorie === 'Série' || categorie === "Série d'Animation";
            const animeType = animeTypeSelect ? animeTypeSelect.value : '';
            const isAnimeSerie = isAnime && animeType === 'Série';
            const isSerie = isSerieCategorie || isAnimeSerie;

            if (seasonDetailSection) {
                seasonDetailSection.style.display = (isAnime || isSerieCategorie) ? 'grid' : 'none';
            }

            if (animeTypeGroup && animeTypeSelect) {
                if (isAnime) {
                    animeTypeGroup.style.display = 'flex';
                    animeTypeSelect.required = true;
                } else {
                    animeTypeGroup.style.display = 'none';
                    animeTypeSelect.required = false;
                    animeTypeSelect.value = '';
                }
            }

            if (saisonDetailleeGroup && saisonDetailleeCheckbox) {
                saisonDetailleeGroup.style.display = isSerie ? 'grid' : 'none';
                if (!isSerie) {
                    saisonDetailleeCheckbox.checked = false;
                }
            }

            const isSaisonDetaillee = !!(saisonDetailleeCheckbox && saisonDetailleeCheckbox.checked);
            if (numSaisonGroup && numSaisonInput) {
                if (isSerie && isSaisonDetaillee) {
                    numSaisonGroup.style.display = 'flex';
                    numSaisonInput.required = true;
                } else {
                    numSaisonGroup.style.display = 'none';
                    numSaisonInput.required = false;
                    numSaisonInput.value = '';
                }
            }

            if (ordreSuiteLabel && ordreSuiteInput && saisonLabel && saisonInput && nbrEpisodeLabel && nbrEpisodeInput) {
                if (isSerie) {
                    ordreSuiteLabel.style.display = "none";
                    ordreSuiteInput.style.display = "none";
                    saisonInput.required = false;
                    nbrEpisodeLabel.style.display = "block";
                    nbrEpisodeInput.style.display = "block";
                    nbrEpisodeInput.required = true;

                    if (isSaisonDetaillee) {
                        saisonLabel.style.display = "none";
                        saisonInput.style.display = "none";
                        saisonInput.value = numSaisonInput ? String(numSaisonInput.value || '').trim() : '';
                    } else {
                        if (lastSaisonDetaillee === true) {
                            saisonInput.value = '';
                        }
                        saisonLabel.style.display = "block";
                        saisonInput.style.display = "block";
                        const sl = document.getElementById("saison_label");
                        if (sl) sl.textContent = "Nombre de saison :";
                    }
                } else {
                    ordreSuiteLabel.style.display = "block";
                    ordreSuiteInput.style.display = "block";
                    saisonLabel.style.display = "none";
                    saisonInput.style.display = "none";
                    saisonInput.required = false;
                    saisonInput.value = '';
                    const sl = document.getElementById("saison_label");
                    if (sl) sl.textContent = "Numéro de saison :";
                    nbrEpisodeLabel.style.display = "none";
                    nbrEpisodeInput.style.display = "none";
                    nbrEpisodeInput.required = false;
                    nbrEpisodeInput.value = '';
                }
            }

            lastSaisonDetaillee = isSerie ? isSaisonDetaillee : null;
        }

        function updateNomFilmLabel() {
            const categorie = document.getElementById("categorie").value;
            const label = document.getElementById("nom_film_label");
            const input = document.getElementById("nom_film");
            if (!label || !input) return;

            const animeType = document.getElementById("anime_type")?.value || '';
            if (categorie === "Série" || categorie === "Série d'Animation" || (categorie === "Anime" && animeType === "Série")) {
                label.textContent = "Nom de la série :";
                input.placeholder = "Nom de la série (max 75 caractères)";
            } else {
                label.textContent = "Nom du film :";
                input.placeholder = "Nom du film (max 75 caractères)";
            }
        }

        function toggleAutreStudio() {
            const studioSelect = document.getElementById("studio");
            const autreStudioInput = document.getElementById("nouveau_studio");
            const group = document.getElementById("nouveau_studio_group");
            if (!studioSelect || !autreStudioInput || !group) return;

            if (studioSelect.value === "autre") {
                group.style.display = "flex";
                autreStudioInput.setAttribute("required", "required");
            } else {
                group.style.display = "none";
                autreStudioInput.removeAttribute("required");
            }
        }

        function toggleAutreAuteur() {
            const auteurSelect = document.getElementById("auteur");
            const autreAuteurInput = document.getElementById("nouveau_auteur");
            const group = document.getElementById("nouveau_auteur_group");
            if (!auteurSelect || !autreAuteurInput || !group) return;

            if (auteurSelect.value === "autre") {
                group.style.display = "flex";
                autreAuteurInput.setAttribute("required", "required");
            } else {
                group.style.display = "none";
                autreAuteurInput.removeAttribute("required");
            }
        }

        async function updateStudios() {
            const categorie = document.getElementById("categorie").value;
            const studioSelect = document.getElementById("studio");
            if (!studioSelect) return;

            const current = studioSelect.value;
            studioSelect.innerHTML = "<option value=''>Sélectionnez un studio</option><option value='autre'>Autre</option><option value='1'>Inconnu</option>";

            if (!categorie) {
                if ([...studioSelect.options].some(o => o.value === current)) {
                    studioSelect.value = current;
                }
                return;
            }

            try {
                const res = await fetch(proposerRoutes.studios + '?categorie=' + encodeURIComponent(categorie), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success && Array.isArray(data.studios)) {
                    data.studios.forEach(s => {
                        if (String(s.id) === '1') return;
                        const option = document.createElement('option');
                        option.value = s.id;
                        option.textContent = s.nom;
                        studioSelect.appendChild(option);
                    });
                }
            } catch (e) {
            }

            if ([...studioSelect.options].some(o => o.value === current)) {
                studioSelect.value = current;
            }
            toggleAutreStudio();
        }

        async function updateAuteurs() {
            const categorie = document.getElementById("categorie").value;
            const auteurSelect = document.getElementById("auteur");
            if (!auteurSelect) return;

            const current = auteurSelect.value;
            auteurSelect.innerHTML = "<option value=''>Sélectionnez un auteur</option><option value='autre'>Autre</option><option value='1'>Inconnu</option>";

            if (!categorie) {
                if ([...auteurSelect.options].some(o => o.value === current)) {
                    auteurSelect.value = current;
                }
                return;
            }

            try {
                const res = await fetch(proposerRoutes.auteurs + '?categorie=' + encodeURIComponent(categorie), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success && Array.isArray(data.auteurs)) {
                    data.auteurs.forEach(a => {
                        if (String(a.id) === '1') return;
                        const option = document.createElement('option');
                        option.value = a.id;
                        option.textContent = a.nom;
                        auteurSelect.appendChild(option);
                    });
                }
            } catch (e) {
            }

            if ([...auteurSelect.options].some(o => o.value === current)) {
                auteurSelect.value = current;
            }
            toggleAutreAuteur();
        }
    </script>
@endsection
