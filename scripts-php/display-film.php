<?php
// Activer les erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour calculer la couleur en fonction de la date
function getDateColor($date) {
    // Convertir la date en une valeur entre 0 et 1
    $normalized_date = ($date - 1900) / (2099 - 1900);

    // Interpolation linéaire entre le rouge et la couleur #f8d276 pour les années 1900 à 1999
    if ($date <= 1999) {
        $hue = 0 + $normalized_date * (35 - 0); // Rouge à couleur #f8d276
    } 
    // Interpolation linéaire entre la couleur #f8d276 et le bleu pour les années 1999 à 2004
    elseif ($date <= 2004) {
        $hue = 35 + $normalized_date * (200 - 35); // Couleur #f8d276 à Bleu
    }
    // Interpolation linéaire entre le bleu et la couleur #007f1e pour les années 2005 à 2099
    else {
        $hue = 200 + $normalized_date * (120 - 200); // Bleu à couleur #007f1e
    }

    // Retourne une couleur au format HSL
    return "hsl($hue, 100%, 50%)";
}


// Nom du fichier de log des erreurs
$errorLogFile = "/var/www/html/logs/error.txt";

// Fonction pour enregistrer les erreurs
function logError($message) {
    global $errorLogFile;
    file_put_contents($errorLogFile, "[" . date("Y-m-d H:i:s") . "] " . $message . "\n", FILE_APPEND | LOCK_EX);
}

// Connexion à la base de données
require_once 'co-bdd.php';

// Fonction pour récupérer les sous-genres d'un film
function getSousGenres($pdo, $film_id) {
    $sousGenres = [];
    $sql = "SELECT sg.id, sg.nom FROM films_sous_genres fsg 
            JOIN sous_genres sg ON fsg.sous_genre_id = sg.id 
            WHERE fsg.film_id = ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$film_id]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sousGenres[$row['id']] = $row['nom'];
        }
    } catch (PDOException $e) {
        logError("Erreur lors de la récupération des sous-genres : " . $e->getMessage());
    }

    return $sousGenres;
}

// Récupération des options dynamiques
function getOptions($pdo, $table) {
    $options = [];
    $sql = "SELECT id, nom FROM $table ORDER BY nom ASC";
    
    try {
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $options[$row['id']] = $row['nom'];
        }
    } catch (PDOException $e) {
        logError("Erreur lors de la récupération des options pour $table : " . $e->getMessage());
    }
    
    return $options;
}

$studios = getOptions($pdo, "studios");
$auteurs = getOptions($pdo, "auteurs");
$pays = getOptions($pdo, "pays");
$sous_genres_list = getOptions($pdo, "sous_genres");

// Récupérer les films avec leurs relations
$sql = "SELECT f.id, f.nom_film, f.description, f.categorie, f.image_path, f.ordre_suite, f.date_sortie, f.saison, f.nbrEpisode,
               COALESCE(s.id, 0) AS studio_id, COALESCE(s.nom, 'Inconnu') AS studio, 
               COALESCE(a.id, 0) AS auteur_id, COALESCE(a.nom, 'Inconnu') AS auteur, 
               COALESCE(p.id, 0) AS pays_id, COALESCE(p.nom, 'Inconnu') AS pays
        FROM films f
        LEFT JOIN studios s ON f.studio_id = s.id
        LEFT JOIN auteurs a ON f.auteur_id = a.id
        LEFT JOIN pays p ON f.pays_id = p.id
        ORDER BY f.id DESC";

try {
    $result = $pdo->query($sql);

    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $sousGenres = getSousGenres($pdo, $row['id']);
        $date = $row['date_sortie'];
        $background_color_date = getDateColor($date);

        echo "<div class='film-item' data-id='" . htmlspecialchars($row['id']) . "'>";
            echo "<div class='film-image'><img src='" . htmlspecialchars($row['image_path']) . "' alt='" . htmlspecialchars($row['nom_film']) . "'></div>";

            echo "<div class='date-sortie' style='background-color: $background_color_date; display: flex; align-items: center;
            justify-content: center; text-align: center; grid-column: 13; grid-row: 1/6; width: auto;'>Date de diffusion: $date</div>";

            //echo "<div class='film-details'>";
                echo "<h3 id='film-nom'>" . htmlspecialchars($row['nom_film']) . "</h3>";
                echo "<p id='film-categorie'><u>Catégorie:</u> " . htmlspecialchars($row['categorie']) . "</p>";
                echo "<p id='film-studio'><u>Studio:</u> " . htmlspecialchars($row['studio']) . "</p>";
                echo "<p id='film-auteur'><u>Auteur:</u> " . htmlspecialchars($row['auteur']) . "</p>";
                echo "<p id='film-pays'><u>Pays:</u> " . htmlspecialchars($row['pays']) . "</p>";
                echo "<p id='film-sous-genres'><u>Sous-genres:</u> " . (!empty($sousGenres) ? implode(", ", $sousGenres) : "Aucun sous-genre") . "</p>";
                echo "<p id='film-description'><u>Description:</u> " . htmlspecialchars($row['description']) . "</p>";

                if (in_array($row['categorie'], ['Série', 'Série d\'Animation'])) {
                    echo "<p id='film-saison'><u>Saison :</u> " . htmlspecialchars($row['saison'] ?? '-') . "</p>";
                    echo "<p id='film-episodes'><u>Nombre d’épisodes :</u> " . htmlspecialchars($row['nbrEpisode'] ?? '-') . "</p>";
                }
                // Actions
                echo "<div class='action-buttons'>";
                echo "<button class='modify-btn' onclick='showModifyForm(" . htmlspecialchars($row['id']) . ")'>Modifier</button>";
                echo "<button class='delete-btn' onclick='deleteFilm(" . $row['id'] . ")'>Supprimer</button>";
                echo "</div>"; //div action-button
            //echo "</div>"; //div film-detail
        echo "</div>"; //div film-item

        // 📌 **Formulaire de modification**
        echo "<div class='modify-form' id='modify-form-" . htmlspecialchars($row['id']) . "' style='display: none;'>";
        echo "<form action='modify-film.php' method='post' class='modify-form' id='modify-form-" . htmlspecialchars($row['id']) . "-form' style='display: none;' onsubmit='modifyFilm(" . htmlspecialchars($row['id']) . "); return false;'>";
        echo "<div class='form-container'>";
        echo "<input type='hidden' name='film_id' value='" . htmlspecialchars($row['id']) . "'>"; // Champ caché pour l'ID du film

        echo "<label>Nom du film :</label>";
        echo "<input type='text' name='nom_film' value='" . htmlspecialchars($row['nom_film'], ENT_QUOTES, 'UTF-8') . "' required><br>";

        echo "<label>Catégorie :</label>";
        echo "<select name='categorie' required>";
        foreach (["Film", "Animation", "Anime", "Série", "Série d'Animation"] as $cat) {
            $catEscaped = htmlspecialchars($cat, ENT_QUOTES, 'UTF-8');
            $selected = ($row['categorie'] === $cat) ? " selected" : "";
            echo "<option value=\"$catEscaped\"$selected>$catEscaped</option>";
        }        
        $categorie = $row['categorie'];
        $isSerie = in_array($categorie, ['Série', 'Série d\'Animation']);

        echo "<label for='description'>Description :</label><br>";
        echo "<textarea id='description_" . htmlspecialchars($row['id']) . "' name='description' rows='4' cols='50' placeholder='Pas de description'>" . htmlspecialchars_decode($row['description']) . "</textarea><br>";

        if (!$isSerie) {
            echo "<label for='ordre_suite'>Ordre du film (Suite?) :</label>";
            echo "<input type='number' id='ordre_suite_" . htmlspecialchars($row['id']) . "' name='ordre_suite' min='1' max='25' step='1' value='" . $row['ordre_suite'] . "'><br>";
        } else {
            echo "<label for='saison'>Numéro de saison :</label>";
            echo "<input type='number' id='saison_" . htmlspecialchars($row['id']) . "' name='saison' min='1' max='100' value='" . ($row['saison'] ?? 1) . "' required><br>";
        
            echo "<label for='nbrEpisode'>Nombre d’épisodes :</label>";
            echo "<input type='number' id='nbrEpisode_" . htmlspecialchars($row['id']) . "' name='nbrEpisode' min='1' max='9999' value='" . ($row['nbrEpisode'] ?? '') . "' required><br>";
        }
                
        echo "<label for='date_sortie'>Date de sortie : </label>";
        echo "<input type='number' id='date_sortie_" . htmlspecialchars($row['id']) . "' name='date_sortie' min='1900' max='2099' step='1' value='" . $row['date_sortie'] . "' required><br>";

        echo "</select><br>";

        echo "<label>Studio :</label>";
        echo "<select name='studio_id'>";
        foreach ($studios as $id => $nom) {
            echo "<option value='$id'" . ($row['studio_id'] == $id ? ' selected' : '') . ">$nom</option>";
        }
        echo "</select><br>";

        echo "<label>Auteur :</label>";
        echo "<select name='auteur_id'>";
        foreach ($auteurs as $id => $nom) {
            echo "<option value='$id'" . ($row['auteur_id'] == $id ? ' selected' : '') . ">$nom</option>";
        }
        echo "</select><br>";

        echo "<label>Pays :</label>";
        echo "<select name='pays_id'>";
        foreach ($pays as $id => $nom) {
            echo "<option value='$id'" . ($row['pays_id'] == $id ? ' selected' : '') . ">$nom</option>";
        }
        echo "</select><br>";

        echo "<label>Sous-genres :</label>";
        foreach ($sous_genres_list as $id => $nom) {
            $checked = isset($sousGenres[$id]) ? "checked" : "";
            echo "<input type='checkbox' name='sous_genres[]' value='$id' $checked> $nom<br>";
        }

        echo "<button type='submit'>Enregistrer</button>";
        echo "<button type='button' onclick='hideModifyForm(" . htmlspecialchars($row['id']) . ")'>Annuler</button>";
        echo "</div></form></div>";
    }
} else {
    echo "Aucun film trouvé.";
}

} catch (PDOException $e) {
    logError("Erreur lors de la récupération des films : " . $e->getMessage());
    echo "Erreur lors de la récupération des films.";
}
?>