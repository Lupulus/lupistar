@php
    $accueilService = app(App\Services\AccueilService::class);
@endphp

@php
    $isMyList = (bool) ($isMyList ?? false);
    $myFilmIds = is_array($myFilmIds ?? null) ? $myFilmIds : [];
@endphp

<div class="film-container-tab">
    @forelse($films as $film)
        @php
            $imagePath = $accueilService->toPublicAssetPath($film->image_path);
            $studioName = $film->studio?->nom ?? 'Inconnu';
            $dateSortie = $film->date_sortie ?? '';
            $isSerie = is_numeric($film->saison) && (int) $film->saison > 0;
            $noteValue = $isMyList
                ? ($film->user_note ?? null)
                : ($film->note_moyenne_global ?? ($film->note_moyenne ?? null));
            $noteFloat = is_numeric($noteValue) ? (float) $noteValue : null;
            $filledStars = $noteFloat === null ? 0 : (int) floor($noteFloat);
        @endphp

        <div class="film-box" data-id="{{ $film->id }}">
            <div class="film-image">
                <img src="{{ asset($imagePath) }}" alt="{{ $film->nom_film }}">
            </div>

            <div class="film-details">
                <h3 class="nom">
                    {{ $film->nom_film }}
                    @if($isSerie)
                        @php
                            $isSaisonDetaillee = (bool) ($film->saison_detaillee ?? true);
                        @endphp
                        <span class="serie-info">
                            @if($isSaisonDetaillee)
                                S{{ (int) $film->saison }}
                            @else
                                {{ (int) $film->saison }} saison(s)
                            @endif
                            @if(!empty($film->nbrEpisode)) ({{ (int) $film->nbrEpisode }} ép.) @endif
                        </span>
                    @endif
                </h3>

                <div class="film-details-grid">
                    <div class="film-metadata">
                        <p class="studio"><strong>Studio:</strong> {{ $studioName }}</p>
                        <p class="date"><strong>Sortie:</strong> {{ $dateSortie }}</p>
                        @if(! $isSerie && !empty($film->nbrEpisode))
                            <p class="episodes"><strong>Épisodes:</strong> {{ (int) $film->nbrEpisode }}</p>
                        @endif
                    </div>

                    <div class="film-description">
                        <p class="description"><strong>Description:</strong> {{ $film->description ?? '' }}</p>
                    </div>
                </div>
            </div>

            <div class="film-rating">
                <div class="note">
                    <div class="note-stars">
                        @for ($i = 1; $i <= 10; $i++)
                            <span class="star @if($i <= $filledStars) filled @endif">★</span>
                        @endfor
                    </div>
                    <span class="note-value">{{ $noteFloat === null ? '-' : number_format($noteFloat, 1, '.', '') }}</span>
                </div>
            </div>

            @if(! $isMyList)
                @php
                    $inMyList = in_array((int) $film->id, $myFilmIds, true);
                    $wolfClass = $inMyList ? 'wolf-view invert-filter' : 'wolf-view';
                    $wolfAction = $inMyList ? 'remove' : 'add';
                    $wolfTitle = $inMyList ? 'Supprimer de Ma Liste' : 'Ajouter à Ma Liste !';
                @endphp
                <img src="{{ asset('img/empreinte-wolf.png') }}" alt="Empreinte de Wolf" class="{{ $wolfClass }}" data-id="{{ (int) $film->id }}" data-action="{{ $wolfAction }}" title="{{ $wolfTitle }}">
            @endif
        </div>
    @empty
        <p style="text-align: center; color: var(--text-white); font-size: 1.2rem; margin: 2rem 0;">Aucun film trouvé pour cette catégorie.</p>
    @endforelse
</div>
