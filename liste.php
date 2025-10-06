<?php
session_start();
include './scripts-php/co-bdd.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

// Définir la page actuelle pour marquer l'onglet actif
$current_page = basename($_SERVER['PHP_SELF'], ".php");

// Récupération des paramètres GET via URL
$pageCourante = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Définir la catégorie par défaut en fonction des préférences utilisateur
$defaultCategory = 'Animation'; // Valeur par défaut de base
if (isset($_SESSION['user_id'])) {
    // Inclure le fichier de gestion des préférences pour récupérer l'ordre
    include_once './scripts-php/user-preferences.php';
    $userCategories = getUserCategoriesOrder($_SESSION['user_id']);
    if (!empty($userCategories)) {
        $defaultCategory = $userCategories[0]; // Premier élément de la liste personnalisée
    }
}

$category = isset($_GET['categorie']) ? $_GET['categorie'] : $defaultCategory;
$studio = isset($_GET['studio_id']) ? $_GET['studio_id'] : '';

// Récupérer les années disponibles pour les films
try {
    $sqlAnnees = "SELECT DISTINCT YEAR(date_sortie) AS annee FROM films ORDER BY annee ASC";
    $stmtAnnees = $pdo->query($sqlAnnees);
    
    $annees = [];
    while ($row = $stmtAnnees->fetch(PDO::FETCH_ASSOC)) {
        $annees[] = $row['annee'];
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des années : " . $e->getMessage());
    $annees = [];
}

// Récupérer les studios distincts
try {
    $sqlStudios = "SELECT DISTINCT studio_id FROM films WHERE categorie = ?";
    $stmtStudios = $pdo->prepare($sqlStudios);
    $stmtStudios->execute([$category]);
    
    $studios = [];
    while ($row = $stmtStudios->fetch(PDO::FETCH_ASSOC)) {
        $studios[] = $row['studio_id'];
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des studios : " . $e->getMessage());
    $studios = [];
}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="./css/style-navigation.css">
    <link rel="stylesheet" href="./css/style-liste.css">
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico">
    <title>Liste des films</title>
</head>
<body>
    <div class="modal-overlay" id="film-modal">
        <div id="modal-content">
            <!-- La fênettre modal du film sera injecté ici via AJAX (film-detail-modal.php) -->
        </div>
    </div>

    <div class="background"></div>
    <header>
        <nav class="navbar">
            <img src="./gif/logogif.GIF" alt="Logo" class="gif">
            <ul class="menu">
                <a class="btn <?php if ($current_page == 'index') echo 'active'; ?>" href="./index.php">Accueil</a>
                <a class="btn <?php if ($current_page == 'liste') echo 'active'; ?>" href="./liste.php">Liste</a>
                <a class="btn <?php if ($current_page == 'ma-liste') echo 'active'; ?>" href="./ma-liste.php">Ma Liste</a>
                <a class="btn <?php if ($current_page == 'forum') echo 'active'; ?>" id="btn4" href="./forum.php">Forum</a>
            </ul>
            <div class="profil">
                <?php include './scripts-php/img-profil.php'; ?>
                <div class="menu-deroulant">
                    <?php include './scripts-php/menu-profil.php'; ?>
                </div>
            </div>
        </nav>
    </header>

    <div class="tab">
        <?php
        // Vérifier si l'utilisateur est connecté et récupérer ses préférences
        $categories = ["Animation", "Anime", "Série d'Animation", "Film", "Série"]; // Ordre par défaut
        
        if (isset($_SESSION['user_id'])) {
            // Inclure le fichier de gestion des préférences
            include_once './scripts-php/user-preferences.php';
            
            // Récupérer l'ordre personnalisé des catégories
            $userCategories = getUserCategoriesOrder($_SESSION['user_id']);
            if (!empty($userCategories)) {
                $categories = $userCategories;
            }
        }
        
        foreach ($categories as $categoryName) {
            $activeClass = ($categoryName == $category) ? 'active' : '';
            // Utiliser addslashes pour échapper les apostrophes dans l'attribut onclick (JavaScript)
            $escapedCategoryNameForJS = addslashes($categoryName);
            // Utiliser htmlspecialchars pour afficher correctement les caractères spéciaux dans le texte HTML
            $escapedCategoryNameForHTML = htmlspecialchars($categoryName, ENT_QUOTES);
            echo "<button class='tablinks $activeClass' onclick=\"openTab(event, '$escapedCategoryNameForJS')\">$escapedCategoryNameForHTML</button>";
        }
        ?>
    </div>

    <div class="search-bar-container">
        
        <div class="filter-container">
            <!-- Section supérieure avec barre de recherche à gauche et statistiques à droite -->
            <div class="top-section">
                <div class="search-section-left">
                    <input type="text" id="search-bar" placeholder="Rechercher un film..." onkeyup="rechercherFilms()" maxlength="50">
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
            
            <!-- Cadre contenant tous les filtres -->
            <div class="filters-frame">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="studio-filter">Filtrer par Studio:</label>
                        <select id="studio-filter" name="studio">
                            <option value="Tous les studios">Tous les studios</option>
                            <?php foreach ($studios as $studioOption) {
                                $selected = ($studio === $studioOption) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($studioOption) . "' $selected>" . htmlspecialchars($studioOption) . "</option>";
                            } ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="annee-filter">Filtrer par Année:</label>
                        <select id="annee-filter" name="annee">
                            <option value="">Toutes les années</option>
                            <?php foreach ($annees as $year) {
                                echo "<option value='$year'>$year</option>";
                            } ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="note-filter">Filtrer par Note (moyenne):</label>
                        <select id="note-filter" name="note">
                            <option value="">Toutes les notes</option>
                            <?php for ($i = 1; $i <= 10; $i++) {
                                echo "<option value='$i'>$i et plus</option>";
                            } ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="statut-filter">Statut de visionnage:</label>
                        <select id="statut-filter" name="statut">
                            <option value="">Vus/Non vus</option>
                            <option value="vu">Vus</option>
                            <option value="non_vu">Non vus</option>
                        </select>
                    </div>

                    <div class="filter-group" id="pays-filter-group">
                        <label for="pays-filter">Filtrer par Pays:</label>
                        <select id="pays-filter" name="pays">
                            <option value="">Tous les pays</option>
                            <!-- Options chargées dynamiquement par JavaScript -->
                        </select>
                    </div>

                    <!-- Filtre Type Film/Série pour Anime uniquement -->
                    <div class="filter-group" id="type-filter-group" style="display: none;">
                        <label for="type-filter">Type:</label>
                        <select id="type-filter" name="type">
                            <option value="">Tous confondus</option>
                            <option value="film">Film</option>
                            <option value="serie">Série</option>
                        </select>
                    </div>

                    <!-- Filtre par nombre d'épisodes -->
                    <div class="filter-group" id="episodes-filter-group" style="display: none;">
                        <label for="episodes-filter">Nombre d'épisodes:</label>
                        <select id="episodes-filter" name="episodes">
                            <option value="">Tous les épisodes</option>
                            <option value="0-13">0 à 13 épisodes</option>
                            <option value="0-24">0 à 24 épisodes</option>
                            <option value="13-24">13 à 24 épisodes</option>
                            <option value="24+">Plus de 24 épisodes</option>
                        </select>
                    </div>
                </div>
                
                <!-- Boutons d'action -->
                <div class="action-buttons-container">
                    <button onclick="rechercherFilms()" class="search-btn">Rechercher</button>
                    <button onclick="reinitialiserFiltres()" class="reset-btn">Réinitialiser</button>
                </div>
            </div>
        </div>
    </div>

    <div id="tabcontent" class="tabcontent">
        <!-- Contenu chargé par AJAX -->
    </div>

    <footer>
        <p>&copy; 2025 lupistar.fr — Tous droits réservés.</p>
        <p>Les illustrations sont la propriété de leurs auteurs et éditeurs respectifs.</p>
        <nav>
            <a href="/mentions-legales.php">Mentions légales</a> | 
            <a href="/confidentialite.php">Politique de confidentialité</a>
        </nav>
    </footer>

    <script>
        function resetFilters() {
            document.getElementById("studio-filter").value = 'Tous les studios';
            document.getElementById("annee-filter").value = '';
            document.getElementById("note-filter").value = '';
            document.getElementById("statut-filter").value = '';
            document.getElementById("pays-filter").value = '';
            document.getElementById("type-filter").value = '';
            document.getElementById("episodes-filter").value = '';
            document.getElementById("search-bar").value = '';
        }

        function reinitialiserFiltres() {
            resetFilters();
            rechercherFilms(); // Relancer la recherche avec les filtres réinitialisés
        }

        function loadStatistics(category) {
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "./scripts-php/statistics.php?categorie=" + encodeURIComponent(category));
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    document.getElementById("stat-animation").textContent = response.total_films || '-';
                    document.getElementById("stat-studios").textContent = response.top_studios || '-';
                    document.getElementById("stat-decade").textContent = response.best_decade || '-';
                }
            };
            xhr.send();
        }

        function loadFilters(category) {
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "./scripts-php/filters.php?categorie=" + encodeURIComponent(category));
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    var studioFilter = document.getElementById("studio-filter");
                    var anneeFilter = document.getElementById("annee-filter");
                    var paysFilter = document.getElementById("pays-filter");

                    // Mettre à jour le filtre de studio
                    studioFilter.innerHTML = '<option value="Tous les studios">Tous les studios</option>';
                    response.studios.forEach(function(studio) {
                        studioFilter.innerHTML += '<option value="' + studio + '">' + studio + '</option>';
                    });

                    // Mettre à jour le filtre d'année
                    anneeFilter.innerHTML = '<option value="">Toutes les années</option>';
                    response.annees.forEach(function(annee) {
                        anneeFilter.innerHTML += '<option value="' + annee + '">' + annee + '</option>';
                    });

                    // Mettre à jour le filtre de pays
                    paysFilter.innerHTML = '<option value="">Tous les pays</option>';
                    response.pays.forEach(function(pays) {
                        paysFilter.innerHTML += '<option value="' + pays + '">' + pays + '</option>';
                    });
                }
            };
            xhr.send();
        }

        function openTab(evt, category, page = 1) {
            document.querySelectorAll(".tablinks").forEach(btn => btn.classList.remove("active"));
            evt.currentTarget.classList.add("active");

            resetFilters(); // Réinitialiser les filtres lors du changement d'onglet
            
            // Gérer l'affichage des filtres selon la catégorie
            const paysFilterGroup = document.getElementById("pays-filter-group");
            const typeFilterGroup = document.getElementById("type-filter-group");
            const episodesFilterGroup = document.getElementById("episodes-filter-group");
            
            if (category === "Anime") {
                // Pour Anime : masquer pays, afficher type et épisodes
                paysFilterGroup.style.display = "none";
                typeFilterGroup.style.display = "flex";
                episodesFilterGroup.style.display = "flex";
            } else if (category === "Série d'Animation" || category === "Série") {
                // Pour Série d'Animation et Série : afficher pays et épisodes, masquer type
                paysFilterGroup.style.display = "flex";
                typeFilterGroup.style.display = "none";
                episodesFilterGroup.style.display = "flex";
            } else {
                // Pour les autres catégories : afficher pays, masquer type et épisodes
                paysFilterGroup.style.display = "flex";
                typeFilterGroup.style.display = "none";
                episodesFilterGroup.style.display = "none";
            }
            
            // Exécuter les requêtes en parallèle pour améliorer les performances
            Promise.all([
                fetch("./scripts-php/filters.php?categorie=" + encodeURIComponent(category))
                    .then(response => response.json()),
                fetch("./scripts-php/statistics.php?categorie=" + encodeURIComponent(category))
                    .then(response => response.json()),
                fetch("./scripts-php/liste-film-display/display.php?categorie=" + encodeURIComponent(category) + "&page=" + encodeURIComponent(page))
                    .then(response => response.text())
            ]).then(([filtersData, statsData, displayData]) => {
                // Mettre à jour les filtres
                const studioFilter = document.getElementById("studio-filter");
                const anneeFilter = document.getElementById("annee-filter");
                const paysFilter = document.getElementById("pays-filter");

                // Mettre à jour le filtre de studio
                studioFilter.innerHTML = '<option value="Tous les studios">Tous les studios</option>';
                filtersData.studios.forEach(function(studio) {
                    studioFilter.innerHTML += '<option value="' + studio + '">' + studio + '</option>';
                });

                // Mettre à jour le filtre d'année
                anneeFilter.innerHTML = '<option value="">Toutes les années</option>';
                filtersData.annees.forEach(function(annee) {
                    anneeFilter.innerHTML += '<option value="' + annee + '">' + annee + '</option>';
                });

                // Mettre à jour le filtre de pays
                paysFilter.innerHTML = '<option value="">Tous les pays</option>';
                filtersData.pays.forEach(function(pays) {
                    paysFilter.innerHTML += '<option value="' + pays + '">' + pays + '</option>';
                });

                // Mettre à jour les statistiques
                document.getElementById("stat-animation").textContent = statsData.total_films || '-';
                document.getElementById("stat-studios").textContent = statsData.top_studios || '-';
                document.getElementById("stat-decade").textContent = statsData.best_decade || '-';

                // Mettre à jour le contenu des films
                document.getElementById("tabcontent").innerHTML = displayData;
            }).catch(error => {
                console.error('Erreur lors du chargement:', error);
                // Fallback en cas d'erreur - charger au moins les films
                fetch("./scripts-php/liste-film-display/display.php?categorie=" + encodeURIComponent(category) + "&page=" + encodeURIComponent(page))
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById("tabcontent").innerHTML = data;
                    });
            });

            history.pushState(null, '', '?categorie=' + encodeURIComponent(category) + '&page=' + encodeURIComponent(page));
        }

        function rechercherFilms() {
            var input = document.getElementById("search-bar").value;
            var studio = document.getElementById("studio-filter").value;
            var annee = document.getElementById("annee-filter").value;
            var note = document.getElementById("note-filter").value;
            var statut = document.getElementById("statut-filter").value;
            var pays = document.getElementById("pays-filter").value;
            var type = document.getElementById("type-filter").value;
            var episodes = document.getElementById("episodes-filter").value;
            var activeTab = document.querySelector('.tablinks.active');
            var category = activeTab ? activeTab.textContent : 'Animation';
            var page = 1;

            var xhr = new XMLHttpRequest();
            xhr.open("GET", "./scripts-php/liste-film-display/display.php?categorie=" + encodeURIComponent(category) +
                     "&recherche=" + encodeURIComponent(input) +
                     "&studio=" + encodeURIComponent(studio) +
                     "&annee=" + encodeURIComponent(annee) +
                     "&note=" + encodeURIComponent(note) +
                     "&statut=" + encodeURIComponent(statut) +
                     "&pays=" + encodeURIComponent(pays) +
                     "&type=" + encodeURIComponent(type) +
                     "&episodes=" + encodeURIComponent(episodes) +
                     "&page=" + encodeURIComponent(page));
            xhr.onload = function() {
                if (xhr.status === 200) {
                    document.getElementById("tabcontent").innerHTML = xhr.responseText;
                }
            };
            xhr.send();

            history.pushState(null, '', '?categorie=' + encodeURIComponent(category) +
                                        '&recherche=' + encodeURIComponent(input) +
                                        '&studio=' + encodeURIComponent(studio) +
                                        '&annee=' + encodeURIComponent(annee) +
                                        '&note=' + encodeURIComponent(note) +
                                        '&statut=' + encodeURIComponent(statut) +
                                        '&pays=' + encodeURIComponent(pays) +
                                        '&type=' + encodeURIComponent(type) +
                                        '&episodes=' + encodeURIComponent(episodes) +
                                        '&page=' + encodeURIComponent(page));
        }



        document.addEventListener('DOMContentLoaded', function() {
            var params = new URLSearchParams(window.location.search);
            var category = params.get('categorie') || '<?php echo addslashes($category); ?>';
            var page = params.get('page') || 1;
            openTab({currentTarget: document.querySelector('.tablinks.active')}, category, page);
        });
    </script>
    <script src="./scripts-js/profile-image-persistence.js" defer></script>
    <script src="./scripts-js/background.js" defer></script>
    <script src="./scripts-js/film-modal-liste.js" defer></script>
    <script src="./scripts-js/notification-badge.js" defer></script>
    <?php include './scripts-php/scroll-to-top.php'; ?>
</body>
</html>