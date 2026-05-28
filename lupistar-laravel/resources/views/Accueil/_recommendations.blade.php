@php
    $recommendations = $recommendations ?? collect();
    $isLoggedIn = (bool) ($isLoggedIn ?? false);
@endphp

@if($recommendations->isEmpty())
    <div class="home-empty">
        Aucune recommandation pour le moment.
    </div>
@else
    <div class="home-reco-list">
        @foreach($recommendations as $film)
            @php
                $imagePath = $film->image_asset_path ?? 'img/favicon.ico';
                $avg = $film->note_moyenne_global ?? $film->note_moyenne ?? null;
                $avgFloat = is_numeric($avg) ? (float) $avg : null;
            @endphp
            <div class="home-reco-card" data-id="{{ $film->id }}">
                <div class="home-reco-poster">
                    <img src="{{ asset($imagePath) }}" alt="{{ $film->nom_film }}">
                </div>
                <div class="home-reco-meta">
                    <div class="home-reco-pills">
                        <span class="home-pill">{{ $film->categorie }}</span>
                        @if(!empty($film->pays?->nom))
                            <span class="home-pill subtle">{{ $film->pays->nom }}</span>
                        @endif
                    </div>
                    <div class="home-reco-title">{{ $film->nom_film }}</div>
                    <div class="home-reco-footer">
                        <span class="home-reco-year">{{ $film->date_sortie }}</span>
                        @if($avgFloat !== null)
                            <span class="home-reco-note">★ {{ number_format($avgFloat, 1, '.', '') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

