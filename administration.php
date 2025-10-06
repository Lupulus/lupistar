<?php
session_start();
include './scripts-php/co-bdd.php'; // Connexion à la BDD

error_reporting(E_ALL);
ini_set('display_errors', 1);
// Vérification du statut d'administrateur
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$isSuperAdmin = isset($_SESSION['titre']) && $_SESSION['titre'] === 'Super-Admin';
$isAdmin = isset($_SESSION['titre']) && $_SESSION['titre'] === 'Admin';

if (!$isLoggedIn || (!$isSuperAdmin && !$isAdmin)) {
    header("Location: ./login.php");
    exit;
}

// Définir la page actuelle pour marquer l'onglet actif
$current_page = basename($_SERVER['PHP_SELF'], ".php");

// Récupération des studios, auteurs, pays et sous-genres en ordre alphabétique
function getOptions($pdo, $table) {
    try {
        $options = [];
        
        // Récupérer l'ID et le nom de "Inconnu" en premier
        $stmt = $pdo->prepare("SELECT id, nom FROM $table WHERE nom = 'Inconnu' LIMIT 1");
        $stmt->execute();
        
        $inconnu = null;
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $inconnu = [$row['id'] => $row['nom']];
        }

        // Récupérer le reste des éléments triés par nom
        $sql = "SELECT id, nom FROM $table WHERE nom != 'Inconnu' ORDER BY nom ASC";
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $options[$row['id']] = $row['nom'];
        }

        // Placer "Inconnu" en premier s'il existe
        return $inconnu ? $inconnu + $options : $options;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des options: " . $e->getMessage());
        return [];
    }
}

$studios = getOptions($pdo, "studios");
$auteurs = getOptions($pdo, "auteurs");
$pays = getOptions($pdo, "pays");
$sous_genres = getOptions($pdo, "sous_genres");

// Fonction pour récupérer les studios en fonction de la catégorie
function getStudiosParCategorie($pdo, $categorie) {
    try {
        $studios = [];
        $searchPattern = "%".$categorie."%"; // Permet de trouver la catégorie peu importe sa position

        $stmt = $pdo->prepare("SELECT id, nom FROM studios WHERE categorie LIKE ? ORDER BY nom ASC");
        $stmt->execute([$searchPattern]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $studios[$row['id']] = $row['nom'];
        }

        return $studios;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des studios par catégorie: " . $e->getMessage());
        return [];
    }
}

// Fonction pour ajouter un studio si "Autre" est sélectionné
function ajouterOuMettreAJourStudio($pdo, $nom_studio, $categorie) {
    try {
        $stmt = $pdo->prepare("SELECT id, categorie FROM studios WHERE nom = ?");
        $stmt->execute([$nom_studio]);

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Studio existe déjà : mettre à jour la catégorie si nécessaire
            $id = $row['id'];
            $categoriesExistantes = explode(',', $row['categorie']);

            if (!in_array($categorie, $categoriesExistantes)) {
                $categoriesExistantes[] = $categorie;
                $nouvellesCategories = implode(',', $categoriesExistantes);

                $stmt = $pdo->prepare("UPDATE studios SET categorie = ? WHERE id = ?");
                $stmt->execute([$nouvellesCategories, $id]);
            }
            return $id;
        } else {
            // Studio inexistant : insertion
            $stmt = $pdo->prepare("INSERT INTO studios (nom, categorie) VALUES (?, ?)");
            $stmt->execute([$nom_studio, $categorie]);
            return $pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de l'ajout/mise à jour du studio: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Traitement AJAX pour récupérer les studios selon la catégorie
    if (isset($_POST['categorie'])) {
        $categorie = $_POST['categorie'];
        $studios = getStudiosParCategorie($pdo, $categorie);
        echo json_encode(["success" => true, "studios" => $studios]);
        exit;
    }

    // Traitement AJAX pour ajouter un studio avec une catégorie
    if (isset($_POST['nouveau_studio'])) {
        $categorie = $_POST['categorie'];
        $nouveau_studio = trim($_POST['nouveau_studio']);

        if (!empty($categorie) && !empty($nouveau_studio)) {
            $studio_id = ajouterOuMettreAJourStudio($pdo, $nouveau_studio, $categorie);
            echo json_encode(["success" => true, "id" => $studio_id, "nom" => $nouveau_studio]);
        } else {
            echo json_encode(["error" => "Données invalides"]);
        }
        exit;
    }

    // Si on arrive ici, c'est une requête AJAX invalide
    echo json_encode(["error" => "Requête invalide."]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="./css/style-navigation.css">
    <link rel="stylesheet" href="./css/style-admin.css">
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico">
    <title>Administration</title>
</head>
<body>
<div class="background"></div>
<header>
    <nav class="navbar">
        <img src="./gif/logogif.GIF" alt="" class="gif">
        <ul class="menu">
            <a class="btn <?php if ($current_page == 'index') echo 'active'; ?>" href="./index.php">Accueil</a>
            <a class="btn <?php if ($current_page == 'administration') echo 'active'; ?>" href="./administration.php">Administration</a>
            <a class="btn <?php if ($current_page == 'membres') echo 'active'; ?>" href="./membres.php">Membres</a>
            <a class="btn <?php echo $isAdmin ? 'disabled' : ''; ?>" href="<?php echo $isAdmin ? '#' : '/bddadmin'; ?>" <?php echo $isAdmin ? '' : 'target="_blank" rel="noopener noreferrer"'; ?>>Base de données</a>
        </ul>
        <div class="profil" id="profil">
            <?php 
                        $img_id = 'profilImg';
                        include './scripts-php/img-profil.php'; 
                        ?>
            <div class="menu-deroulant" id="deroulant">
                <?php include './scripts-php/menu-profil.php'; ?>
            </div>
        </div>
    </nav>
</header>

<!-- Sidebar de sommaire avec icônes flottantes -->
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
<form id="filmForm" action="./scripts-php/add-film.php" method="post" enctype="multipart/form-data">
    <!-- Section principale : Titre et Catégorie -->
    <div class="form-section two-columns">
        <div class="form-group">
            <label id="nom_film_label" for="nom_film">Nom du film :</label>
            <input type="text" id="nom_film" name="nom_film" placeholder="Nom du film (max 50 caractères)" maxlength="50" required>
        </div>
        <div class="form-group">
            <label id="categorie_label" for="categorie">Catégorie :</label>
            <select id="categorie" name="categorie" required onchange="updateStudios()">
                <option value="">Sélectionnez une catégorie</option>
                <option value="Film">Film</option>
                <option value="Animation">Animation</option>
                <option value="Anime">Anime</option>
                <option value="Série">Série</option>
                <option value="Série d'Animation">Série d'Animation</option>
            </select>
        </div>
    </div>

    <!-- Section Anime Type (cachée par défaut) -->
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

    <!-- Section description -->
    <div class="form-section full-width">
        <div class="form-group">
            <label id="description_label" for="description">Description :</label>
            <textarea id="description" name="description" rows="4" cols="50" placeholder="Pas de description" maxlength="400" oninput="updateCharCount()"></textarea>
            <span id="charCount" class="description-compteur">0 / 400</span>
        </div>
    </div>

    <!-- Section détails : Date, Image, Ordre -->
    <div class="form-section three-columns">
        <div class="form-group">
            <label id="date_sortie_label" for="date_sortie">Année de sortie :</label>
            <input type="number" id="date_sortie" name="date_sortie" min="1900" max="2099" step="1" value="<?php echo date('Y'); ?>" required>
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

    <!-- Section série (cachée par défaut) -->
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

    <!-- Section Studio et Auteur -->
    <div class="form-section two-columns">
        <div class="form-group">
            <label id="studio_label" for="studio">Studio :</label>
            <select id="studio" name="studio_id" required onchange="toggleAutreStudio()">
                <option value="">Sélectionnez un studio</option>
                <option value="autre">Autre</option>
                <?php foreach ($studios as $id => $nom) { echo "<option value='$id'>$nom</option>"; } ?>
            </select>
            <input type="text" id="nouveau_studio" name="nouveau_studio" placeholder="Nom du studio" maxlength="30" style="display:none;">
        </div>
        <div class="form-group">
            <label id="auteur_label" for="auteur">Auteur :</label>
            <select id="auteur" name="auteur_id" required>
                <option value="">Sélectionnez un auteur</option>
                <option value="autre">Autre</option>
                <option value="1">Inconnu</option>
                <?php foreach ($auteurs as $id => $nom) { echo "<option value='$id'>$nom</option>"; } ?>
            </select>
            <input type="text" id="nouveau_auteur" name="nouveau_auteur" placeholder="Nom de l'auteur" maxlength="30" style="display:none;">
        </div>
    </div>

    <!-- Section Pays -->
    <div class="form-section full-width">
        <div class="form-group">
            <label id="pays_label" for="pays">Pays :</label>
            <select id="pays" name="pays_id" required onchange="handlePaysChange()">
                <option value="">Sélectionnez un pays</option>
                <?php foreach ($pays as $id => $nom) { echo "<option value='$id'>$nom</option>"; } ?>
            </select>
            <div id="japan-notification" class="japan-notification" style="display: none;">
                <span class="notification-icon">ℹ️</span>
                <span class="notification-text">Les films et séries d'animation japonaises appartiennent à la catégorie "Anime".</span>
            </div>
        </div>
    </div>

    <!-- Section sous-genres -->
    <div class="form-section full-width">
        <div class="form-group">
            <label id="sous-genres_label">Sous-genres :</label>
            <div id="sous-genres-container">
                <table>
                    <tbody>
                        <?php
                        $sous_genres_list = array_values($sous_genres); // Assure l'indexation
                        $total_sous_genres = count($sous_genres_list);
                        $colonnes = 6; // Nombre de colonnes par ligne
                        $lignes = ceil($total_sous_genres / $colonnes); // Calcul du nombre de lignes

                        for ($i = 0; $i < $lignes; $i++) {
                            echo "<tr>";
                            for ($j = 0; $j < $colonnes; $j++) {
                                $index = ($i * $colonnes) + $j;
                                if ($index < $total_sous_genres) {
                                    $id = array_keys($sous_genres)[$index];
                                    $nom = $sous_genres[$id];
                                    echo "<td><label class='checkbox-label'><input type='checkbox' name='sous_genres[]' value='$id'> $nom</label></td>";
                                } else {
                                    echo "<td></td>"; // Case vide pour alignement correct
                                }
                            }
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <p style="color: red; display: none;" id="sous-genre-warning">⚠️ Sélectionnez au moins un sous-genre.</p>
        </div>
    </div>

    <input id="Bouton-ajouter" type="submit" value="Ajouter le film">
</form>
<div id="notification"></div> <!-- Div pour afficher les messages -->
<script>
    document.getElementById('auteur').addEventListener('change', function() {
        document.getElementById('nouveau_auteur').style.display = (this.value === 'autre') ? 'block' : 'none';
    });

    // Fonction pour gérer le changement de pays
    function handlePaysChange() {
        const paysSelect = document.getElementById('pays');
        const categorieSelect = document.getElementById('categorie');
        const japanNotification = document.getElementById('japan-notification');
        
        // Récupérer le texte de l'option sélectionnée
        const selectedOption = paysSelect.options[paysSelect.selectedIndex];
        const selectedText = selectedOption ? selectedOption.text : '';
        
        // Vérifier si le Japon est sélectionné (ID 2 ou texte contenant "Japon")
        const isJapanSelected = paysSelect.value === '2' || selectedText.includes('Japon');
        
        // Afficher/masquer la notification
        if (isJapanSelected) {
            
            // Logique de changement automatique de catégorie
            const currentCategory = categorieSelect.value;
            if (currentCategory === 'Animation' || currentCategory === 'Série d\'Animation') {
                japanNotification.style.display = 'block';
                categorieSelect.value = 'Anime';
                // Déclencher l'événement change pour mettre à jour les studios si nécessaire
                if (typeof updateStudios === 'function') {
                    updateStudios();
                }
            }
        } else {
            japanNotification.style.display = 'none';
        }
    }

    // Fonction pour gérer le changement de type d'anime
    function handleAnimeTypeChange() {
        const animeType = document.getElementById('anime_type').value;
        const nbrEpisodeLabel = document.getElementById('nbrEpisode_label');
        const nbrEpisodeInput = document.getElementById('nbrEpisode');
        
        if (animeType === 'Série') {
            // Afficher le champ nombre d'épisodes pour les séries
            nbrEpisodeLabel.style.display = 'block';
            nbrEpisodeInput.style.display = 'block';
            nbrEpisodeInput.required = true;
        } else {
            // Masquer le champ nombre d'épisodes pour les films
            nbrEpisodeLabel.style.display = 'none';
            nbrEpisodeInput.style.display = 'none';
            nbrEpisodeInput.required = false;
        }
    }

    // Fonction pour gérer l'affichage de la section anime type
    function handleCategoryChange() {
        const categorieSelect = document.getElementById('categorie');
        const animeTypeSection = document.getElementById('anime-type-section');
        const animeTypeSelect = document.getElementById('anime_type');
        
        if (categorieSelect.value === 'Anime') {
            animeTypeSection.style.display = 'block';
        } else {
            animeTypeSection.style.display = 'none';
            animeTypeSelect.value = '';
            // Réinitialiser l'affichage des épisodes
            handleAnimeTypeChange();
        }
    }

    // Ajouter l'événement pour le changement de catégorie
    document.getElementById('categorie').addEventListener('change', handleCategoryChange);
</script>

<div id="pending-films-section" class="admin-section">
<h2>Films en attente d'approbation</h2>
<div id="pending-films-container" class="pending-container">
    <div id="pending-films-count" class="pending-count">Chargement...</div>
    <div id="pending-films-list" class="pending-films-list"></div>
</div>

<!-- Modal pour examiner/modifier un film en attente -->
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
                            <option value="Film">Film</option>
                            <option value="Animation">Animation</option>
                            <option value="Anime">Anime</option>
                            <option value="Série">Série</option>
                            <option value="Série d'Animation">Série d'Animation</option>
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
                        <option value="Autre">Autre (préciser dans le commentaire)</option>
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
<div id="liste-film" class="films-container"></div>
<!-- Script pour mise à jour du compteur de Charactère de Description -->
<script>
    function updateCharCount() {
        const desc = document.getElementById("description");
        const counter = document.getElementById("charCount");
        
        if (desc.value.length > 400) {
            desc.value = desc.value.slice(0, 400); // Couper à 400 caractères
        }

        counter.textContent = `${desc.value.length} / 400`;
        if (desc.value.length >= 390) {
            counter.style.color = "red";
        } else {
            counter.style.color = "";
        }
    }

    // Initialisation au chargement
    document.addEventListener("DOMContentLoaded", () => {
        updateCharCount();
        document.getElementById("description").addEventListener("input", updateCharCount);
    });
</script>
<!-- Script pour Affichage info Série -->
<script>
    document.getElementById('categorie').addEventListener('change', function () {
        const categorie = this.value;
        const isSerie = categorie === "Série" || categorie === "Série d'Animation";

        // Gérer affichage des champs
        const ordreSuiteLabel = document.getElementById("ordre_suite_label");
        const ordreSuiteInput = document.getElementById("ordre_suite");
        const saisonLabel = document.getElementById("saison_label");
        const saisonInput = document.getElementById("saison");
        const nbrEpisodeLabel = document.getElementById("nbrEpisode_label");
        const nbrEpisodeInput = document.getElementById("nbrEpisode");

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
    });
    function updateNomFilmLabel() {
        const categorie = document.getElementById("categorie").value;
        const label = document.getElementById("nom_film_label");
        const input = document.getElementById("nom_film");

        if (categorie === "Série" || categorie === "Série d'Animation") {
            label.textContent = "Nom de la série :";
            input.placeholder = "Nom de la série (max 50 caractères)";
        } else {
            label.textContent = "Nom du film :";
            input.placeholder = "Nom du film (max 50 caractères)";
        }
    }

    // Exécute dès que la catégorie change
    document.getElementById("categorie").addEventListener("change", updateNomFilmLabel);

    // Appel au chargement de la page (au cas où la catégorie est préremplie)
    document.addEventListener("DOMContentLoaded", updateNomFilmLabel);
</script>
<!-- Script pour chargement et delete des films -->
<script>
    function loadFilms() {
        fetch("./scripts-php/display-film.php")
        .then(response => response.text())
        .then(data => document.getElementById("liste-film").innerHTML = data);
    }

    async function deleteFilm(id) {
        const confirmed = await customDanger('Voulez-vous vraiment supprimer ce film ?', 'Confirmation de suppression');
        if (confirmed) {
            fetch('./scripts-php/delete-film.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    id: id,
                    action: 'delete'
                })
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
            .catch(error => {
                console.error("Erreur AJAX :", error);
                customAlert("Une erreur s'est produite lors de la suppression.", 'Erreur');
            });
        }
    }


    window.onload = loadFilms;
    function filterFilms() {
        const input = document.getElementById("searchBar");
        const filter = input.value.toLowerCase();
        const filmItems = document.querySelectorAll(".film-item");

        filmItems.forEach(film => {
            const text = film.textContent.toLowerCase();
            if (text.includes(filter)) {
                film.style.display = ""; // Remet le style d'origine (pas "block")
            } else {
                film.style.display = "none";
            }
        });
    }
</script>
<!-- Script de gestion du formulaire d'ajout -->
<script>
    function updateStudios() {
        let categorie = document.getElementById("categorie").value;
        let studioSelect = document.getElementById("studio");

        // Réinitialiser le select avec l'option de base et "Autre" en deuxième position
        studioSelect.innerHTML = "<option value=''>Sélectionnez un studio</option><option value='autre'>Autre</option>";
        
        // Ajouter l'option "Inconnu" toujours présente
        let optionInconnu = document.createElement("option");
        optionInconnu.value = "1";
        optionInconnu.textContent = "Inconnu";
        studioSelect.appendChild(optionInconnu);

        if (!categorie) return;

        fetch("./administration.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "categorie=" + encodeURIComponent(categorie)
        })
        .then(response => response.text())
        .then(text => {
            console.log("Réponse brute du serveur :", text);
            return JSON.parse(text);
        })
        .then(data => {
            if (data.success) {
                Object.entries(data.studios).forEach(([id, nom]) => { 
                    let option = document.createElement("option");
                    option.value = id;
                    option.textContent = nom;
                    studioSelect.appendChild(option);
                });
                }
        })
        .catch(error => console.error("Erreur AJAX :", error));
    }

    function toggleAutreStudio() {
        let studioSelect = document.getElementById("studio");
        let autreStudioInput = document.getElementById("nouveau_studio");

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

    document.getElementById('auteur').addEventListener('change', function() {
        toggleAutreChamp('auteur', 'nouveau_auteur');
    });
</script>
<!-- Script pour ajout des films via formulaire -->
<script>
    document.getElementById('filmForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Empêcher le rechargement de la page
        
        var formData = new FormData(this);
        console.log("Valeur de la catégorie :", formData.get("categorie")); // Debug

        // Vérification des sous-genres cochés
        let sousGenresCoches = document.querySelectorAll('input[name="sous_genres[]"]:checked');
        if (sousGenresCoches.length === 0) {
            document.getElementById("sous-genre-warning").style.display = "block";
            document.getElementById("sous-genre-warning").textContent = "⚠️ Vous devez sélectionner au moins un sous-genre.";
            return;
        } else {
            document.getElementById("sous-genre-warning").style.display = "none";
        }

        let studioSelect = document.getElementById("studio");
        if (studioSelect.value === "autre") {
            let nouveauStudioInput = document.getElementById("nouveau_studio").value;
            formData.append("nouveau_studio", nouveauStudioInput);
        }

        fetch('./scripts-php/add-film.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            console.log("Réponse brute reçue :", text); // Debug

            // Tenter d'extraire le JSON en supprimant les warnings PHP potentiels
            const jsonStart = text.indexOf("{");
            const jsonEnd = text.lastIndexOf("}");
            if (jsonStart !== -1 && jsonEnd !== -1) {
                text = text.substring(jsonStart, jsonEnd + 1);
            }

            let data;
            try {
                data = JSON.parse(text);
            } catch (error) {
                console.error("Erreur JSON :", error, "Réponse brute corrigée :", text);
                document.getElementById('notification').innerHTML = 
                    '<div class="error">⚠️ Réponse invalide du serveur.</div>';
                return;
            }

            // Affichage du message dans la div notification
            const notificationDiv = document.getElementById("notification");
            if (data.success) {
                notificationDiv.innerHTML = '<div class="success">✅ ' + data.success + '</div>';
                document.getElementById('filmForm').reset();
                document.querySelectorAll("#sous-genres-container input[type='checkbox']").forEach(cb => cb.checked = false);
            } else {
                notificationDiv.innerHTML = '<div class="error">❌ ' + (data.error || "Une erreur inconnue est survenue.") + '</div>';
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'envoi des données:', error);
            document.getElementById('notification').innerHTML = 
                '<div class="error">⚠️ Impossible de contacter le serveur.</div>';
        });

    });
</script>
<!-- Script pour formulaire de modification -->
<script>
        function showModifyForm(id) {
            document.getElementById('modify-form-' + id).style.display = 'block';
            document.getElementById('modify-form-' + id + '-form').style.display = 'block';
        }
        
        function hideModifyForm(id) {
            document.getElementById('modify-form-' + id).style.display = 'none';
            document.getElementById('modify-form-' + id + '-form').style.display = 'none';
        }
        
        function modifyFilm(id) {
            var form = document.getElementById("modify-form-" + id + "-form");
            var formData = new FormData(form);

            fetch("./scripts-php/modify-film.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.text())  // Récupère la réponse brute
            .then(text => {

                try {
                    let data = JSON.parse(text);  // Tente de parser le JSON
                    console.log("JSON reçu :", data);

                    if (data.success) {
                        customSuccess("Modification réussie !", "Modification du film");
                        loadFilms(); // Recharger la liste des films après modification
                    } else {
                        customAlert("Erreur : " + (data.error || "Aucune réponse"), "Erreur de modification");
                    }
                } catch (error) {
                    console.error("Erreur JSON :", error, "Réponse brute :", text);
                    customAlert("Erreur lors de la modification du film.", "Erreur");
                }
            })
            .catch(error => console.error("Erreur AJAX :", error));
        }
</script>

<!-- Scripts pour la gestion des films en attente -->
<script>
let currentPendingFilm = null;

// Charger les films en attente au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadPendingFilms();
});

function loadPendingFilms() {
    fetch('./scripts-php/get-pending-films.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPendingFilms(data.films);
                document.getElementById('pending-films-count').textContent = 
                    `${data.count} film(s) en attente d'approbation`;
            } else {
                document.getElementById('pending-films-count').textContent = 'Erreur de chargement';
                console.error('Erreur:', data.error);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('pending-films-count').textContent = 'Erreur de chargement';
        });
}

function displayPendingFilms(films) {
    const container = document.getElementById('pending-films-list');
    
    if (films.length === 0) {
        container.innerHTML = '<p class="no-pending-films">Aucun film en attente d\'approbation.</p>';
        return;
    }
    
    let html = '<div class="pending-films-grid">';
    
    films.forEach(film => {
        html += `
            <div class="pending-film-card" onclick="openPendingFilmModal(${film.id})">
                <div class="pending-film-image">
                    <img src="${film.image_path}" alt="${film.nom_film}" onerror="this.src='./img/default-film.png'">
                </div>
                <div class="pending-film-info">
                    <h4>${film.nom_film}</h4>
                    <p><strong>Catégorie:</strong> ${film.categorie}</p>
                    <p><strong>Année:</strong> ${film.date_sortie}</p>
                    <p><strong>Proposé par:</strong> ${film.propose_par_pseudo}</p>
                    <p><strong>Date:</strong> ${film.date_proposition_formatted}</p>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function openPendingFilmModal(filmId) {
    fetch('./scripts-php/get-pending-films.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const film = data.films.find(f => f.id == filmId);
                if (film) {
                    currentPendingFilm = film;
                    populateModal(film);
                    document.getElementById('pendingFilmModal').style.display = 'block';
                }
            }
        })
        .catch(error => console.error('Erreur:', error));
}

function populateModal(film) {
    document.getElementById('modal-film-title').textContent = `Examiner: ${film.nom_film}`;
    document.getElementById('modal-nom-film').value = film.nom_film;
    document.getElementById('modal-categorie').value = film.categorie;
    document.getElementById('modal-description').value = film.description;
    document.getElementById('modal-date-sortie').value = film.date_sortie;
    document.getElementById('modal-ordre-suite').value = film.ordre_suite;
    document.getElementById('modal-saison').value = film.saison || '';
    document.getElementById('modal-nbrEpisode').value = film.nbrEpisode || '';
    
    document.getElementById('modal-studio').textContent = film.studio_nom || 'Inconnu';
    document.getElementById('modal-auteur').textContent = film.auteur_nom || 'Inconnu';
    document.getElementById('modal-pays').textContent = film.pays_nom || 'Inconnu';
    document.getElementById('modal-sous-genres').textContent = film.sous_genres.join(', ');
    document.getElementById('modal-propose-par').textContent = film.propose_par_pseudo;
    document.getElementById('modal-date-proposition').textContent = film.date_proposition_formatted;
    
    document.getElementById('modal-film-image').src = film.image_path;
    document.getElementById('modal-commentaire-admin').value = '';
    
    // Gérer l'affichage des champs saison/épisodes
    const isSerie = film.categorie.includes('Série');
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
    
    const commentaire = document.getElementById('modal-commentaire-admin').value;
    
    const confirmed = await customConfirm('Êtes-vous sûr de vouloir approuver ce film ?', 'Confirmation d\'approbation');
    if (confirmed) {
        const formData = new FormData();
        formData.append('film_temp_id', currentPendingFilm.id);
        formData.append('commentaire_admin', commentaire);
        
        fetch('./scripts-php/approve-film.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                customSuccess('Film approuvé avec succès !', 'Approbation réussie');
                closePendingFilmModal();
                loadPendingFilms();
                // Recharger aussi la liste des films si elle existe
                if (typeof loadFilms === 'function') {
                    loadFilms();
                }
            } else {
                customAlert('Erreur: ' + data.error, 'Erreur d\'approbation');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            customAlert('Erreur lors de l\'approbation', 'Erreur');
        });
    }
}

async function rejectFilm() {
    if (!currentPendingFilm) return;
    
    // Afficher le champ de raison de rejet
    document.getElementById('rejection-reason-row').style.display = 'block';
    
    const raisonRejet = document.getElementById('modal-raison-rejet').value;
    const commentaire = document.getElementById('modal-commentaire-admin').value;
    
    if (!raisonRejet) {
        customAlert('Veuillez sélectionner une raison de rejet.', 'Raison manquante');
        return;
    }
    
    // Si "Autre" est sélectionné, un commentaire est obligatoire
    if (raisonRejet === 'Autre' && !commentaire.trim()) {
        customAlert('Un commentaire est requis lorsque vous sélectionnez "Autre" comme raison.', 'Commentaire requis');
        return;
    }
    
    const confirmed = await customDanger('Êtes-vous sûr de vouloir rejeter ce film ?', 'Confirmation de rejet');
    if (confirmed) {
        const formData = new FormData();
        formData.append('film_temp_id', currentPendingFilm.id);
        formData.append('raison_rejet', raisonRejet);
        formData.append('commentaire_admin', commentaire);
        
        fetch('./scripts-php/reject-film.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                customSuccess('Film rejeté.');
                closePendingFilmModal();
                loadPendingFilms();
            } else {
                customAlert('Erreur: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            customAlert('Erreur lors du rejet');
        });
    }
}

// Fonctions d'autocomplétion pour l'administration
function setupAutocompleteAdmin(input, type) {
    let timeout;
    let suggestionsList = document.getElementById(input.id + '_suggestions');
    
    // Créer la liste de suggestions si elle n'existe pas
    if (!suggestionsList) {
        suggestionsList = document.createElement('ul');
        suggestionsList.id = input.id + '_suggestions';
        suggestionsList.className = 'autocomplete-suggestions';
        
        // Créer un wrapper autocomplete-container si il n'existe pas déjà
        let container = input.parentElement.querySelector('.autocomplete-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'autocomplete-container';
            
            // Insérer le container avant l'input
            input.parentElement.insertBefore(container, input);
            // Déplacer l'input dans le container
            container.appendChild(input);
        }
        
        container.appendChild(suggestionsList);
    }
    
    input.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            suggestionsList.classList.remove('show');
            return;
        }
        
        timeout = setTimeout(() => {
            const categorie = document.getElementById('categorie').value;
            let url = `./scripts-php/get-autocomplete-${type}.php?search=${encodeURIComponent(query)}`;
            if (type === 'studios' && categorie) {
                url += `&categorie=${encodeURIComponent(categorie)}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    suggestionsList.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach(item => {
                            const li = document.createElement('li');
                            li.textContent = item;
                            li.className = 'autocomplete-suggestion';
                            
                            li.addEventListener('click', function() {
                                input.value = this.textContent;
                                suggestionsList.classList.remove('show');
                            });
                            
                            suggestionsList.appendChild(li);
                        });
                        
                        suggestionsList.classList.add('show');
                    } else {
                        // Afficher un message "aucun résultat"
                        const li = document.createElement('li');
                        li.textContent = 'Aucun résultat trouvé';
                        li.className = 'autocomplete-no-results';
                        suggestionsList.appendChild(li);
                        suggestionsList.classList.add('show');
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de l\'autocomplétion:', error);
                    suggestionsList.classList.remove('show');
                });
        }, 300);
    });
    
    // Masquer les suggestions quand on clique ailleurs
    document.addEventListener('click', function(e) {
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

// Fermer la modal en cliquant à l'extérieur
window.onclick = function(event) {
    const modal = document.getElementById('pendingFilmModal');
    if (event.target == modal) {
        closePendingFilmModal();
    }
}
</script>


<!-- Section d'envoi de notifications -->
<div id="send-notification-section" class="admin-section">
    <h2>Envoyer une notification</h2>
    <form id="notificationForm">
        <!-- Type de destinataire -->
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

        <!-- Section pour sélection par titre (cachée par défaut) -->
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

        <!-- Section pour utilisateur spécifique (cachée par défaut) -->
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

        <!-- Contenu de la notification -->
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

        <!-- Boutons d'action -->
        <div class="form-section">
            <button type="button" class="btn-add" onclick="sendNotification()">Envoyer la notification</button>
            <button type="button" class="btn-cancel" onclick="resetNotificationForm()">Réinitialiser</button>
        </div>

        <!-- Zone de résultat -->
        <div id="notification-result" class="result-message" style="display:none;"></div>
    </form>
</div>

<script src="./scripts-js/profile-image-persistence.js" defer></script>
<script src="./scripts-js/background.js" defer></script>

<footer>
  <p>&copy; 2025 lupistar.fr — Tous droits réservés.</p>
  <p>Les illustrations sont la propriété de leurs auteurs et éditeurs respectifs.</p>
  <nav>
    <a href="/mentions-legales.php">Mentions légales</a> | 
    <a href="/confidentialite.php">Politique de confidentialité</a>
  </nav>
</footer>

<!-- Scripts pour le sommaire avec icônes flottantes -->
<script>
    // Variables pour gérer l'état du sommaire
    let summaryExpanded = false;
    
    // Fonction pour basculer l'affichage du sommaire
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
    
    // Fonction pour faire défiler vers une section
    function scrollToSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }
    
    // Initialisation au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        // Masquer le contenu du sommaire par défaut
        const summaryContent = document.getElementById('summary-content');
        summaryContent.style.display = 'none';
        
        // Ajouter les animations de flottement aux icônes
        const floatingIcons = document.querySelectorAll('.floating-icon');
        floatingIcons.forEach((icon, index) => {
            // Délai différent pour chaque icône pour un effet plus naturel
            icon.style.animationDelay = `${index * 0.5}s`;
        });
        
        // Charger les conversions de studios au chargement de la page
        loadStudioConversions();
    });
    
    // Fonctions pour la gestion des conversions de studios
    function openStudioConversionsModal() {
        document.getElementById('studioConversionsModal').style.display = 'block';
        loadStudioConversions();
    }
    
    function closeStudioConversionsModal() {
        document.getElementById('studioConversionsModal').style.display = 'none';
    }
    
    function loadStudioConversions() {
        fetch('./scripts-php/studio-converter.php?action=list')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayConversions(data.conversions);
                } else {
                    console.error('Erreur lors du chargement des conversions:', data.error);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
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
                    <strong>${key}</strong> → <span class="target-name">${conversion.target}</span>
                    <button class="btn-delete-conversion" onclick="deleteConversion('${key}')">🗑️</button>
                </div>
                <div class="conversion-patterns">
                    Variantes: ${conversion.patterns.join(', ')}
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
        
        const data = {
            action: 'add_conversion',
            key: key,
            patterns: patterns,
            target: target
        };
        
        fetch('./scripts-php/studio-converter.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Réinitialiser le formulaire
                document.getElementById('conversion-key').value = '';
                document.getElementById('conversion-patterns').value = '';
                document.getElementById('conversion-target').value = '';
                
                // Recharger la liste
                loadStudioConversions();
                
                customSuccess('Conversion ajoutée avec succès!');
            } else {
                customAlert('Erreur: ' + data.error, 'Erreur d\'ajout');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            customAlert('Erreur lors de l\'ajout de la conversion.', 'Erreur');
        });
    }
    
    async function deleteConversion(key) {
        const confirmed = await customDanger('Êtes-vous sûr de vouloir supprimer cette conversion?', 'Confirmation de suppression');
        if (!confirmed) {
            return;
        }
        
        const data = {
            action: 'remove_conversion',
            key: key
        };
        
        fetch('./scripts-php/studio-converter.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadStudioConversions();
                customSuccess('Conversion supprimée avec succès!');
            } else {
                customAlert('Erreur: ' + data.error, 'Erreur de suppression');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
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
        
        fetch('./scripts-php/studio-converter.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const resultDiv = document.getElementById('test-result');
                if (data.converted !== data.original) {
                    resultDiv.innerHTML = `<span class="test-success">✅ "${data.original}" → "${data.converted}"</span>`;
                } else {
                    resultDiv.innerHTML = `<span class="test-no-change">ℹ️ "${data.original}" (aucune conversion trouvée)</span>`;
                }
            } else {
                customAlert('Erreur: ' + data.error, 'Erreur de test');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            customAlert('Erreur lors du test de conversion.', 'Erreur');
        });
    }
</script>

<!-- Modale de gestion des conversions de studios -->
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
            
            <!-- Formulaire d'ajout de conversion -->
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
            
            <!-- Liste des conversions existantes -->
            <div class="existing-conversions-section">
                <h4>Conversions existantes</h4>
                <div id="conversions-list" class="conversions-list">
                    <!-- Les conversions seront chargées ici via JavaScript -->
                </div>
            </div>
            
            <!-- Section de test -->
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

<script>
// Gestion des interactions pour la section d'envoi de notifications

// Fonction pour gérer le changement de type de destinataire
function handleRecipientTypeChange() {
    const recipientType = document.getElementById('recipient-type').value;
    const titleSelection = document.getElementById('title-selection');
    const specificUserSelection = document.getElementById('specific-user-selection');
    
    // Masquer toutes les sections conditionnelles
    titleSelection.style.display = 'none';
    specificUserSelection.style.display = 'none';
    
    // Réinitialiser les champs
    document.getElementById('user-title').value = '';
    document.getElementById('search-type').value = '';
    document.getElementById('user-search').value = '';
    document.getElementById('user-search-group').style.display = 'none';
    
    // Afficher la section appropriée
    if (recipientType === 'title') {
        titleSelection.style.display = 'block';
    } else if (recipientType === 'specific') {
        specificUserSelection.style.display = 'block';
    }
}

// Fonction pour gérer le changement de type de recherche
function handleSearchTypeChange() {
    const searchType = document.getElementById('search-type').value;
    const userSearchGroup = document.getElementById('user-search-group');
    const userSearchLabel = document.getElementById('user-search-label');
    const userSearchInput = document.getElementById('user-search');
    
    if (searchType) {
        userSearchGroup.style.display = 'block';
        if (searchType === 'username') {
            userSearchLabel.textContent = 'Nom d\'utilisateur :';
            userSearchInput.placeholder = 'Entrez le nom d\'utilisateur';
        } else if (searchType === 'email') {
            userSearchLabel.textContent = 'Adresse e-mail :';
            userSearchInput.placeholder = 'Entrez l\'adresse e-mail';
        }
    } else {
        userSearchGroup.style.display = 'none';
    }
    
    userSearchInput.value = '';
}

// Fonction pour mettre à jour le compteur de caractères
function updateNotificationCharCount() {
    const textarea = document.getElementById('notification-message');
    const counter = document.getElementById('notificationCharCount');
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

// Fonction pour envoyer la notification
function sendNotification() {
    const form = document.getElementById('notificationForm');
    const resultDiv = document.getElementById('notification-result');
    
    // Validation des champs requis
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
    
    // Validation spécifique selon le type
    if (recipientType === 'title') {
        const userTitle = document.getElementById('user-title').value;
        if (!userTitle) {
            showNotificationResult('Veuillez sélectionner un titre d\'utilisateur.', 'error');
            return;
        }
    } else if (recipientType === 'specific') {
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
        
        // Validation de l'email si nécessaire
        if (searchType === 'email' && !isValidEmail(userSearch)) {
            showNotificationResult('Veuillez saisir une adresse e-mail valide.', 'error');
            return;
        }
    }
    
    // Préparation des données
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
    
    // Affichage du message de chargement
    showNotificationResult('Envoi en cours...', 'info');
    
    // Envoi de la requête AJAX
    fetch('./scripts-php/send-notification.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotificationResult(data.message, 'success');
            resetNotificationForm();
        } else {
            showNotificationResult(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotificationResult('Une erreur est survenue lors de l\'envoi de la notification.', 'error');
    });
}

// Fonction pour afficher le résultat
function showNotificationResult(message, type) {
    const resultDiv = document.getElementById('notification-result');
    resultDiv.textContent = message;
    resultDiv.className = 'result-message ' + type;
    resultDiv.style.display = 'block';
    
    // Faire défiler vers le résultat
    resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Masquer automatiquement après 5 secondes pour les messages de succès
    if (type === 'success') {
        setTimeout(() => {
            resultDiv.style.display = 'none';
        }, 5000);
    }
}

// Fonction pour réinitialiser le formulaire
function resetNotificationForm() {
    document.getElementById('notificationForm').reset();
    document.getElementById('title-selection').style.display = 'none';
    document.getElementById('specific-user-selection').style.display = 'none';
    document.getElementById('user-search-group').style.display = 'none';
    document.getElementById('notification-result').style.display = 'none';
    updateNotificationCharCount();
}

// Fonction pour valider une adresse e-mail
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Fonction pour faire défiler vers une section
function scrollToSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        section.scrollIntoView({ behavior: 'smooth' });
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le compteur de caractères
    updateNotificationCharCount();
});
</script>

<script src="./scripts-js/notification-badge.js" defer></script>

<!-- Inclusion du système de popup personnalisé -->
<?php include './scripts-php/popup.php'; ?>
<script src="./scripts-js/custom-popup.js"></script>

<!-- Inclusion du bouton "retour en haut" -->
<?php include './scripts-php/scroll-to-top.php'; ?>
</body>
</html>