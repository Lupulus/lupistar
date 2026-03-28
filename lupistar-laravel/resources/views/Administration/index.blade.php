@extends('layouts.site')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/style-admin.css') }}">
@endsection

@section('content')
    @php
        function getDateColor(int $date): string
        {
            $normalized = ($date - 1900) / (2099 - 1900);
            if ($date <= 1999) {
                $hue = 0 + $normalized * (35 - 0);
            } elseif ($date <= 2004) {
                $hue = 35 + $normalized * (200 - 35);
            } else {
                $hue = 200 + $normalized * (120 - 200);
            }

            return "hsl({$hue}, 100%, 50%)";
        }
        $csrf = csrf_token();
        $categories = ['Film', 'Animation', 'Anime', 'Série', "Série d'Animation"];
    @endphp

    <div id="admin-summary-sidebar" class="admin-summary-sidebar">
        <div class="summary-toggle" onclick="toggleSummary()">
            <span class="summary-icon">📋</span>
            <span class="summary-text">Sommaire</span>
        </div>

        <div class="summary-content" id="summary-content">
            <div class="summary-item" onclick="scrollToSection('add-film-section')">
                <div class="floating-icon">
                    <span class="icon">➕</span>
                </div>
                <span class="item-text">Ajouter un film</span>
            </div>

            <div class="summary-item" onclick="scrollToSection('pending-films-section')">
                <div class="floating-icon">
                    <span class="icon">⏳</span>
                </div>
                <span class="item-text">Films en attente</span>
            </div>

            <div class="summary-item" onclick="scrollToSection('films-list-section')">
                <div class="floating-icon">
                    <span class="icon">🎬</span>
                </div>
                <span class="item-text">Liste des films</span>
            </div>

            <div class="summary-item" onclick="scrollToSection('send-notification-section')">
                <div class="floating-icon">
                    <span class="icon">📧</span>
                </div>
                <span class="item-text">Envoyer notification</span>
            </div>

            @if(session('titre') === 'Super-Admin')
                <div class="summary-item" onclick="scrollToSection('super-admin-communication-section')">
                    <div class="floating-icon">
                        <span class="icon">📨</span>
                    </div>
                    <span class="item-text">Communication</span>
                </div>
            @endif

            <div class="summary-item" onclick="openStudioConversionsModal()">
                <div class="floating-icon">
                    <span class="icon">🔄</span>
                </div>
                <span class="item-text">Conversions Studios</span>
            </div>
        </div>
    </div>

    <div id="add-film-section" class="admin-section">
        <h2>Ajouter un film</h2>
        <form id="filmForm" action="{{ route('administration.add-film') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-section two-columns">
                <div class="form-group">
                    <label id="nom_film_label" for="nom_film">Nom du film :</label>
                    <input type="text" id="nom_film" name="nom_film" placeholder="Nom du film (max 50 caractères)" maxlength="50" required>
                </div>
                <div class="form-group">
                    <label id="categorie_label" for="categorie">Catégorie :</label>
                    <select id="categorie" name="categorie" required onchange="updateStudios()">
                        <option value="">Sélectionnez une catégorie</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="anime-type-section" class="form-section two-columns" style="display:none;">
                <div class="form-group">
                    <label for="anime_type">Type d'Anime :</label>
                    <select id="anime_type" name="anime_type" onchange="handleAnimeTypeChange()">
                        <option value="">Sélectionnez le type</option>
                        <option value="Film">Film</option>
                        <option value="Série">Série</option>
                    </select>
                </div>
            </div>

            <div class="form-section full-width">
                <div class="form-group">
                    <label id="description_label" for="description">Description :</label>
                    <textarea id="description" name="description" rows="4" cols="50" placeholder="Pas de description" maxlength="400" oninput="updateCharCount()"></textarea>
                    <span id="charCount" class="description-compteur">0 / 400</span>
                </div>
            </div>

            <div class="form-section three-columns">
                <div class="form-group">
                    <label id="date_sortie_label" for="date_sortie">Année de sortie :</label>
                    <input type="number" id="date_sortie" name="date_sortie" min="1900" max="2099" step="1" value="{{ date('Y') }}" required>
                </div>
                <div class="form-group">
                    <label id="image_label" for="image">Image du film :</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label id="ordre_suite_label" for="ordre_suite">Ordre (Suite?) :</label>
                    <input type="number" id="ordre_suite" name="ordre_suite" min="1" max="25" step="1" placeholder="1">
                </div>
            </div>

            <div class="form-section two-columns">
                <div class="form-group">
                    <label id="saison_label" for="saison" style="display:none;">Numéro de saison :</label>
                    <input type="number" id="saison" name="saison" min="1" max="100" placeholder="1" style="display:none;">
                </div>
                <div class="form-group">
                    <label id="nbrEpisode_label" for="nbrEpisode" style="display:none;">Nombre d'épisodes :</label>
                    <input type="number" id="nbrEpisode" name="nbrEpisode" min="1" max="9999" placeholder="10" style="display:none;">
                </div>
            </div>

            <div class="form-section two-columns">
                <div class="form-group">
                    <label id="studio_label" for="studio">Studio :</label>
                    <select id="studio" name="studio_id" required onchange="toggleAutreStudio()">
                        <option value="">Sélectionnez un studio</option>
                        <option value="autre">Autre</option>
                        @if(isset($studios[1]))
                            <option value="1">{{ $studios[1] }}</option>
                        @endif
                        @foreach($studios as $id => $nom)
                            @continue((int) $id === 1)
                            <option value="{{ $id }}">{{ $nom }}</option>
                        @endforeach
                    </select>
                    <input type="text" id="nouveau_studio" name="nouveau_studio" placeholder="Nom du studio" maxlength="30" style="display:none;">
                </div>
                <div class="form-group">
                    <label id="auteur_label" for="auteur">Auteur :</label>
                    <select id="auteur" name="auteur_id" required>
                        <option value="">Sélectionnez un auteur</option>
                        <option value="autre">Autre</option>
                        @if(isset($auteurs[1]))
                            <option value="1">{{ $auteurs[1] }}</option>
                        @endif
                        @foreach($auteurs as $id => $nom)
                            @continue((int) $id === 1)
                            <option value="{{ $id }}">{{ $nom }}</option>
                        @endforeach
                    </select>
                    <input type="text" id="nouveau_auteur" name="nouveau_auteur" placeholder="Nom de l'auteur" maxlength="30" style="display:none;">
                </div>
            </div>

            <div class="form-section full-width">
                <div class="form-group">
                    <label id="pays_label" for="pays">Pays :</label>
                    <select id="pays" name="pays_id" required onchange="handlePaysChange()">
                        <option value="">Sélectionnez un pays</option>
                        @foreach($pays as $id => $nom)
                            <option value="{{ $id }}">{{ $nom }}</option>
                        @endforeach
                    </select>
                    <div id="japan-notification" class="japan-notification" style="display: none;">
                        <span class="notification-icon">ℹ️</span>
                        <span class="notification-text">Les films et séries d'animation japonaises appartiennent à la catégorie "Anime".</span>
                    </div>
                </div>
            </div>

            <div class="form-section full-width">
                <div class="form-group">
                    <label id="sous-genres_label">Sous-genres :</label>
                    <div id="sous-genres-container">
                        @php
                            $sousGenresList = array_values($sousGenres);
                            $sousGenresKeys = array_keys($sousGenres);
                            $totalSousGenres = count($sousGenresList);
                            $colonnes = 6;
                            $lignes = (int) ceil($totalSousGenres / $colonnes);
                        @endphp
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
                                                @endphp
                                                <td>
                                                    <label class="checkbox-label">
                                                        <input type="checkbox" name="sous_genres[]" value="{{ $id }}"> {{ $nom }}
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
                </div>
            </div>

            <input id="Bouton-ajouter" type="submit" value="Ajouter le film">
        </form>
        <div id="notification"></div>
    </div>

    <div id="pending-films-section" class="admin-section">
        <h2>Films en attente d'approbation</h2>
        <div id="pending-films-container" class="pending-container">
            <div id="pending-films-count" class="pending-count">Chargement...</div>
            <div id="pending-films-list" class="pending-films-list"></div>
        </div>

        <div id="pendingFilmModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close" onclick="closePendingFilmModal()">&times;</span>
                <h3 id="modal-film-title">Examiner la proposition</h3>

                <div class="modal-body">
                    <div class="film-info-section">
                        <h4>Informations du film</h4>
                        <div class="film-details">
                            <div class="detail-row">
                                <label>Nom du film:</label>
                                <input type="text" id="modal-nom-film" class="modal-input">
                            </div>
                            <div class="detail-row">
                                <label>Catégorie:</label>
                                <select id="modal-categorie" class="modal-input">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="detail-row">
                                <label>Description:</label>
                                <textarea id="modal-description" class="modal-input" rows="4"></textarea>
                            </div>
                            <div class="detail-row">
                                <label>Année de sortie:</label>
                                <input type="number" id="modal-date-sortie" class="modal-input" min="1900" max="2099">
                            </div>
                            <div class="detail-row">
                                <label>Ordre/Suite:</label>
                                <input type="number" id="modal-ordre-suite" class="modal-input" min="1" max="25">
                            </div>
                            <div class="detail-row" id="modal-saison-row">
                                <label>Saison:</label>
                                <input type="number" id="modal-saison" class="modal-input" min="1" max="100">
                            </div>
                            <div class="detail-row" id="modal-episodes-row">
                                <label>Nombre d'épisodes:</label>
                                <input type="number" id="modal-nbrEpisode" class="modal-input" min="1" max="9999">
                            </div>
                            <div class="detail-row">
                                <label>Studio:</label>
                                <span id="modal-studio" class="modal-info"></span>
                            </div>
                            <div class="detail-row">
                                <label>Auteur:</label>
                                <span id="modal-auteur" class="modal-info"></span>
                            </div>
                            <div class="detail-row">
                                <label>Pays:</label>
                                <span id="modal-pays" class="modal-info"></span>
                            </div>
                            <div class="detail-row">
                                <label>Sous-genres:</label>
                                <span id="modal-sous-genres" class="modal-info"></span>
                            </div>
                            <div class="detail-row">
                                <label>Proposé par:</label>
                                <span id="modal-propose-par" class="modal-info"></span>
                            </div>
                            <div class="detail-row">
                                <label>Date de proposition:</label>
                                <span id="modal-date-proposition" class="modal-info"></span>
                            </div>
                        </div>

                        <div class="film-image-section">
                            <h4>Image du film</h4>
                            <img id="modal-film-image" src="" alt="Image du film" class="modal-film-image">
                        </div>
                    </div>

                    <div class="admin-actions-section">
                        <h4>Actions administrateur</h4>
                        <div class="detail-row">
                            <label>Commentaire administrateur:</label>
                            <textarea id="modal-commentaire-admin" class="modal-input" rows="3" placeholder="Commentaire optionnel pour l'approbation..."></textarea>
                        </div>

                        <div class="detail-row" id="rejection-reason-row" style="display: none;">
                            <label>Raison du rejet:</label>
                            <select id="modal-raison-rejet" class="modal-input">
                                <option value="">Sélectionnez une raison...</option>
                                <option value="Qualité de l'image">Qualité de l'image</option>
                                <option value="Mauvaise information">Mauvaise information</option>
                                <option value="Film déjà existant">Film déjà existant</option>
                                <option value="Contenu inapproprié">Contenu inapproprié</option>
                                <option value="Informations incomplètes">Informations incomplètes</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>

                        <div class="modal-buttons">
                            <button type="button" class="btn-approve" onclick="approveFilm()">Approuver</button>
                            <button type="button" class="btn-reject" onclick="rejectFilm()">Rejeter</button>
                            <button type="button" class="btn-cancel" onclick="closePendingFilmModal()">Annuler</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="films-list-section" class="admin-section">
        <h2>Liste des films ajoutés</h2>
        <div class="search-container">
            <input type="text" id="searchBar" placeholder="Rechercher un film, auteur, studio..." onkeyup="filterFilms()">
        </div>

        <div id="liste-film" class="films-container">
            @foreach($films as $film)
                @php
                    $sg = $sousGenresByFilm[(int) $film->id] ?? [];
                    $sgNames = array_map(fn ($x) => $x['nom'], $sg);
                    $isSerie = in_array((string) $film->categorie, ['Série', "Série d'Animation"], true);
                    $date = (int) $film->date_sortie;
                    $bg = getDateColor($date);
                @endphp
                <div class="film-item" data-id="{{ $film->id }}">
                    <div class="film-image">
                        <img src="{{ $film->image_path ? asset($film->image_path) : '' }}" alt="{{ $film->nom_film }}">
                    </div>

                    <div class="date-sortie" style="background-color: {{ $bg }}; display: flex; align-items: center; justify-content: center; text-align: center; grid-column: 13; grid-row: 1/6; width: auto;">
                        Date de diffusion: {{ $date }}
                    </div>

                    <h3 id="film-nom">{{ $film->nom_film }}</h3>
                    <p id="film-categorie"><u>Catégorie:</u> {{ $film->categorie }}</p>
                    <p id="film-studio"><u>Studio:</u> {{ $film->studio_nom }}</p>
                    <p id="film-auteur"><u>Auteur:</u> {{ $film->auteur_nom }}</p>
                    <p id="film-pays"><u>Pays:</u> {{ $film->pays_nom }}</p>
                    <p id="film-sous-genres"><u>Sous-genres:</u> {{ !empty($sgNames) ? implode(', ', $sgNames) : 'Aucun sous-genre' }}</p>
                    <p id="film-description"><u>Description:</u> {{ $film->description }}</p>
                    @if($isSerie)
                        <p id="film-saison"><u>Saison :</u> {{ $film->saison ?? '-' }}</p>
                        <p id="film-episodes"><u>Nombre d’épisodes :</u> {{ $film->nbrEpisode ?? '-' }}</p>
                    @endif

                    <div class="action-buttons">
                        <button class="modify-btn" type="button" onclick="showModifyForm({{ $film->id }})">Modifier</button>
                        <button class="delete-btn" type="button" onclick="deleteFilm({{ $film->id }})">Supprimer</button>
                    </div>
                </div>

                <div class="modify-form" id="modify-form-{{ $film->id }}" style="display: none;">
                    <form class="modify-form" id="modify-form-{{ $film->id }}-form" style="display: none;" onsubmit="modifyFilm({{ $film->id }}); return false;">
                        <div class="form-container">
                            <label>Nom du film :</label>
                            <input type="text" name="nom_film" value="{{ $film->nom_film }}" required><br>

                            <label>Catégorie :</label>
                            <select name="categorie" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" @selected($film->categorie === $cat)>{{ $cat }}</option>
                                @endforeach
                            </select><br>

                            <label for="description">Description :</label><br>
                            <textarea id="description_{{ $film->id }}" name="description" rows="4" cols="50" placeholder="Pas de description">{{ $film->description }}</textarea><br>

                            @if(! $isSerie)
                                <label for="ordre_suite">Ordre du film (Suite?) :</label>
                                <input type="number" id="ordre_suite_{{ $film->id }}" name="ordre_suite" min="1" max="25" step="1" value="{{ $film->ordre_suite }}"><br>
                            @else
                                <label for="saison">Numéro de saison :</label>
                                <input type="number" id="saison_{{ $film->id }}" name="saison" min="1" max="100" value="{{ $film->saison ?? 1 }}" required><br>

                                <label for="nbrEpisode">Nombre d’épisodes :</label>
                                <input type="number" id="nbrEpisode_{{ $film->id }}" name="nbrEpisode" min="1" max="9999" value="{{ $film->nbrEpisode ?? '' }}" required><br>
                            @endif

                            <label for="date_sortie">Date de sortie :</label>
                            <input type="number" id="date_sortie_{{ $film->id }}" name="date_sortie" min="1900" max="2099" step="1" value="{{ $film->date_sortie }}" required><br>

                            <label>Studio :</label>
                            <select name="studio_id">
                                @foreach($studios as $sid => $snom)
                                    <option value="{{ $sid }}" @selected((int) $film->studio_id === (int) $sid)>{{ $snom }}</option>
                                @endforeach
                            </select><br>

                            <label>Auteur :</label>
                            <select name="auteur_id">
                                @foreach($auteurs as $aid => $anom)
                                    <option value="{{ $aid }}" @selected((int) $film->auteur_id === (int) $aid)>{{ $anom }}</option>
                                @endforeach
                            </select><br>

                            <label>Pays :</label>
                            <select name="pays_id">
                                @foreach($pays as $pid => $pnom)
                                    <option value="{{ $pid }}" @selected((int) $film->pays_id === (int) $pid)>{{ $pnom }}</option>
                                @endforeach
                            </select><br>

                            <label>Sous-genres :</label>
                            @foreach($sousGenres as $gid => $gnom)
                                @php $checked = in_array((int) $gid, array_map(fn ($x) => (int) $x['id'], $sg), true); @endphp
                                <input type="checkbox" name="sous_genres[]" value="{{ $gid }}" @checked($checked)> {{ $gnom }}<br>
                            @endforeach

                            <button type="submit">Enregistrer</button>
                            <button type="button" onclick="hideModifyForm({{ $film->id }})">Annuler</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </div>

    <div id="send-notification-section" class="admin-section">
        <h2>Envoyer une notification</h2>
        <form id="notificationForm">
            <div class="form-section">
                <div class="form-group">
                    <label for="recipient-type">Type de destinataire :</label>
                    <select id="recipient-type" name="recipient_type" required onchange="handleRecipientTypeChange()">
                        <option value="">Sélectionnez le type</option>
                        <option value="all">Tous les utilisateurs</option>
                        <option value="title">Par titre (Admin, Membre, etc.)</option>
                        <option value="specific">Utilisateur spécifique</option>
                    </select>
                </div>
            </div>

            <div id="title-selection" class="form-section" style="display:none;">
                <div class="form-group">
                    <label for="user-title">Titre des utilisateurs :</label>
                    <select id="user-title" name="user_title">
                        <option value="">Sélectionnez un titre</option>
                        <option value="Super-Admin">Super-Admin</option>
                        <option value="Admin">Admin</option>
                        <option value="Membre">Membre</option>
                    </select>
                </div>
            </div>

            <div id="specific-user-selection" class="form-section" style="display:none;">
                <div class="form-group">
                    <label for="search-type">Rechercher par :</label>
                    <select id="search-type" name="search_type" onchange="handleSearchTypeChange()">
                        <option value="">Sélectionnez le type de recherche</option>
                        <option value="username">Nom d'utilisateur</option>
                        <option value="email">Adresse e-mail</option>
                    </select>
                </div>
                <div class="form-group" id="user-search-group" style="display:none;">
                    <label for="user-search" id="user-search-label">Utilisateur :</label>
                    <input type="text" id="user-search" name="user_search" placeholder="Entrez le nom d'utilisateur ou l'e-mail">
                </div>
            </div>

            <div class="form-section">
                <div class="form-group">
                    <label for="notification-title">Titre de la notification :</label>
                    <input type="text" id="notification-title" name="notification_title" placeholder="Titre de la notification" maxlength="100" required>
                </div>
            </div>

            <div class="form-section">
                <div class="form-group">
                    <label for="notification-message">Message :</label>
                    <textarea id="notification-message" name="notification_message" rows="4" placeholder="Contenu de la notification" maxlength="500" required oninput="updateNotificationCharCount()"></textarea>
                    <span id="notificationCharCount" class="description-compteur">0 / 500</span>
                </div>
            </div>

            <div class="form-section">
                <button type="button" class="btn-add" onclick="sendNotification()">Envoyer la notification</button>
                <button type="button" class="btn-cancel" onclick="resetNotificationForm()">Réinitialiser</button>
            </div>

            <div id="notification-result" class="result-message" style="display:none;"></div>
        </form>
    </div>

    @if(session('titre') === 'Super-Admin')
        <div id="super-admin-communication-section" class="admin-section">
            <h2>Communication (Super-Admin)</h2>

            <form id="emailForm">
                <div class="form-section">
                    <div class="form-group">
                        <label for="email-recipient-type">Type de destinataire :</label>
                        <select id="email-recipient-type" name="email_recipient_type" required onchange="handleEmailRecipientTypeChange()">
                            <option value="">Sélectionnez le type</option>
                            <option value="all">Tous les utilisateurs (avec email)</option>
                            <option value="title">Par titre (Admin, Membre, etc.)</option>
                            <option value="specific">Utilisateur spécifique</option>
                        </select>
                    </div>
                </div>

                <div id="email-title-selection" class="form-section" style="display:none;">
                    <div class="form-group">
                        <label for="email-user-title">Titre des utilisateurs :</label>
                        <select id="email-user-title" name="email_user_title">
                            <option value="">Sélectionnez un titre</option>
                            <option value="Super-Admin">Super-Admin</option>
                            <option value="Admin">Admin</option>
                            <option value="Membre">Membre</option>
                        </select>
                    </div>
                </div>

                <div id="email-specific-user-selection" class="form-section" style="display:none;">
                    <div class="form-group">
                        <label for="email-search-type">Rechercher par :</label>
                        <select id="email-search-type" name="email_search_type" onchange="handleEmailSearchTypeChange()">
                            <option value="">Sélectionnez le type de recherche</option>
                            <option value="username">Nom d'utilisateur</option>
                            <option value="email">Adresse e-mail</option>
                        </select>
                    </div>
                    <div class="form-group" id="email-user-search-group" style="display:none;">
                        <label for="email-user-search" id="email-user-search-label">Utilisateur :</label>
                        <input type="text" id="email-user-search" name="email_user_search" placeholder="Entrez le nom d'utilisateur ou l'e-mail">
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-group">
                        <label for="email-subject-type">Sujet :</label>
                        <select id="email-subject-type" name="email_subject_type" required onchange="handleEmailSubjectTypeChange()">
                            <option value="other" selected>Autre</option>
                            <option value="privacy_policy">Mise à jour de la politique de confidentialité</option>
                        </select>
                    </div>
                </div>

                <div class="form-section" id="email-subject-custom-group" style="display:none;">
                    <div class="form-group">
                        <label for="email-subject-custom">Sujet (autre) :</label>
                        <input type="text" id="email-subject-custom" name="email_subject_custom" placeholder="Sujet de l'e-mail" maxlength="120">
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-group">
                        <label for="email-message">Message :</label>
                        <textarea id="email-message" name="email_message" rows="8" placeholder="Contenu de l'e-mail" required></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label" for="email-enable-popup">
                            <input type="checkbox" id="email-enable-popup" name="email_enable_popup" onchange="handleEmailPopupToggle()">
                            Activer un popup
                        </label>
                    </div>
                </div>

                <div class="form-section" id="email-popup-message-group" style="display:none;">
                    <div class="form-group">
                        <label for="email-popup-message">Message du popup :</label>
                        <textarea id="email-popup-message" rows="3" maxlength="500" placeholder="Message affiché dans le popup (max 500 caractères)" oninput="updateEmailPopupCharCount()"></textarea>
                        <span id="emailPopupCharCount" class="description-compteur">0 / 500</span>
                    </div>
                </div>

                <div class="form-section form-actions">
                    <button type="button" class="btn-add" onclick="sendEmail()">Envoyer</button>
                    <button type="button" class="btn-cancel" onclick="resetEmailForm()">Réinitialiser</button>
                </div>

                <div id="email-result" class="result-message" style="display:none;"></div>
            </form>
        </div>
    @endif

    <div id="studioConversionsModal" class="modal" style="display: none;">
        <div class="modal-content studio-conversions-modal">
            <div class="modal-header">
                <h3>🔄 Gestion des Conversions de Studios</h3>
                <span class="close" onclick="closeStudioConversionsModal()">&times;</span>
            </div>

            <div class="modal-body">
                <div class="conversions-info">
                    <p>Cette section permet de gérer les conversions automatiques des noms de studios pour éviter les doublons dans la base de données.</p>
                </div>

                <div class="add-conversion-section">
                    <h4>Ajouter une nouvelle conversion</h4>
                    <div class="conversion-form">
                        <div class="form-group">
                            <label for="conversion-key">Clé de conversion :</label>
                            <input type="text" id="conversion-key" placeholder="ex: walt-disney" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="conversion-patterns">Variantes (une par ligne) :</label>
                            <textarea id="conversion-patterns" rows="4" placeholder="Walt Disney&#10;Walt Disney Pictures&#10;WaltDisney&#10;Disney"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="conversion-target">Nom cible :</label>
                            <input type="text" id="conversion-target" placeholder="Walt Disney" maxlength="100">
                        </div>
                        <button type="button" class="btn-add-conversion" onclick="addConversion()">Ajouter la conversion</button>
                    </div>
                </div>

                <div class="existing-conversions-section">
                    <h4>Conversions existantes</h4>
                    <div id="conversions-list" class="conversions-list"></div>
                </div>

                <div class="test-conversion-section">
                    <h4>Tester une conversion</h4>
                    <div class="test-form">
                        <input type="text" id="test-studio-name" placeholder="Entrez un nom de studio à tester">
                        <button type="button" class="btn-test-conversion" onclick="testConversion()">Tester</button>
                        <div id="test-result" class="test-result"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeStudioConversionsModal()">Fermer</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const CSRF = @json($csrf);
        const routes = {
            pendingFilms: @json(route('administration.pending-films')),
            addFilm: @json(route('administration.add-film')),
            approve: @json(url('/administration/propositions')) + '/',
            reject: @json(url('/administration/propositions')) + '/',
            studiosByCategorie: @json(route('administration.studios-by-categorie')),
            autocompleteStudios: @json(route('administration.autocomplete.studios')),
            autocompleteAuteurs: @json(route('administration.autocomplete.auteurs')),
            deleteFilm: @json(url('/administration/films')) + '/',
            modifyFilm: @json(url('/administration/films')) + '/',
            sendNotification: @json(route('administration.send-notification')),
            sendEmail: @json(route('administration.send-email')),
            publishPrivacyPolicy: @json(route('administration.privacy-policy.publish')),
            home: @json(route('accueil')),
            confidentialite: @json(route('confidentialite')),
            studioConverter: @json(route('administration.studio-converter')),
        };

        let summaryExpanded = false;
        let pendingFilmsCache = [];
        let currentPendingFilm = null;

        document.addEventListener('DOMContentLoaded', function () {
            const summaryContent = document.getElementById('summary-content');
            summaryContent.style.display = 'none';
            loadPendingFilms();
            updateCharCount();
            updateNotificationCharCount();
            handleCategoryChange();
            updateNomFilmLabel();
            loadStudioConversions();
            handleEmailSubjectTypeChange();
            handleEmailPopupToggle();
        });

        function toggleSummary() {
            const summaryContent = document.getElementById('summary-content');
            const sidebar = document.getElementById('admin-summary-sidebar');
            summaryExpanded = !summaryExpanded;
            if (summaryExpanded) {
                summaryContent.style.display = 'block';
                sidebar.classList.add('expanded');
            } else {
                summaryContent.style.display = 'none';
                sidebar.classList.remove('expanded');
            }
        }

        function scrollToSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

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
                    if (typeof updateStudios === 'function') {
                        updateStudios();
                    }
                }
            } else {
                japanNotification.style.display = 'none';
            }
        }

        function handleAnimeTypeChange() {
            const animeType = document.getElementById('anime_type').value;
            const ordreSuiteLabel = document.getElementById("ordre_suite_label");
            const ordreSuiteInput = document.getElementById("ordre_suite");
            const saisonLabel = document.getElementById("saison_label");
            const saisonInput = document.getElementById("saison");
            const nbrEpisodeLabel = document.getElementById('nbrEpisode_label');
            const nbrEpisodeInput = document.getElementById('nbrEpisode');
            if (!nbrEpisodeLabel || !nbrEpisodeInput) return;

            if (animeType === 'Série') {
                if (ordreSuiteLabel && ordreSuiteInput) {
                    ordreSuiteLabel.style.display = "none";
                    ordreSuiteInput.style.display = "none";
                }
                if (saisonLabel && saisonInput) {
                    saisonLabel.style.display = "block";
                    saisonInput.style.display = "block";
                    saisonInput.required = true;
                }
                nbrEpisodeLabel.style.display = 'block';
                nbrEpisodeInput.style.display = 'block';
                nbrEpisodeInput.required = true;
            } else {
                if (ordreSuiteLabel && ordreSuiteInput) {
                    ordreSuiteLabel.style.display = "block";
                    ordreSuiteInput.style.display = "block";
                }
                if (saisonLabel && saisonInput) {
                    saisonLabel.style.display = "none";
                    saisonInput.style.display = "none";
                    saisonInput.required = false;
                }
                nbrEpisodeLabel.style.display = 'none';
                nbrEpisodeInput.style.display = 'none';
                nbrEpisodeInput.required = false;
            }

            handleCategoryChange();
        }

        function handleCategoryChange() {
            const categorieSelect = document.getElementById('categorie');
            const animeTypeSection = document.getElementById('anime-type-section');
            const animeTypeSelect = document.getElementById('anime_type');
            const ordreSuiteLabel = document.getElementById("ordre_suite_label");
            const ordreSuiteInput = document.getElementById("ordre_suite");
            const saisonLabel = document.getElementById("saison_label");
            const saisonInput = document.getElementById("saison");
            const nbrEpisodeLabel = document.getElementById("nbrEpisode_label");
            const nbrEpisodeInput = document.getElementById("nbrEpisode");
            if (!categorieSelect) return;

            const categorie = categorieSelect.value;
            const animeType = animeTypeSelect ? animeTypeSelect.value : '';
            const isSerie = categorie === "Série" || categorie === "Série d'Animation" || (categorie === 'Anime' && animeType === 'Série');
            if (animeTypeSection && animeTypeSelect) {
                if (categorie === 'Anime') {
                    animeTypeSection.style.display = 'block';
                } else {
                    animeTypeSection.style.display = 'none';
                    animeTypeSelect.value = '';
                    handleAnimeTypeChange();
                }
            }

            if (ordreSuiteLabel && ordreSuiteInput && saisonLabel && saisonInput && nbrEpisodeLabel && nbrEpisodeInput) {
                if (isSerie) {
                    ordreSuiteLabel.style.display = "none";
                    ordreSuiteInput.style.display = "none";
                    saisonLabel.style.display = "block";
                    saisonInput.style.display = "block";
                    saisonInput.required = true;
                    nbrEpisodeLabel.style.display = "block";
                    nbrEpisodeInput.style.display = "block";
                    nbrEpisodeInput.required = true;
                } else {
                    ordreSuiteLabel.style.display = "block";
                    ordreSuiteInput.style.display = "block";
                    saisonLabel.style.display = "none";
                    saisonInput.style.display = "none";
                    saisonInput.required = false;
                    nbrEpisodeLabel.style.display = "none";
                    nbrEpisodeInput.style.display = "none";
                    nbrEpisodeInput.required = false;
                }
            }
        }

        document.getElementById('categorie').addEventListener('change', handleCategoryChange);

        function updateNomFilmLabel() {
            const categorie = document.getElementById("categorie").value;
            const label = document.getElementById("nom_film_label");
            const input = document.getElementById("nom_film");
            if (!label || !input) return;

            if (categorie === "Série" || categorie === "Série d'Animation") {
                label.textContent = "Nom de la série :";
                input.placeholder = "Nom de la série (max 50 caractères)";
            } else {
                label.textContent = "Nom du film :";
                input.placeholder = "Nom du film (max 50 caractères)";
            }
        }
        document.getElementById("categorie").addEventListener("change", updateNomFilmLabel);

        async function updateStudios() {
            const categorie = document.getElementById("categorie").value;
            const studioSelect = document.getElementById("studio");
            if (!studioSelect) return;

            studioSelect.innerHTML = "<option value=''>Sélectionnez un studio</option><option value='autre'>Autre</option><option value='1'>Inconnu</option>";
            if (!categorie) return;

            try {
                const res = await fetch(routes.studiosByCategorie + '?categorie=' + encodeURIComponent(categorie), {
                    headers: { 'Accept': 'application/json' }
                });
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
        }

        function toggleAutreStudio() {
            const studioSelect = document.getElementById("studio");
            const autreStudioInput = document.getElementById("nouveau_studio");
            if (!studioSelect || !autreStudioInput) return;

            if (studioSelect.value === "autre") {
                autreStudioInput.style.display = "block";
                autreStudioInput.setAttribute("required", "required");
                setupAutocompleteAdmin(autreStudioInput, 'studios');
            } else {
                autreStudioInput.style.display = "none";
                autreStudioInput.removeAttribute("required");
                removeAutocompleteAdmin(autreStudioInput);
            }
        }

        function toggleAutreChamp(selectId, inputId) {
            const input = document.getElementById(inputId);
            const select = document.getElementById(selectId);
            if (!input || !select) return;

            if (select.value === 'autre') {
                input.style.display = 'block';
                if (inputId === 'nouveau_auteur') {
                    setupAutocompleteAdmin(input, 'auteurs');
                }
            } else {
                input.style.display = 'none';
                if (inputId === 'nouveau_auteur') {
                    removeAutocompleteAdmin(input);
                }
            }
        }
        document.getElementById('auteur').addEventListener('change', function () {
            toggleAutreChamp('auteur', 'nouveau_auteur');
        });

        function setupAutocompleteAdmin(input, type) {
            let timeout;
            let suggestionsList = document.getElementById(input.id + '_suggestions');
            if (!suggestionsList) {
                suggestionsList = document.createElement('ul');
                suggestionsList.id = input.id + '_suggestions';
                suggestionsList.className = 'autocomplete-suggestions';

                let container = input.parentElement.querySelector('.autocomplete-container');
                if (!container) {
                    container = document.createElement('div');
                    container.className = 'autocomplete-container';
                    input.parentElement.insertBefore(container, input);
                    container.appendChild(input);
                }
                container.appendChild(suggestionsList);
            }

            input.addEventListener('input', function () {
                clearTimeout(timeout);
                const query = this.value.trim();
                if (query.length < 2) {
                    suggestionsList.classList.remove('show');
                    return;
                }

                timeout = setTimeout(() => {
                    const categorie = document.getElementById('categorie').value;
                    let url = type === 'studios' ? routes.autocompleteStudios : routes.autocompleteAuteurs;
                    url += '?search=' + encodeURIComponent(query);
                    if (type === 'studios' && categorie) {
                        url += '&categorie=' + encodeURIComponent(categorie);
                    }
                    fetch(url, { headers: { 'Accept': 'application/json' }})
                        .then(r => r.json())
                        .then(data => {
                            suggestionsList.innerHTML = '';
                            if (Array.isArray(data) && data.length > 0) {
                                data.forEach(item => {
                                    const li = document.createElement('li');
                                    li.textContent = item;
                                    li.className = 'autocomplete-suggestion';
                                    li.addEventListener('click', function () {
                                        input.value = this.textContent;
                                        suggestionsList.classList.remove('show');
                                    });
                                    suggestionsList.appendChild(li);
                                });
                                suggestionsList.classList.add('show');
                            } else {
                                const li = document.createElement('li');
                                li.textContent = 'Aucun résultat trouvé';
                                li.className = 'autocomplete-no-results';
                                suggestionsList.appendChild(li);
                                suggestionsList.classList.add('show');
                            }
                        })
                        .catch(() => {
                            suggestionsList.classList.remove('show');
                        });
                }, 300);
            });

            document.addEventListener('click', function (e) {
                if (!input.contains(e.target) && !suggestionsList.contains(e.target)) {
                    suggestionsList.classList.remove('show');
                }
            });
        }

        function removeAutocompleteAdmin(input) {
            const suggestionsList = document.getElementById(input.id + '_suggestions');
            if (suggestionsList) {
                suggestionsList.classList.remove('show');
            }
        }

        document.getElementById('filmForm').addEventListener('submit', function (event) {
            event.preventDefault();

            const formData = new FormData(this);
            const sousGenresCoches = document.querySelectorAll('input[name="sous_genres[]"]:checked');
            if (sousGenresCoches.length === 0) {
                document.getElementById("sous-genre-warning").style.display = "block";
                document.getElementById("sous-genre-warning").textContent = "⚠️ Vous devez sélectionner au moins un sous-genre.";
                return;
            }
            document.getElementById("sous-genre-warning").style.display = "none";

            fetch(routes.addFilm, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            })
                .then(r => r.json())
                .then(data => {
                    const notificationDiv = document.getElementById("notification");
                    if (data.success) {
                        notificationDiv.innerHTML = '<div class="success">✅ ' + (data.message || 'Ajout réussi') + '</div>';
                        this.reset();
                        document.querySelectorAll("#sous-genres-container input[type='checkbox']").forEach(cb => cb.checked = false);
                        updateCharCount();
                        setTimeout(() => { location.reload(); }, 500);
                    } else {
                        const msg = data.error || (data.errors ? Object.values(data.errors)[0][0] : "Une erreur inconnue est survenue.");
                        notificationDiv.innerHTML = '<div class="error">❌ ' + msg + '</div>';
                    }
                })
                .catch(() => {
                    document.getElementById('notification').innerHTML = '<div class="error">⚠️ Impossible de contacter le serveur.</div>';
                });
        });

        function filterFilms() {
            const input = document.getElementById("searchBar");
            const filter = input.value.toLowerCase();
            const filmItems = document.querySelectorAll(".film-item");
            filmItems.forEach(film => {
                const text = film.textContent.toLowerCase();
                film.style.display = text.includes(filter) ? "" : "none";
            });
        }

        function showModifyForm(id) {
            document.getElementById('modify-form-' + id).style.display = 'block';
            document.getElementById('modify-form-' + id + '-form').style.display = 'block';
        }

        function hideModifyForm(id) {
            document.getElementById('modify-form-' + id).style.display = 'none';
            document.getElementById('modify-form-' + id + '-form').style.display = 'none';
        }

        function modifyFilm(id) {
            const form = document.getElementById("modify-form-" + id + "-form");
            const formData = new FormData(form);
            fetch(routes.modifyFilm + id + '/modify', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        customSuccess("Modification réussie !", "Modification du film");
                        setTimeout(() => { location.reload(); }, 300);
                    } else {
                        customAlert("Erreur : " + (data.error || "Aucune réponse"), "Erreur de modification");
                    }
                })
                .catch(() => {
                    customAlert("Erreur lors de la modification du film.", "Erreur");
                });
        }

        async function deleteFilm(id) {
            const confirmed = await customDanger('Voulez-vous vraiment supprimer ce film ?', 'Confirmation de suppression');
            if (!confirmed) return;
            fetch(routes.deleteFilm + id + '/delete', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
            })
                .then(response => {
                    if (response.status === 204) {
                        const filmElement = document.querySelector(`.film-item[data-id="${id}"]`);
                        if (filmElement) filmElement.remove();
                        customSuccess('Film supprimé avec succès !', 'Suppression réussie');
                    } else {
                        return response.json().then(data => {
                            customAlert('Erreur : ' + (data.error || 'Erreur lors de la suppression.'), 'Erreur de suppression');
                        });
                    }
                })
                .catch(() => {
                    customAlert("Une erreur s'est produite lors de la suppression.", 'Erreur');
                });
        }

        function loadPendingFilms() {
            fetch(routes.pendingFilms, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('pending-films-count').textContent = 'Erreur de chargement';
                        return;
                    }
                    pendingFilmsCache = data.films || [];
                    document.getElementById('pending-films-count').textContent = `${data.count} film(s) en attente d'approbation`;
                    displayPendingFilms(pendingFilmsCache);
                })
                .catch(() => {
                    document.getElementById('pending-films-count').textContent = 'Erreur de chargement';
                });
        }

        function displayPendingFilms(films) {
            const container = document.getElementById('pending-films-list');
            if (!Array.isArray(films) || films.length === 0) {
                container.innerHTML = '<p class="no-pending-films">Aucun film en attente d\'approbation.</p>';
                return;
            }
            let html = '<div class="pending-films-grid">';
            films.forEach(film => {
                html += `
                    <div class="pending-film-card" onclick="openPendingFilmModal(${film.id})">
                        <div class="pending-film-image">
                            <img src="${film.image_path}" alt="${escapeHtml(film.nom_film)}" onerror="this.src='{{ asset('img/default-film.png') }}'">
                        </div>
                        <div class="pending-film-info">
                            <h4>${escapeHtml(film.nom_film)}</h4>
                            <p><strong>Catégorie:</strong> ${escapeHtml(film.categorie)}</p>
                            <p><strong>Année:</strong> ${film.date_sortie}</p>
                            <p><strong>Proposé par:</strong> ${escapeHtml(film.propose_par_pseudo)}</p>
                            <p><strong>Date:</strong> ${escapeHtml(film.date_proposition_formatted)}</p>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        function openPendingFilmModal(filmId) {
            const film = pendingFilmsCache.find(f => f.id == filmId);
            if (!film) return;
            currentPendingFilm = film;
            populateModal(film);
            document.getElementById('pendingFilmModal').style.display = 'block';
        }

        function populateModal(film) {
            document.getElementById('modal-film-title').textContent = `Examiner: ${film.nom_film}`;
            document.getElementById('modal-nom-film').value = film.nom_film;
            document.getElementById('modal-categorie').value = film.categorie;
            document.getElementById('modal-description').value = film.description || '';
            document.getElementById('modal-date-sortie').value = film.date_sortie;
            document.getElementById('modal-ordre-suite').value = film.ordre_suite || '';
            document.getElementById('modal-saison').value = film.saison || '';
            document.getElementById('modal-nbrEpisode').value = film.nbrEpisode || '';
            document.getElementById('modal-studio').textContent = film.studio_nom || 'Inconnu';
            document.getElementById('modal-auteur').textContent = film.auteur_nom || 'Inconnu';
            document.getElementById('modal-pays').textContent = film.pays_nom || 'Inconnu';
            document.getElementById('modal-sous-genres').textContent = Array.isArray(film.sous_genres) ? film.sous_genres.join(', ') : '';
            document.getElementById('modal-propose-par').textContent = film.propose_par_pseudo || '';
            document.getElementById('modal-date-proposition').textContent = film.date_proposition_formatted || '';
            document.getElementById('modal-film-image').src = film.image_path || '';
            document.getElementById('modal-commentaire-admin').value = '';
            document.getElementById('rejection-reason-row').style.display = 'none';
            document.getElementById('modal-raison-rejet').value = '';

            const isSerie = String(film.categorie || '').includes('Série');
            document.getElementById('modal-saison-row').style.display = isSerie ? 'block' : 'none';
            document.getElementById('modal-episodes-row').style.display = isSerie ? 'block' : 'none';
        }

        function closePendingFilmModal() {
            document.getElementById('pendingFilmModal').style.display = 'none';
            document.getElementById('rejection-reason-row').style.display = 'none';
            document.getElementById('modal-raison-rejet').value = '';
            currentPendingFilm = null;
        }

        async function approveFilm() {
            if (!currentPendingFilm) return;
            const confirmed = await customConfirm('Êtes-vous sûr de vouloir approuver ce film ?', 'Confirmation d\'approbation');
            if (!confirmed) return;

            const id = currentPendingFilm.id;
            const formData = new FormData();
            formData.append('nom_film', document.getElementById('modal-nom-film').value);
            formData.append('categorie', document.getElementById('modal-categorie').value);
            formData.append('description', document.getElementById('modal-description').value);
            formData.append('date_sortie', document.getElementById('modal-date-sortie').value);
            formData.append('ordre_suite', document.getElementById('modal-ordre-suite').value);
            formData.append('saison', document.getElementById('modal-saison').value);
            formData.append('nbrEpisode', document.getElementById('modal-nbrEpisode').value);
            formData.append('commentaire_admin', document.getElementById('modal-commentaire-admin').value);

            fetch(routes.approve + id + '/approve', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        customSuccess('Film approuvé avec succès !', 'Approbation réussie');
                        closePendingFilmModal();
                        loadPendingFilms();
                        setTimeout(() => { location.reload(); }, 300);
                    } else {
                        customAlert('Erreur: ' + (data.error || 'Erreur'), 'Erreur d\'approbation');
                    }
                })
                .catch(() => {
                    customAlert('Erreur lors de l\'approbation', 'Erreur');
                });
        }

        async function rejectFilm() {
            if (!currentPendingFilm) return;
            document.getElementById('rejection-reason-row').style.display = 'block';

            const raisonRejet = document.getElementById('modal-raison-rejet').value;
            const commentaire = document.getElementById('modal-commentaire-admin').value;
            if (!raisonRejet) {
                customAlert('Veuillez sélectionner une raison de rejet.', 'Raison manquante');
                return;
            }
            if (raisonRejet === 'Autre' && !commentaire.trim()) {
                customAlert('Un commentaire est requis lorsque vous sélectionnez "Autre" comme raison.', 'Commentaire requis');
                return;
            }

            const confirmed = await customDanger('Êtes-vous sûr de vouloir rejeter ce film ?', 'Confirmation de rejet');
            if (!confirmed) return;

            const id = currentPendingFilm.id;
            const formData = new FormData();
            formData.append('raison_rejet', raisonRejet);
            formData.append('commentaire_admin', commentaire);

            fetch(routes.reject + id + '/reject', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        customSuccess('Film rejeté.');
                        closePendingFilmModal();
                        loadPendingFilms();
                    } else {
                        customAlert('Erreur: ' + (data.error || 'Erreur'));
                    }
                })
                .catch(() => {
                    customAlert('Erreur lors du rejet');
                });
        }

        window.onclick = function (event) {
            const modal = document.getElementById('pendingFilmModal');
            if (event.target === modal) {
                closePendingFilmModal();
            }
            const scModal = document.getElementById('studioConversionsModal');
            if (event.target === scModal) {
                closeStudioConversionsModal();
            }
        }

        function handleRecipientTypeChange() {
            const recipientType = document.getElementById('recipient-type').value;
            const titleSelection = document.getElementById('title-selection');
            const specificUserSelection = document.getElementById('specific-user-selection');
            titleSelection.style.display = 'none';
            specificUserSelection.style.display = 'none';
            document.getElementById('user-title').value = '';
            document.getElementById('search-type').value = '';
            document.getElementById('user-search').value = '';
            document.getElementById('user-search-group').style.display = 'none';
            if (recipientType === 'title') {
                titleSelection.style.display = 'block';
            } else if (recipientType === 'specific') {
                specificUserSelection.style.display = 'block';
            }
        }

        function handleSearchTypeChange() {
            const searchType = document.getElementById('search-type').value;
            const userSearchGroup = document.getElementById('user-search-group');
            const userSearchLabel = document.getElementById('user-search-label');
            const userSearchInput = document.getElementById('user-search');
            if (searchType) {
                userSearchGroup.style.display = 'block';
                if (searchType === 'username') {
                    userSearchLabel.textContent = "Nom d'utilisateur :";
                    userSearchInput.placeholder = "Entrez le nom d'utilisateur";
                } else {
                    userSearchLabel.textContent = 'Adresse e-mail :';
                    userSearchInput.placeholder = "Entrez l'adresse e-mail";
                }
            } else {
                userSearchGroup.style.display = 'none';
            }
            userSearchInput.value = '';
        }

        function updateNotificationCharCount() {
            const textarea = document.getElementById('notification-message');
            const counter = document.getElementById('notificationCharCount');
            if (!textarea || !counter) return;
            const currentLength = textarea.value.length;
            const maxLength = 500;
            counter.textContent = currentLength + ' / ' + maxLength;
            if (currentLength > maxLength * 0.9) {
                counter.style.color = '#e74c3c';
            } else if (currentLength > maxLength * 0.7) {
                counter.style.color = '#f39c12';
            } else {
                counter.style.color = '#7f8c8d';
            }
        }

        function sendNotification() {
            const recipientType = document.getElementById('recipient-type').value;
            const notificationTitle = document.getElementById('notification-title').value.trim();
            const notificationMessage = document.getElementById('notification-message').value.trim();
            if (!recipientType) {
                showNotificationResult('Veuillez sélectionner un type de destinataire.', 'error');
                return;
            }
            if (!notificationTitle) {
                showNotificationResult('Veuillez saisir un titre pour la notification.', 'error');
                return;
            }
            if (!notificationMessage) {
                showNotificationResult('Veuillez saisir un message pour la notification.', 'error');
                return;
            }

            if (recipientType === 'title') {
                const userTitle = document.getElementById('user-title').value;
                if (!userTitle) {
                    showNotificationResult('Veuillez sélectionner un titre d\'utilisateur.', 'error');
                    return;
                }
            }
            if (recipientType === 'specific') {
                const searchType = document.getElementById('search-type').value;
                const userSearch = document.getElementById('user-search').value.trim();
                if (!searchType) {
                    showNotificationResult('Veuillez sélectionner un type de recherche.', 'error');
                    return;
                }
                if (!userSearch) {
                    showNotificationResult('Veuillez saisir un nom d\'utilisateur ou une adresse e-mail.', 'error');
                    return;
                }
                if (searchType === 'email' && !isValidEmail(userSearch)) {
                    showNotificationResult('Veuillez saisir une adresse e-mail valide.', 'error');
                    return;
                }
            }

            const formData = new FormData();
            formData.append('recipient_type', recipientType);
            formData.append('notification_title', notificationTitle);
            formData.append('notification_message', notificationMessage);
            if (recipientType === 'title') {
                formData.append('user_title', document.getElementById('user-title').value);
            } else if (recipientType === 'specific') {
                formData.append('search_type', document.getElementById('search-type').value);
                formData.append('user_search', document.getElementById('user-search').value.trim());
            }

            showNotificationResult('Envoi en cours...', 'info');
            fetch(routes.sendNotification, { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showNotificationResult(data.message, 'success');
                        resetNotificationForm();
                    } else {
                        showNotificationResult(data.message || data.error || 'Erreur', 'error');
                    }
                })
                .catch(() => {
                    showNotificationResult('Une erreur est survenue lors de l\'envoi de la notification.', 'error');
                });
        }

        function showNotificationResult(message, type) {
            const resultDiv = document.getElementById('notification-result');
            resultDiv.textContent = message;
            resultDiv.className = 'result-message ' + type;
            resultDiv.style.display = 'block';
            resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            if (type === 'success') {
                setTimeout(() => { resultDiv.style.display = 'none'; }, 5000);
            }
        }

        function resetNotificationForm() {
            document.getElementById('notificationForm').reset();
            document.getElementById('title-selection').style.display = 'none';
            document.getElementById('specific-user-selection').style.display = 'none';
            document.getElementById('user-search-group').style.display = 'none';
            document.getElementById('notification-result').style.display = 'none';
            updateNotificationCharCount();
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function handleEmailRecipientTypeChange() {
            const recipientType = document.getElementById('email-recipient-type').value;
            const titleSelection = document.getElementById('email-title-selection');
            const specificUserSelection = document.getElementById('email-specific-user-selection');
            titleSelection.style.display = 'none';
            specificUserSelection.style.display = 'none';
            document.getElementById('email-user-title').value = '';
            document.getElementById('email-search-type').value = '';
            document.getElementById('email-user-search').value = '';
            document.getElementById('email-user-search-group').style.display = 'none';
            if (recipientType === 'title') {
                titleSelection.style.display = 'block';
            } else if (recipientType === 'specific') {
                specificUserSelection.style.display = 'block';
            }
        }

        function handleEmailSearchTypeChange() {
            const searchType = document.getElementById('email-search-type').value;
            const userSearchGroup = document.getElementById('email-user-search-group');
            const userSearchLabel = document.getElementById('email-user-search-label');
            const userSearchInput = document.getElementById('email-user-search');
            if (searchType) {
                userSearchGroup.style.display = 'block';
                if (searchType === 'username') {
                    userSearchLabel.textContent = "Nom d'utilisateur :";
                    userSearchInput.placeholder = "Entrez le nom d'utilisateur";
                } else {
                    userSearchLabel.textContent = 'Adresse e-mail :';
                    userSearchInput.placeholder = "Entrez l'adresse e-mail";
                }
            } else {
                userSearchGroup.style.display = 'none';
            }
            userSearchInput.value = '';
        }

        function privacyPolicyEmailTemplate() {
            const lines = [
                'Bonjour,',
                '',
                'Nous vous informons que la politique de confidentialité de Lupistar a été mise à jour.',
                '',
                `Pour la consulter, cliquez sur ce lien : ${routes.confidentialite}`,
                '',
                "Si un popup d'information est activé, il apparaîtra lors de votre prochaine visite.",
                '',
                `Accéder au site : ${routes.home}`,
                '',
                'Merci,',
                'Lupistar',
            ];
            return lines.join('\n');
        }

        function handleEmailSubjectTypeChange() {
            const type = document.getElementById('email-subject-type')?.value || 'other';
            const customGroup = document.getElementById('email-subject-custom-group');
            const subjectCustom = document.getElementById('email-subject-custom');
            const message = document.getElementById('email-message');

            if (type === 'other') {
                if (customGroup) customGroup.style.display = 'block';
                if (subjectCustom) subjectCustom.required = true;
            } else {
                if (customGroup) customGroup.style.display = 'none';
                if (subjectCustom) {
                    subjectCustom.required = false;
                    subjectCustom.value = '';
                }
                if (message) {
                    message.value = privacyPolicyEmailTemplate();
                }
            }
        }

        function updateEmailPopupCharCount() {
            const textarea = document.getElementById('email-popup-message');
            const counter = document.getElementById('emailPopupCharCount');
            if (!textarea || !counter) return;
            counter.textContent = `${textarea.value.length} / 500`;
        }

        function handleEmailPopupToggle() {
            const enabled = document.getElementById('email-enable-popup')?.checked || false;
            const group = document.getElementById('email-popup-message-group');
            if (!group) return;
            group.style.display = enabled ? 'block' : 'none';
            if (!enabled) {
                const textarea = document.getElementById('email-popup-message');
                if (textarea) textarea.value = '';
            }
            updateEmailPopupCharCount();
        }

        async function doPublishPrivacyPolicy(messageText) {
            const formData = new FormData();
            formData.append('message', messageText || '');
            try {
                const r = await fetch(routes.publishPrivacyPolicy, { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
                const data = await r.json();
                return data;
            } catch (e) {
                return { success: false };
            }
        }

        function sendEmail() {
            const recipientType = document.getElementById('email-recipient-type')?.value || '';
            const subjectType = document.getElementById('email-subject-type')?.value || 'other';
            const subjectCustom = document.getElementById('email-subject-custom')?.value.trim() || '';
            const message = document.getElementById('email-message')?.value.trim() || '';
            const enablePopup = document.getElementById('email-enable-popup')?.checked || false;
            const popupMessage = document.getElementById('email-popup-message')?.value.trim() || '';

            const subject = subjectType === 'privacy_policy'
                ? 'Mise à jour de la politique de confidentialité'
                : subjectCustom;

            if (!recipientType) {
                showEmailResult('Veuillez sélectionner un type de destinataire.', 'error');
                return;
            }
            if (!subject) {
                showEmailResult("Veuillez saisir un sujet d'e-mail.", 'error');
                return;
            }
            if (!message) {
                showEmailResult("Veuillez saisir un message d'e-mail.", 'error');
                return;
            }

            if (recipientType === 'title') {
                const userTitle = document.getElementById('email-user-title').value;
                if (!userTitle) {
                    showEmailResult('Veuillez sélectionner un titre d\'utilisateur.', 'error');
                    return;
                }
            }
            if (recipientType === 'specific') {
                const searchType = document.getElementById('email-search-type').value;
                const userSearch = document.getElementById('email-user-search').value.trim();
                if (!searchType) {
                    showEmailResult('Veuillez sélectionner un type de recherche.', 'error');
                    return;
                }
                if (!userSearch) {
                    showEmailResult('Veuillez saisir un nom d\'utilisateur ou une adresse e-mail.', 'error');
                    return;
                }
                if (searchType === 'email' && !isValidEmail(userSearch)) {
                    showEmailResult('Veuillez saisir une adresse e-mail valide.', 'error');
                    return;
                }
            }

            const sendNow = async () => {
                if (enablePopup) {
                    showEmailResult('Publication de la mise à jour...', 'info');
                    const publish = await doPublishPrivacyPolicy(popupMessage);
                    if (!publish?.success) {
                        showEmailResult(publish?.message || publish?.error || 'Erreur lors de la publication de la mise à jour.', 'error');
                        return;
                    }
                }

                const formData = new FormData();
                formData.append('recipient_type', recipientType);
                formData.append('email_subject', subject);
                formData.append('email_message', message);
                if (recipientType === 'title') {
                    formData.append('user_title', document.getElementById('email-user-title').value);
                } else if (recipientType === 'specific') {
                    formData.append('search_type', document.getElementById('email-search-type').value);
                    formData.append('user_search', document.getElementById('email-user-search').value.trim());
                }

                showEmailResult('Envoi en cours...', 'info');
                try {
                    const r = await fetch(routes.sendEmail, { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
                    const data = await r.json();
                    if (data.success) {
                        showEmailResult(data.message, 'success');
                        resetEmailForm();
                    } else {
                        showEmailResult(data.message || data.error || 'Erreur', 'error');
                    }
                } catch (e) {
                    showEmailResult('Une erreur est survenue lors de l\'envoi de l\'e-mail.', 'error');
                }
            };

            if (enablePopup && window.customConfirm) {
                window.customConfirm('Activer le popup (mise à jour) et envoyer l’e-mail ?', 'Confirmer')
                    .then(ok => { if (ok) sendNow(); });
            } else {
                sendNow();
            }
        }

        function showEmailResult(message, type) {
            const resultDiv = document.getElementById('email-result');
            if (!resultDiv) return;
            resultDiv.textContent = message;
            resultDiv.className = 'result-message ' + type;
            resultDiv.style.display = 'block';
            resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            if (type === 'success') {
                setTimeout(() => { resultDiv.style.display = 'none'; }, 7000);
            }
        }

        function resetEmailForm() {
            const form = document.getElementById('emailForm');
            if (!form) return;
            form.reset();
            const titleSelection = document.getElementById('email-title-selection');
            const specificUserSelection = document.getElementById('email-specific-user-selection');
            const userSearchGroup = document.getElementById('email-user-search-group');
            const subjectCustomGroup = document.getElementById('email-subject-custom-group');
            const popupGroup = document.getElementById('email-popup-message-group');
            const result = document.getElementById('email-result');
            if (titleSelection) titleSelection.style.display = 'none';
            if (specificUserSelection) specificUserSelection.style.display = 'none';
            if (userSearchGroup) userSearchGroup.style.display = 'none';
            if (subjectCustomGroup) subjectCustomGroup.style.display = 'none';
            if (popupGroup) popupGroup.style.display = 'none';
            if (result) result.style.display = 'none';
            handleEmailSubjectTypeChange();
            handleEmailPopupToggle();
        }

        function openStudioConversionsModal() {
            document.getElementById('studioConversionsModal').style.display = 'block';
            loadStudioConversions();
        }

        function closeStudioConversionsModal() {
            document.getElementById('studioConversionsModal').style.display = 'none';
        }

        function loadStudioConversions() {
            fetch(routes.studioConverter + '?action=list', { headers: { 'Accept': 'application/json' }})
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        displayConversions(data.conversions || {});
                    }
                })
                .catch(() => {});
        }

        function displayConversions(conversions) {
            const conversionsList = document.getElementById('conversions-list');
            conversionsList.innerHTML = '';
            Object.keys(conversions).forEach(key => {
                const conversion = conversions[key];
                const conversionDiv = document.createElement('div');
                conversionDiv.className = 'conversion-item';
                conversionDiv.innerHTML = `
                    <div class="conversion-header">
                        <strong>${escapeHtml(key)}</strong> → <span class="target-name">${escapeHtml(conversion.target || '')}</span>
                        <button class="btn-delete-conversion" onclick="deleteConversion('${escapeHtml(key)}')">🗑️</button>
                    </div>
                    <div class="conversion-patterns">
                        Variantes: ${(conversion.patterns || []).map(escapeHtml).join(', ')}
                    </div>
                `;
                conversionsList.appendChild(conversionDiv);
            });
        }

        function addConversion() {
            const key = document.getElementById('conversion-key').value.trim();
            const patternsText = document.getElementById('conversion-patterns').value.trim();
            const target = document.getElementById('conversion-target').value.trim();
            if (!key || !patternsText || !target) {
                customAlert('Veuillez remplir tous les champs.', 'Champs manquants');
                return;
            }
            const patterns = patternsText.split('\n').map(p => p.trim()).filter(p => p);
            fetch(routes.studioConverter, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ action: 'add_conversion', key, patterns, target })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('conversion-key').value = '';
                        document.getElementById('conversion-patterns').value = '';
                        document.getElementById('conversion-target').value = '';
                        loadStudioConversions();
                        customSuccess('Conversion ajoutée avec succès!');
                    } else {
                        customAlert('Erreur: ' + (data.error || 'Erreur'), "Erreur d'ajout");
                    }
                })
                .catch(() => {
                    customAlert("Erreur lors de l'ajout de la conversion.", 'Erreur');
                });
        }

        async function deleteConversion(key) {
            const confirmed = await customDanger('Êtes-vous sûr de vouloir supprimer cette conversion?', 'Confirmation de suppression');
            if (!confirmed) return;
            fetch(routes.studioConverter, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ action: 'remove_conversion', key })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        loadStudioConversions();
                        customSuccess('Conversion supprimée avec succès!');
                    } else {
                        customAlert('Erreur: ' + (data.error || 'Erreur'), 'Erreur de suppression');
                    }
                })
                .catch(() => {
                    customAlert('Erreur lors de la suppression de la conversion.', 'Erreur');
                });
        }

        function testConversion() {
            const testName = document.getElementById('test-studio-name').value.trim();
            if (!testName) {
                customAlert('Veuillez entrer un nom de studio à tester.', 'Nom manquant');
                return;
            }
            const formData = new FormData();
            formData.append('action', 'convert_studio');
            formData.append('studio_name', testName);
            fetch(routes.studioConverter, { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const resultDiv = document.getElementById('test-result');
                        if (data.converted !== data.original) {
                            resultDiv.innerHTML = `<span class="test-success">✅ "${escapeHtml(data.original)}" → "${escapeHtml(data.converted)}"</span>`;
                        } else {
                            resultDiv.innerHTML = `<span class="test-no-change">ℹ️ "${escapeHtml(data.original)}" (aucune conversion trouvée)</span>`;
                        }
                    } else {
                        customAlert('Erreur: ' + (data.error || 'Erreur'), 'Erreur de test');
                    }
                })
                .catch(() => {
                    customAlert('Erreur lors du test de conversion.', 'Erreur');
                });
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
        }
    </script>
@endsection
