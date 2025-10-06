<?php
// Nom du fichier de log des erreurs
$errorLogFile = "/var/www/html/logs/error.txt";
if (!file_exists(dirname($errorLogFile))) {
    mkdir(dirname($errorLogFile), 0777, true);
}
if (!file_exists($errorLogFile)) {
    touch($errorLogFile);
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour enregistrer les erreurs
function logError($message) {
    global $errorLogFile;
    file_put_contents($errorLogFile, "[" . date("Y-m-d H:i:s") . "] " . $message . "\n", FILE_APPEND | LOCK_EX);
}

// Connexion à la base de données
include './co-bdd.php';

// Vérifie si le formulaire a été soumis via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');

    try {
        // Récupération des données du formulaire
        $nom_film = trim($_POST['nom_film']);
        $description = !empty($_POST['description']) ? $_POST['description'] : "Pas de description";
        $categorie = trim($_POST['categorie']);
        $ordre_suite = !empty($_POST['ordre_suite']) ? (int) $_POST['ordre_suite'] : 1;
        $date_sortie = (int) $_POST['date_sortie'];
        $saison = isset($_POST['saison']) && $_POST['saison'] !== '' ? (int) $_POST['saison'] : null;
        $nbrEpisode = isset($_POST['nbrEpisode']) && $_POST['nbrEpisode'] !== '' ? (int) $_POST['nbrEpisode'] : null;

        // Vérification des doublons (insensible à la casse)
        $stmt = $pdo->prepare("SELECT id FROM films WHERE LOWER(nom_film) = LOWER(?) LIMIT 1");
        $stmt->execute([$nom_film]);
        $result = $stmt->fetch();

        if ($result) {
            logError("Tentative d'ajout d'un film en double : " . $nom_film);
            die(json_encode(["error" => "⚠️ Ce film existe déjà dans la base de données."]));
        }

        if (!$nom_film || !$categorie || !$date_sortie) {
            die(json_encode(["error" => "Nom du film, catégorie et date de sortie sont obligatoires."]));
        }

        if (!isset($_POST['sous_genres']) || !is_array($_POST['sous_genres']) || count($_POST['sous_genres']) === 0) {
            logError("Aucun sous-genre sélectionné.");
            die(json_encode(["error" => "⚠️ Vous devez sélectionner au moins un sous-genre."]));
        }
        $sous_genres = $_POST['sous_genres'];
        
        // Fonction pour insérer ou récupérer l'ID d'une entité (studio, auteur, pays)
        function getOrInsertId($pdo, $table, $nom, $categorie = null) {
            $nom = trim($nom);
            if (empty($nom)) return null;
        
            if ($table === "studios") {
                // Appliquer la conversion de studio si disponible
                require_once 'studio-converter.php';
                $converter = new StudioConverter();
                $nom = $converter->convertStudio($nom);
                
                // Vérifier si le studio existe déjà
                $stmt = $pdo->prepare("SELECT id, categorie FROM studios WHERE nom = ?");
                $stmt->execute([$nom]);
                $row = $stmt->fetch();
        
                if ($row) {
                    // Si le studio existe déjà, mettre à jour la catégorie si nécessaire
                    $id = $row['id'];
                    $categoriesExistantes = explode(',', $row['categorie'] ?? '');
        
                    if ($categorie && !in_array($categorie, $categoriesExistantes)) {
                        $categoriesExistantes[] = $categorie;
                        $nouvellesCategories = implode(',', $categoriesExistantes);
        
                        $stmt = $pdo->prepare("UPDATE studios SET categorie = ? WHERE id = ?");
                        $stmt->execute([$nouvellesCategories, $id]);
                    }
                    return $id;
                }
        
                // Insérer une nouvelle entrée pour un studio
                $stmt = $pdo->prepare("INSERT INTO studios (nom, categorie) VALUES (?, ?)");
                $stmt->execute([$nom, $categorie]);
            } else {
                // Vérifier si l'auteur ou le pays existe déjà
                $stmt = $pdo->prepare("SELECT id FROM $table WHERE nom = ?");
                $stmt->execute([$nom]);
                $row = $stmt->fetch();
        
                if ($row) {
                    return $row['id'];
                }
        
                // Insérer une nouvelle entrée pour un auteur ou un pays
                $stmt = $pdo->prepare("INSERT INTO $table (nom) VALUES (?)");
                $stmt->execute([$nom]);
            }
        
        return $pdo->lastInsertId();
    }
     
    // Vérifier et ajouter un nouveau studio, auteur et pays si nécessaire
    function getDefaultOrInsert($pdo, $table, $select_value, $input_value, $categorie = null) {
        if (empty($select_value) || $select_value === "Sélectionnez un ...") {
            return getOrInsertId($pdo, $table, "Inconnu");
        }
    
        if ($select_value === "autre") {
            if (empty($input_value)) {
                return getOrInsertId($pdo, $table, "Inconnu");
            }
            return getOrInsertId($pdo, $table, $input_value, ($table === "studios" ? $categorie : null));
        }
        return (int) $select_value;
    }

    $studio_id = getDefaultOrInsert($pdo, "studios", $_POST['studio_id'], $_POST['nouveau_studio'], $_POST['categorie']);
    $auteur_id = getDefaultOrInsert($pdo, "auteurs", $_POST['auteur_id'], $_POST['nouveau_auteur']);
    $pays_id = getDefaultOrInsert($pdo, "pays", $_POST['pays_id'], $_POST['nouveau_pays']);

    // Gestion de l'image
    $image_path = null;

    if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $fileType = mime_content_type($_FILES["image"]["tmp_name"]);
        
        if (!in_array($fileType, $allowedTypes)) {
            logError("Fichier non autorisé : $fileType");
            die(json_encode(["error" => "⚠️ Seules les images JPG, PNG ou WEBP sont autorisées."]));
        }

        // 🔤 Fonction pour nettoyer et formater le nom de l'image
        function formatImageName($nom, $date_sortie, $ordre_suite, $saison, $categorie, $extension) {
            // Supprimer les accents
            $nom = iconv('UTF-8', 'ASCII//TRANSLIT', $nom);
            // Remplacer les espaces et caractères spéciaux par "_"
            $nom = preg_replace('/[^a-zA-Z0-9]/', '_', $nom);
            $nom = preg_replace('/_+/', '_', $nom);
            $nom = trim($nom, '_');
        
            // Si c'est une série, on utilise la saison à la place de l'ordre
            $numero = (in_array($categorie, ['Série', "Série d'Animation"]) && $saison) ? $saison : $ordre_suite;
        
            return "../publiclisteimg/" . $date_sortie . "-" . $nom . "_" . $numero . "." . $extension;
        }        

        $extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $extension = strtolower($extension);
        $image_path = formatImageName($nom_film, $date_sortie, $ordre_suite, $saison, $categorie, $extension);

        // Compression si JPEG
        if ($fileType === 'image/jpeg' || $fileType === 'image/jpg') {
            $image = imagecreatefromjpeg($_FILES["image"]["tmp_name"]);
            if ($image) {
                imagejpeg($image, $image_path, 90); // Qualité X%
                imagedestroy($image);
            } else {
                logError("Erreur de lecture JPEG.");
                die(json_encode(["error" => "Erreur de lecture de l'image JPEG."]));
            }
        }
        // PNG ou WebP → simple déplacement sans compression (ou adaptation possible)
        else {
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $image_path)) {
                logError("Erreur lors du déplacement de l'image : " . $_FILES["image"]["error"]);
                die(json_encode(["error" => "Erreur lors de l'enregistrement de l'image."]));
            }
        }

    } else {
        logError("Aucune image reçue ou erreur : " . $_FILES["image"]["error"]);
        die(json_encode(["error" => "⚠️ Une image valide est requise."]));
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO films (nom_film, description, categorie, image_path, ordre_suite, saison, nbrEpisode, date_sortie, studio_id, auteur_id, pays_id)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom_film, $description, $categorie, $image_path, $ordre_suite, $saison, $nbrEpisode, $date_sortie, $studio_id, $auteur_id, $pays_id]);
        
        $film_id = $pdo->lastInsertId();

        // Gestion de la table de jointure films_sous_genres
        if (!empty($sous_genres)) {
            $stmt_j = $pdo->prepare("INSERT INTO films_sous_genres (film_id, sous_genre_id) VALUES (?, ?)");
            
            foreach ($sous_genres as $sous_genre_id) {
                $stmt_j->execute([$film_id, $sous_genre_id]);
            }
        }

        echo json_encode(["success" => "Film ajouté avec succès !"]);
        exit();
    } catch (PDOException $e) {
        logError("Erreur lors de l'ajout du film : " . $e->getMessage());
        die(json_encode(["error" => "Erreur lors de l'ajout du film."]));
    } catch (Exception $e) {
        logError("Erreur générale : " . $e->getMessage());
        die(json_encode(["error" => "Une erreur est survenue."]));
    }
    } catch (PDOException $e) {
        logError("Erreur lors de l'ajout du film : " . $e->getMessage());
        die(json_encode(["error" => "Erreur lors de l'ajout du film."]));
    } catch (Exception $e) {
        logError("Erreur générale : " . $e->getMessage());
        die(json_encode(["error" => "Une erreur est survenue."]));
    }
}
?>