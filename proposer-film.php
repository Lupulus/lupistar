<?php
session_start();
include './scripts-php/co-bdd.php'; // Connexion à la BDD

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification de la connexion et du titre utilisateur
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$userTitle = $_SESSION['titre'] ?? '';

// Définir la hiérarchie des titres
$titres_hierarchie = [
    'Membre' => 1,
    'Amateur' => 2,
    'Fan' => 3,
    'NoLife' => 4,
    'Admin' => 5,
    'Super-Admin' => 6
];

$user_level = $titres_hierarchie[$userTitle] ?? 0;

// Vérifier si l'utilisateur a un titre supérieur à "Amateur"
if (!$isLoggedIn || $user_level <= 1) {
    header("Location: ./login.php");
    exit;
}
// Fonction pour récupérer les auteurs en fonction de la catégorie
function getAuteursParCategorie($pdo, $categorie) {
    try {
        $auteurs = [];
        $searchPattern = "%".$categorie."%";

        // Récupérer les auteurs triés par réputation (nombre de films associés) puis par nom
        $stmt = $pdo->prepare("
            SELECT a.id, a.nom, COUNT(f.id) as reputation
            FROM auteurs a
            LEFT JOIN films f ON a.id = f.auteur_id AND f.categorie = ?
            WHERE a.categorie LIKE ?
            GROUP BY a.id, a.nom
            ORDER BY reputation DESC, a.nom ASC
        ");
        $stmt->execute([$categorie, $searchPattern]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $auteurs[$row['id']] = $row['nom'];
        }

        return $auteurs;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des auteurs par catégorie: " . $e->getMessage());
        return [];
    }
}

// Fonction pour ajouter un auteur si "Autre" est sélectionné
function ajouterOuMettreAJourAuteur($pdo, $nom_auteur, $categorie) {
    try {
        $stmt = $pdo->prepare("SELECT id, categorie FROM auteurs WHERE nom = ?");
        $stmt->execute([$nom_auteur]);

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Auteur existe déjà : mettre à jour la catégorie si nécessaire
            $id = $row['id'];
            $categoriesExistantes = explode(',', $row['categorie']);

            if (!in_array($categorie, $categoriesExistantes)) {
                $categoriesExistantes[] = $categorie;
                $nouvellesCategories = implode(',', $categoriesExistantes);

                $stmt = $pdo->prepare("UPDATE auteurs SET categorie = ? WHERE id = ?");
                $stmt->execute([$nouvellesCategories, $id]);
            }
            return $id;
        } else {
            // Auteur inexistant : insertion
            $stmt = $pdo->prepare("INSERT INTO auteurs (nom, categorie) VALUES (?, ?)");
            $stmt->execute([$nom_auteur, $categorie]);
            return $pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de l'ajout/mise à jour de l'auteur: " . $e->getMessage());
        return false;
    }
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
        $searchPattern = "%".$categorie."%";

        // Récupérer les studios triés par réputation (nombre de films associés) puis par nom
        $stmt = $pdo->prepare("
            SELECT s.id, s.nom, COUNT(f.id) as reputation
            FROM studios s
            LEFT JOIN films f ON s.id = f.studio_id AND f.categorie = ?
            WHERE s.categorie LIKE ?
            GROUP BY s.id, s.nom
            ORDER BY reputation DESC, s.nom ASC
        ");
        $stmt->execute([$categorie, $searchPattern]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $studios[$row['id']] = $row['nom'];
        }

        return $studios;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des studios par catégorie: " . $e->getMessage());
        return [];
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

    // Traitement AJAX pour récupérer les auteurs selon la catégorie
    if (isset($_POST['categorie_auteurs'])) {
        $categorie = $_POST['categorie_auteurs'];
        $auteurs = getAuteursParCategorie($pdo, $categorie);
        echo json_encode(["success" => true, "auteurs" => $auteurs]);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposer un film - Lupistar</title>
    <link rel="stylesheet" href="./css/style-proposer.css">
    <link rel="stylesheet" href="./css/style-navigation.css">
    <link rel="icon" type="image/x-icon" href="./img/favicon.ico">
</head>
<body>
    <div class="background"></div>
    
    <header>
        <nav class="navbar">
            <img src="./gif/logogif.GIF" alt="" class="gif">
            <ul class="menu">
            <a class="btn <?php if ($current_page == 'index') echo 'active'; ?>" id="btn1" href="./index.php">Accueil</a>
            <a class="btn <?php if ($current_page == 'liste') echo 'active'; ?>" id="btn2" href="./liste.php">Liste</a>
            <a class="btn <?php if ($current_page == 'ma-liste') echo 'active'; ?>" id="btn3" href="./ma-liste.php">Ma Liste</a>
            <a class="btn <?php if ($current_page == 'forum') echo 'active'; ?>" id="btn4" href="./forum.php">Forum</a>
            </ul>
            <div class="profil" id="profil">
            <?php 
                        $img_id = 'profilImg';
                        include './scripts-php/img-profil.php'; 
                        ?>
            <div class="menu-deroulant" id="deroulant">
                <!-- Options de menu dans la page menuprofil.php -->
                <?php include './scripts-php/menu-profil.php'; ?>
            </div>
        </div>
        </nav>
    </header>

    <main>
        <h2>Proposer un film</h2>
        <div class="container">
            <p class="info-text">Proposez un film à ajouter à la base de données. Votre proposition sera examinée par les administrateurs avant publication.</p>
            
<form id="filmForm" action="./scripts-php/add-film-temp.php" method="post" enctype="multipart/form-data">
    <!-- Section principale : Titre et Catégorie -->
    <div class="form-section two-columns">
        <div class="form-group">
            <label id="nom_film_label" for="nom_film">Nom du film :</label>
            <input type="text" id="nom_film" name="nom_film" placeholder="Nom du film (max 50 caractères)" maxlength="50" required>
        </div>
        <div class="form-group">
            <label id="categorie_label" for="categorie">Catégorie :</label>
            <select id="categorie" name="categorie" required>
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
                <option value="1">Inconnu</option>
            </select>
            <input type="text" id="nouveau_studio" name="nouveau_studio" placeholder="Nom du studio" maxlength="30" style="display:none;">
        </div>
        <div class="form-group">
            <label id="auteur_label" for="auteur">Auteur :</label>
            <select id="auteur" name="auteur_id" required>
                <option value="">Sélectionnez un auteur</option>
                <option value="autre">Autre</option>
                <option value="1">Inconnu</option>
                <?php foreach ($auteurs as $id => $nom) { 
                    if ($nom !== 'Inconnu') {
                        echo "<option value='$id'>$nom</option>"; 
                    }
                } ?>
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

    <input id="Bouton-proposer" type="submit" value="Proposer le film">
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

    // Fonction pour mettre à jour les studios selon la catégorie
    function updateStudios() {
        // Cette fonction est maintenant gérée par script-proposer.js
        // Garder cette fonction vide pour éviter les conflits
    }

    // Fonction pour mettre à jour les auteurs selon la catégorie
    function updateAuteurs() {
        let categorie = document.getElementById("categorie").value;
        let auteurSelect = document.getElementById("auteur");

        // Réinitialiser le select avec l'option de base et "Autre" en deuxième position
        auteurSelect.innerHTML = "<option value=''>Sélectionnez un auteur</option><option value='autre'>Autre</option>";
        
        // Ajouter l'option "Inconnu" toujours présente
        let optionInconnu = document.createElement("option");
        optionInconnu.value = "1";
        optionInconnu.textContent = "Inconnu";
        auteurSelect.appendChild(optionInconnu);

        if (!categorie) return;

        fetch("./proposer-film.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "categorie_auteurs=" + encodeURIComponent(categorie)
        })
        .then(response => response.text())
        .then(text => {
            console.log("Réponse brute du serveur (auteurs) :", text);
            return JSON.parse(text);
        })
        .then(data => {
            if (data.success) {
                Object.entries(data.auteurs).forEach(([id, nom]) => { 
                    // Éviter de dupliquer "Inconnu" qui est déjà ajouté manuellement
                    if (nom !== "Inconnu") {
                        let option = document.createElement("option");
                        option.value = id;
                        option.textContent = nom;
                        auteurSelect.appendChild(option);
                    }
                });
                }
        })
        .catch(error => console.error("Erreur AJAX (auteurs) :", error));
    }

    // Ajouter l'événement pour le changement de catégorie
    document.getElementById('categorie').addEventListener('change', function() {
        handleCategoryChange();
        updateStudios();
        updateAuteurs();
    });
</script>
            
            <div id="notification"></div>
        </div>
    </main>

    <script src="./scripts-js/script-proposer.js"></script>
    <script src="./scripts-js/notification-badge.js" defer></script>
    <footer>
        <p>&copy; 2025 lupistar.fr — Tous droits réservés.</p>
        <p>Les illustrations sont la propriété de leurs auteurs et éditeurs respectifs.</p>
        <nav>
            <a href="/mentions-legales.php">Mentions légales</a> | 
            <a href="/confidentialite.php">Politique de confidentialité</a>
        </nav>
    </footer>
</body>
</html>