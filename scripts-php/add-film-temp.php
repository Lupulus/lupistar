<?php
session_start();

// Nom du fichier de log des erreurs
$errorLogFile = "../logs/error.txt";
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

// Vérification de la connexion utilisateur et du niveau
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$userTitle = $_SESSION['titre'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

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
if (!$isLoggedIn || $user_level <= 1 || !$userId) {
    logError("Tentative d'accès non autorisée à add-film-temp.php - Utilisateur: " . ($userId ?? 'inconnu') . " - Titre: " . $userTitle);
    die(json_encode(["error" => "Accès non autorisé."]));
}

// Connexion à la base de données
include __DIR__ . '/co-bdd.php';

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

        // Vérification des doublons dans les films existants ET les propositions en attente
        $stmt = $pdo->prepare("SELECT id FROM films WHERE LOWER(nom_film) = LOWER(?) LIMIT 1");
        $stmt->execute([$nom_film]);

        if ($stmt->rowCount() > 0) {
            logError("Tentative de proposition d'un film déjà existant : " . $nom_film . " par utilisateur " . $userId);
            die(json_encode(["error" => "⚠️ Ce film existe déjà dans la base de données."]));
        }

        // Vérifier aussi dans les propositions en attente
        $stmt = $pdo->prepare("SELECT id FROM films_temp WHERE LOWER(nom_film) = LOWER(?) AND statut = 'en_attente' LIMIT 1");
        $stmt->execute([$nom_film]);

        if ($stmt->rowCount() > 0) {
            logError("Tentative de proposition d'un film déjà proposé : " . $nom_film . " par utilisateur " . $userId);
            die(json_encode(["error" => "⚠️ Ce film a déjà été proposé et est en attente d'approbation."]));
        }

        if (!$nom_film || !$categorie || !$date_sortie) {
            die(json_encode(["error" => "Nom du film, catégorie et date de sortie sont obligatoires."]));
        }

        if (!isset($_POST['sous_genres']) || !is_array($_POST['sous_genres']) || count($_POST['sous_genres']) === 0) {
            logError("Aucun sous-genre sélectionné pour la proposition de " . $nom_film);
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
        
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
        
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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

        $studio_id = getDefaultOrInsert($pdo, "studios", $_POST['studio_id'] ?? '', $_POST['nouveau_studio'] ?? '', $_POST['categorie']);
        $auteur_id = getDefaultOrInsert($pdo, "auteurs", $_POST['auteur_id'] ?? '', $_POST['nouveau_auteur'] ?? '');
        $pays_id = getDefaultOrInsert($pdo, "pays", $_POST['pays_id'] ?? '', $_POST['nouveau_pays'] ?? '');

        // Gestion de l'image - stockage dans img-temp
        $image_path = null;

        if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = mime_content_type($_FILES["image"]["tmp_name"]);
            
            if (!in_array($fileType, $allowedTypes)) {
                logError("Fichier non autorisé : $fileType");
                die(json_encode(["error" => "⚠️ Seules les images JPG, PNG ou WEBP sont autorisées."]));
            }

            // Fonction pour nettoyer et formater le nom de l'image (stockage temporaire)
            function formatImageNameTemp($nom, $date_sortie, $ordre_suite, $saison, $categorie, $extension, $userId) {
                // Supprimer les accents
                $nom = iconv('UTF-8', 'ASCII//TRANSLIT', $nom);
                // Remplacer les espaces et caractères spéciaux par "_"
                $nom = preg_replace('/[^a-zA-Z0-9]/', '_', $nom);
                $nom = preg_replace('/_+/', '_', $nom);
                $nom = trim($nom, '_');
            
                // Si c'est une série, on utilise la saison à la place de l'ordre
                $numero = (in_array($categorie, ['Série', "Série d'Animation"]) && $saison) ? $saison : $ordre_suite;
            
                // Ajouter l'ID utilisateur et timestamp pour éviter les conflits
                $timestamp = time();
                return "../img-temp/" . $date_sortie . "-" . $nom . "_" . $numero . "_user" . $userId . "_" . $timestamp . "." . $extension;
            }        

            $extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $extension = strtolower($extension);
            $image_path = formatImageNameTemp($nom_film, $date_sortie, $ordre_suite, $saison, $categorie, $extension, $userId);

            // Compression si JPEG
            if ($fileType === 'image/jpeg' || $fileType === 'image/jpg') {
                $image = imagecreatefromjpeg($_FILES["image"]["tmp_name"]);
                if ($image) {
                    imagejpeg($image, $image_path, 90); // Qualité 90%
                    imagedestroy($image);
                } else {
                    logError("Erreur de lecture JPEG pour proposition.");
                    die(json_encode(["error" => "Erreur de lecture de l'image JPEG."]));
                }
            }
            // PNG ou WebP → simple déplacement sans compression
            else {
                if (!move_uploaded_file($_FILES["image"]["tmp_name"], $image_path)) {
                    logError("Erreur lors du déplacement de l'image de proposition : " . $_FILES["image"]["error"]);
                    die(json_encode(["error" => "Erreur lors de l'enregistrement de l'image."]));
                }
            }

        } else {
            logError("Aucune image reçue pour proposition ou erreur : " . $_FILES["image"]["error"]);
            die(json_encode(["error" => "⚠️ Une image valide est requise."]));
        }

        // Insertion dans la table films_temp
        $stmt = $pdo->prepare("INSERT INTO films_temp (nom_film, description, categorie, image_path, ordre_suite, saison, nbrEpisode, date_sortie, studio_id, auteur_id, pays_id, propose_par, statut)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')");
        
        if ($stmt->execute([$nom_film, $description, $categorie, $image_path, $ordre_suite, $saison, $nbrEpisode, $date_sortie, $studio_id, $auteur_id, $pays_id, $userId])) {
            $film_temp_id = $pdo->lastInsertId();

            // Gestion de la table de jointure films_temp_sous_genres
            if (!empty($sous_genres)) {
                $stmt_j = $pdo->prepare("INSERT INTO films_temp_sous_genres (film_temp_id, sous_genre_id) VALUES (?, ?)");

                foreach ($sous_genres as $sous_genre_id) {
                    $stmt_j->execute([$film_temp_id, $sous_genre_id]);
                }
            }

            logError("Nouvelle proposition de film soumise : " . $nom_film . " par utilisateur " . $userId . " (ID: " . $film_temp_id . ")");
            echo json_encode(["success" => "Votre proposition de film a été soumise avec succès ! Elle sera examinée par les administrateurs."]);
            exit();
        } else {
            logError("Erreur lors de l'ajout de la proposition de film");
            die(json_encode(["error" => "Erreur lors de la soumission de votre proposition."]));
        }

    } catch (PDOException $e) {
        logError("Erreur PDO add-film-temp: " . $e->getMessage());
        die(json_encode(["error" => "Erreur de base de données."]));
    }
}
?>