@extends('layouts.site', ['title' => 'Lupistar — Accueil'])

@section('content')
    <section class="recently-added">
        <h2>Ajouts récent du moment</h2>

        <div class="categorie-container">
            @foreach($categories as $category)
                @php
                    $films = $recentFilmsByCategory[$category] ?? collect();
                @endphp

                @if($films->isNotEmpty())
                    <div class="categorie-h4">
                        <h4>{{ $category }}</h4>
                    </div>

                    <div class="film-container">
                        <div class="carousel-container">
                            <button class="carousel-btn left" type="button">&#10094;</button>

                            <div class="film-carousel">
                                @foreach($films as $film)
                                    @php
                                        $imagePath = $film->image_asset_path ?? 'img/favicon.ico';
                                    @endphp

                                    <div class="recent-film-item" data-id="{{ $film->id }}">
                                        <div class="film-image">
                                            <img src="{{ asset($imagePath) }}" alt="{{ $film->nom_film }}">
                                        </div>

                                        <div class="film-details">
                                            <h3>{{ $film->nom_film }}</h3>
                                            <p class="studio"><strong><u>Studio:</u>&nbsp;</strong>{{ $film->studio?->nom ?? 'Inconnu' }}</p>
                                            <p class="date-sortie"><strong><u>Année:</u>&nbsp;</strong>{{ $film->date_sortie }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <button class="carousel-btn right" type="button">&#10095;</button>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </section>
@endsection
