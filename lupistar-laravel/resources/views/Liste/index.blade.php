@extends('layouts.site')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/style-liste.css') }}">
@endsection

@section('content')
    <div class="tab">
        @foreach($categories as $categoryName)
            @php
                $active = $categoryName === $category ? 'active' : '';
            @endphp
            <button class="tablinks {{ $active }}" type="button" data-category="{{ $categoryName }}">
                {{ $categoryName }}
            </button>
        @endforeach
    </div>

    <div class="search-bar-container">
        <div class="filter-container">
            <div class="top-section">
                <div class="search-section-left">
                    <input type="text" id="search-bar" placeholder="Rechercher un film..." maxlength="50" value="{{ request('recherche') }}">
                </div>
                <div class="statistics-section">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-label">Nombre de</span>
                            <span class="stat-value" id="stat-animation">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Top 3 Studios:</span>
                            <span class="stat-value" id="stat-studios">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Meilleure décennie:</span>
                            <span class="stat-value" id="stat-decade">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="filters-frame">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="studio-filter">Filtrer par Studio:</label>
                        <select id="studio-filter" name="studio">
                            <option value="">Tous les studios</option>
                            @foreach($studios as $studio)
                                <option value="{{ $studio }}" @selected(request('studio') === $studio)>{{ $studio }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="annee-filter">Filtrer par Année:</label>
                        <select id="annee-filter" name="annee">
                            <option value="">Toutes les années</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}" @selected((string) request('annee') === (string) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="note-filter">Filtrer par Note (moyenne):</label>
                        <select id="note-filter" name="note">
                            <option value="">Toutes les notes</option>
                            @for ($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" @selected((string) request('note') === (string) $i)>{{ $i }} et plus</option>
                            @endfor
                        </select>
                    </div>

                    <div class="filter-group" id="pays-filter-group">
                        <label for="pays-filter">Filtrer par Pays:</label>
                        <select id="pays-filter" name="pays">
                            <option value="">Tous les pays</option>
                            @foreach($pays as $paysNom)
                                <option value="{{ $paysNom }}" @selected(request('pays') === $paysNom)>{{ $paysNom }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group" id="type-filter-group" style="display: none;">
                        <label for="type-filter">Type:</label>
                        <select id="type-filter" name="type">
                            <option value="">Tous confondus</option>
                            <option value="Film" @selected(request('type') === 'Film' || request('type') === 'film')>Film</option>
                            <option value="Série" @selected(request('type') === 'Série' || request('type') === 'serie' || request('type') === 'série')>Série</option>
                        </select>
                    </div>

                    <div class="filter-group" id="episodes-filter-group" style="display: none;">
                        <label for="episodes-filter">Nombre d'épisodes:</label>
                        <select id="episodes-filter" name="episodes">
                            <option value="">Tous les épisodes</option>
                            <option value="0-13" @selected(request('episodes') === '0-13')>0 à 13 épisodes</option>
                            <option value="0-24" @selected(request('episodes') === '0-24')>0 à 24 épisodes</option>
                            <option value="13-24" @selected(request('episodes') === '13-24')>13 à 24 épisodes</option>
                            <option value="24+" @selected(request('episodes') === '24+')>Plus de 24 épisodes</option>
                            <option value="101" @selected(request('episodes') === '101')>101 épisodes et plus</option>
                        </select>
                    </div>
                </div>

                <div class="action-buttons-container">
                    <button class="search-btn">Rechercher</button>
                    <button class="reset-btn" type="button">Réinitialiser</button>
                </div>
            </div>
        </div>
    </div>

    <div id="tabcontent" class="tabcontent">
        <div id="films-container">
            @include('Liste._films-grid', ['films' => $paginator->items(), 'isMyList' => false, 'myFilmIds' => $myFilmIds ?? []])
        </div>
        <div id="pagination-container">
            @include('Liste._pagination', ['paginator' => $paginator])
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('scripts-js/ma-liste-actions.js') }}" defer></script>
@endsection
