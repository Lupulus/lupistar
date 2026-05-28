@extends('layouts.site', ['title' => 'Accueil'])

@section('content')
    <main class="home">
        @if(!empty($heroFilm))
            @php
                $heroImg = $heroFilm->image_asset_path ?? 'img/favicon.ico';
                $heroAvg = $heroFilm->note_moyenne_global ?? $heroFilm->note_moyenne ?? null;
                $heroAvgFloat = is_numeric($heroAvg) ? (float) $heroAvg : null;
            @endphp
            <section class="home-hero">
                <div class="home-hero-card recent-film-item" data-id="{{ $heroFilm->id }}">
                    <div class="home-hero-media">
                        <img src="{{ asset($heroImg) }}" alt="{{ $heroFilm->nom_film }}">
                    </div>
                    <div class="home-hero-overlay">
                        <div class="home-hero-pills">
                            <span class="home-pill">{{ $heroFilm->categorie }}</span>
                            @if(!empty($heroFilm->pays?->nom))
                                <span class="home-pill subtle">{{ $heroFilm->pays->nom }}</span>
                            @endif
                            <span class="home-pill subtle">{{ $heroFilm->date_sortie }}</span>
                            @if($heroAvgFloat !== null)
                                <span class="home-pill accent">★ {{ number_format($heroAvgFloat, 1, '.', '') }}</span>
                            @endif
                        </div>
                        <h1 class="home-hero-title">{{ $heroFilm->nom_film }}</h1>
                        @if(!empty($heroFilm->description))
                            <p class="home-hero-desc">{{ $heroFilm->description }}</p>
                        @endif
                        <div class="home-hero-cta">
                            <button class="home-btn" type="button">Voir les détails</button>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section class="home-reco">
            <div class="home-section-head">
                <h2>Recommandations</h2>
                <div class="home-reco-controls">
                    @if(!empty($isLoggedIn))
                        <label class="home-toggle">
                            <input type="checkbox" id="home-reco-include-seen">
                            <span>Inclure déjà vus</span>
                        </label>
                    @endif
                    <button type="button" id="home-reco-refresh" class="home-btn secondary">Reproposer</button>
                </div>
            </div>
            <div id="home-reco-container" data-api-url="{{ route('api.accueil.recommendations') }}">
                @include('Accueil._recommendations', ['recommendations' => $recommendations ?? collect(), 'isLoggedIn' => $isLoggedIn ?? false])
            </div>
        </section>

        <section class="recently-added">
            <h2>Ajouts récent du moment</h2>

            @if(!empty($recentFilms) && $recentFilms->isNotEmpty())
                <div class="film-container">
                    <div class="carousel-container">
                        <button class="carousel-btn left" type="button">&#10094;</button>

                        <div class="film-carousel">
                            @foreach($recentFilms as $film)
                                @php
                                    $imagePath = $film->image_asset_path ?? 'img/favicon.ico';
                                @endphp

                                <div class="recent-film-item" data-id="{{ $film->id }}">
                                    <div class="film-image">
                                        <img src="{{ asset($imagePath) }}" alt="{{ $film->nom_film }}">
                                    </div>

                                    <div class="film-details">
                                        <h3>{{ $film->nom_film }}</h3>
                                        <p class="studio">
                                            <strong><u>Catégorie:</u></strong>
                                            <span class="studio-value">{{ $film->categorie }}</span>
                                        </p>
                                        <p class="date-sortie"><strong><u>Année:</u>&nbsp;</strong>{{ $film->date_sortie }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button class="carousel-btn right" type="button">&#10095;</button>
                    </div>
                </div>
            @endif
        </section>

        @if(!empty($voyagePays) && !empty($voyageFilms) && $voyageFilms->isNotEmpty())
            <section class="home-voyage">
                <div class="home-section-head">
                    <h2>Voyage — {{ $voyagePays }}</h2>
                    <div class="home-voyage-sub">Découverte du jour • toutes catégories confondues</div>
                </div>
                <div class="home-voyage-scene">
                    <div class="home-voyage-a3d" style="--n: {{ $voyageFilms->count() }}">
                        @foreach($voyageFilms as $film)
                            @php $imagePath = $film->image_asset_path ?? 'img/favicon.ico'; @endphp
                            <div class="home-voyage-card" data-id="{{ $film->id }}" style="--i: {{ $loop->index }}">
                                <img src="{{ asset($imagePath) }}" alt="{{ $film->nom_film }}">
                                <div class="home-voyage-card-meta">
                                    <div class="home-voyage-title">{{ $film->nom_film }}</div>
                                    <div class="home-voyage-mini">
                                        <span>{{ $film->categorie }}</span>
                                        <span>•</span>
                                        <span>{{ $film->date_sortie }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </main>
@endsection

@section('scripts')
    <script src="{{ asset('scripts-js/accueil.js') }}" defer></script>
@endsection
