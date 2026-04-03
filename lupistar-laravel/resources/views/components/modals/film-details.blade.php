@php
    $image = $imagePath ? asset($imagePath) : asset('img/favicon.ico');
    $class = 'wolf-view';
    $action = 'add';
    if (!empty($filmDansListe)) {
        $class .= ' invert-filter';
        $action = 'remove';
    }
@endphp

<span class="modal-close">&times;</span>
<div class="modal-header">
    <h2 class="modal-title">{{ $film->nom_film }}</h2>
</div>
<div class="modal-left">
    <img class="modal-image" src="{{ $image }}" alt="{{ $film->nom_film }}">

    <div class="note-bar-container">
        <h3>Répartition des Notes :</h3>
        <div class="note-bar-chart" id="note-bar-chart">
            <p>Chargement des notes...</p>
        </div>
    </div>

    @if(!empty($filmDansListe))
        <div class="user-note-container">
            <p><strong>Ma note :</strong>
                <span id="user-note">
                    @if($userNote !== null)
                        {{ rtrim(rtrim(number_format((float) $userNote, 2, '.', ''), '0'), '.') }}/10
                    @else
                        Non noté
                    @endif
                </span>
                <span id="edit-note" class="edit-icon" title="Modifier ma note">✏️</span>
            </p>
            <input type="number" id="note-input" min="0" max="10" step="0.25" style="display:none;">
        </div>
    @endif
</div>
<div class="modal-right">
    @php
        $isSerie = in_array((string) $film->categorie, ['Série', "Série d'Animation"], true) || ((string) $film->categorie === 'Anime' && $film->ordre_suite === null);
        $isSaisonDetaillee = (bool) ($film->saison_detaillee ?? true);
    @endphp
    <p id="modal-categorie"><strong>Catégorie :</strong> {{ $film->categorie }}</p>
    <p id="modal-studio"><strong>Studio :</strong> {{ $film->studio?->nom ?? 'Inconnu' }}</p>
    <p id="modal-date"><strong>Date de sortie :</strong> {{ $film->date_sortie }}</p>
    <p id="modal-pays"><strong>Pays :</strong> {{ $film->pays?->nom ?? 'Inconnu' }}</p>
    <p id="modal-auteur"><strong>Auteur :</strong> {{ $film->auteur?->nom ?? 'Inconnu' }}</p>
    @if($isSerie)
        @if($isSaisonDetaillee)
            <p id="modal-saison"><strong>Saison :</strong> {{ $film->saison }}</p>
        @else
            <p id="modal-nombre-saison"><strong>Nombre de saison :</strong> {{ $film->saison }}</p>
        @endif
        <p id="modal-nombre-episodes"><strong>Nombre d'épisode :</strong> {{ $film->nbrEpisode }}</p>
    @endif
    <p id="modal-description"><strong>Description :</strong> {!! nl2br(e($film->description)) !!}</p>

    @if(!empty($isLoggedIn))
        <img
            src="{{ asset('img/empreinte-wolf.png') }}"
            alt="Favori"
            class="{{ $class }}"
            data-id="{{ $film->id }}"
            data-nom="{{ $film->nom_film }}"
            data-action="{{ $action }}"
            title="{{ $action === 'add' ? 'Ajouter à Ma Liste !' : 'Supprimer de Ma Liste' }}"
        >
    @endif

    <p id="modal-sous-genres-label"><strong>Sous-genres :</strong></p>
    <ul id="modal-sous-genres">
        @if($film->sousGenres?->isNotEmpty())
            @foreach($film->sousGenres as $sg)
                <li>{{ $sg->nom }}</li>
            @endforeach
        @else
            <li>Aucun sous-genre</li>
        @endif
    </ul>
</div>
