@extends('layouts.site')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/style-proposer.css') }}">
@endsection

@section('content')
    <div class="container" style="max-width:920px;margin:30px auto;">
        <h2>Proposer un film</h2>
        <p class="info-text">Proposez un titre à ajouter. Il sera vérifié avant d’apparaître publiquement.</p>
        <form action="{{ route('proposer-film.store') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div>
                <label for="categorie">Catégorie :</label>
                <select id="categorie" name="categorie" required>
                    <option value="">Sélectionner</option>
                    <option value="Film">Film</option>
                    <option value="Animation">Animation</option>
                    <option value="Anime">Anime</option>
                    <option value="Série">Série</option>
                    <option value="Série d'Animation">Série d'Animation</option>
                </select>
                @error('categorie')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
            </div>
            <div>
                <label id="nom_film_label" for="nom_film">Nom du film :</label>
                <input type="text" id="nom_film" name="nom_film" maxlength="50" placeholder="Nom du film (max 50 caractères)" required value="{{ old('nom_film') }}">
                @error('nom_film')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
            </div>
            <div id="anime-fields" style="display:none;">
                <div>
                    <label for="saison">Saison :</label>
                    <input type="number" id="saison" name="saison" min="0" value="{{ old('saison') }}">
                </div>
                <div>
                    <label for="nbrEpisode">Nombre d'épisodes :</label>
                    <input type="number" id="nbrEpisode" name="nbrEpisode" min="0" value="{{ old('nbrEpisode') }}">
                </div>
            </div>
            <div>
                <label for="date_sortie">Année de sortie :</label>
                <input type="number" id="date_sortie" name="date_sortie" min="1900" max="2100" required value="{{ old('date_sortie') }}">
                @error('date_sortie')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
            </div>
            <div>
                <label for="ordre_suite">Ordre :</label>
                <input type="number" id="ordre_suite" name="ordre_suite" min="0" value="{{ old('ordre_suite', 1) }}">
            </div>
            <div>
                <label for="studio_select">Studio :</label>
                <select id="studio_select" name="studio_select">
                    <option value="">Sélectionnez un ...</option>
                    <option value="autre">Autre</option>
                    @foreach($studios as $id => $nom)
                        <option value="{{ $id }}">{{ $nom }}</option>
                    @endforeach
                </select>
                <input type="text" id="nouveau_studio" name="nouveau_studio" placeholder="Nouveau studio" style="display:none" value="{{ old('nouveau_studio') }}">
            </div>
            <div>
                <label for="auteur_select">Auteur :</label>
                <select id="auteur_select" name="auteur_select">
                    <option value="">Sélectionnez un ...</option>
                    <option value="autre">Autre</option>
                    @foreach($auteurs as $id => $nom)
                        <option value="{{ $id }}">{{ $nom }}</option>
                    @endforeach
                </select>
                <input type="text" id="nouveau_auteur" name="nouveau_auteur" placeholder="Nouvel auteur" style="display:none" value="{{ old('nouveau_auteur') }}">
            </div>
            <div>
                <label for="pays_select">Pays :</label>
                <select id="pays_select" name="pays_select">
                    <option value="">Sélectionnez un ...</option>
                    <option value="autre">Autre</option>
                    @foreach($pays as $id => $nom)
                        <option value="{{ $id }}">{{ $nom }}</option>
                    @endforeach
                </select>
                <input type="text" id="nouveau_pays" name="nouveau_pays" placeholder="Nouveau pays" style="display:none" value="{{ old('nouveau_pays') }}">
            </div>
            <div>
                <label for="sous_genres">Sous-genres :</label>
                <select id="sous_genres" name="sous_genres[]" multiple size="6">
                    @foreach($sousGenres as $id => $nom)
                        <option value="{{ $id }}">{{ $nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="description">Description :</label>
                <textarea id="description" name="description" maxlength="400" rows="4" placeholder="Description (max 400 caractères)">{{ old('description') }}</textarea>
                <div id="charCount">0 / 400</div>
            </div>
            <div>
                <label for="image">Image :</label>
                <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp">
                @error('image')<div id="message-container" class="error"><p>{{ $message }}</p></div>@enderror
            </div>
            <div>
                <button type="submit">Envoyer ma proposition</button>
            </div>
        </form>
        @if(session('status'))
            <div id="message-container" class="success"><p>{{ session('status') }}</p></div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        function updateCharCount() {
            const desc = document.getElementById("description");
            const counter = document.getElementById("charCount");
            if (!desc || !counter) return;
            if (desc.value.length > 400) desc.value = desc.value.slice(0, 400);
            counter.textContent = desc.value.length + " / 400";
        }
        document.addEventListener('DOMContentLoaded', function(){
            const desc = document.getElementById("description");
            if (desc) {
                updateCharCount();
                desc.addEventListener('input', updateCharCount);
            }
            const categorie = document.getElementById('categorie');
            const animeFields = document.getElementById('anime-fields');
            const studioSelect = document.getElementById('studio_select');
            const nouveauStudio = document.getElementById('nouveau_studio');
            const auteurSelect = document.getElementById('auteur_select');
            const nouveauAuteur = document.getElementById('nouveau_auteur');
            const paysSelect = document.getElementById('pays_select');
            const nouveauPays = document.getElementById('nouveau_pays');
            const toggleNew = (selectEl, inputEl) => {
                const v = (selectEl.value || '').trim();
                inputEl.style.display = v === 'autre' ? '' : 'none';
            };
            if (categorie) {
                const syncAnime = () => {
                    const v = (categorie.value || '').trim();
                    animeFields.style.display = v === 'Anime' ? '' : 'none';
                };
                categorie.addEventListener('change', syncAnime);
                syncAnime();
            }
            if (studioSelect && nouveauStudio) {
                studioSelect.addEventListener('change', () => toggleNew(studioSelect, nouveauStudio));
                toggleNew(studioSelect, nouveauStudio);
            }
            if (auteurSelect && nouveauAuteur) {
                auteurSelect.addEventListener('change', () => toggleNew(auteurSelect, nouveauAuteur));
                toggleNew(auteurSelect, nouveauAuteur);
            }
            if (paysSelect && nouveauPays) {
                paysSelect.addEventListener('change', () => toggleNew(paysSelect, nouveauPays));
                toggleNew(paysSelect, nouveauPays);
            }
            if (categorie) {
                const refreshDeps = async () => {
                    const cat = categorie.value;
                    await updateSelectByCategory(cat, 'studios', document.getElementById('studio_select'));
                    await updateSelectByCategory(cat, 'auteurs', document.getElementById('auteur_select'));
                };
                categorie.addEventListener('change', refreshDeps);
                refreshDeps();
            }
        });

        async function updateSelectByCategory(cat, type, selectEl) {
            if (!selectEl) return;
            const url = type === 'studios' ? '{{ route('proposer-film.studios') }}' : '{{ route('proposer-film.auteurs') }}';
            const params = cat ? ('?categorie=' + encodeURIComponent(cat)) : '';
            try {
                const res = await fetch(url + params);
                const data = await res.json();
                const items = data[type] || [];
                const current = selectEl.value;
                selectEl.innerHTML = '<option value="">Sélectionnez un ...</option><option value="autre">Autre</option><option value="1">Inconnu</option>';
                items.forEach(it => {
                    if (it.nom === 'Inconnu') return;
                    const opt = document.createElement('option');
                    opt.value = it.id;
                    opt.textContent = it.nom;
                    selectEl.appendChild(opt);
                });
                if ([...selectEl.options].some(o => o.value === current)) {
                    selectEl.value = current;
                }
            } catch (e) {
                // silence
            }
        }
    </script>
@endsection
