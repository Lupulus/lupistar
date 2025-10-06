<?php
session_start();

// Désactiver l'affichage des erreurs pour éviter de corrompre la réponse JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Nom du fichier de log des erreurs
$errorLogFile = "/var/www/html/logs/error.txt";
if (!file_exists(dirname($errorLogFile))) {
    mkdir(dirname($errorLogFile), 0777, true);
}
if (!file_exists($errorLogFile)) {
    touch($errorLogFile);
}

// Fonction pour enregistrer les erreurs
function logError($message) {
    global $errorLogFile;
    file_put_contents($errorLogFile, "[" . date("Y-m-d H:i:s") . "] " . $message . "\n", FILE_APPEND | LOCK_EX);
}

// Vérification de la connexion utilisateur et du niveau admin
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

// Vérifier si l'utilisateur est admin
if (!$isLoggedIn || $user_level <= 4 || !$userId) {
    logError("Tentative d'accès non autorisée à approve-film.php - Utilisateur: " . ($userId ?? 'inconnu') . " - Titre: " . $userTitle);
    die(json_encode(["error" => "Accès non autorisé."]));
}

// Connexion à la base de données
require_once 'co-bdd.php';

// Vérifie si le formulaire a été soumis via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Nettoyer le buffer de sortie pour éviter les caractères parasites
    ob_clean();
    header('Content-Type: application/json');

    $film_temp_id = (int) $_POST['film_temp_id'];
    $commentaire_admin = trim($_POST['commentaire_admin'] ?? '');

    if (!$film_temp_id) {
        die(json_encode(["error" => "ID de film temporaire manquant."]));
    }

    try {
        // Récupérer les données du film temporaire
        $stmt = $pdo->prepare("SELECT * FROM films_temp WHERE id = ? AND statut = 'en_attente'");
        $stmt->execute([$film_temp_id]);

        if ($stmt->rowCount() === 0) {
            die(json_encode(["error" => "Film temporaire non trouvé ou déjà traité."]));
        }

        $film_temp = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérifier si le film n'existe pas déjà dans la table films
        $stmt = $pdo->prepare("SELECT id FROM films WHERE LOWER(nom_film) = LOWER(?) LIMIT 1");
        $stmt->execute([$film_temp['nom_film']]);

        if ($stmt->rowCount() > 0) {
            // Marquer comme rejeté avec commentaire
            $stmt = $pdo->prepare("UPDATE films_temp SET statut = 'rejete', commentaire_admin = ? WHERE id = ?");
            $commentaire_rejet = "Film déjà existant dans la base de données.";
            $stmt->execute([$commentaire_rejet, $film_temp_id]);
            
            logError("Tentative d'approbation d'un film déjà existant : " . $film_temp['nom_film'] . " (ID temp: " . $film_temp_id . ")");
            die(json_encode(["error" => "Ce film existe déjà dans la base de données."]));
        }

        // Commencer une transaction
        $pdo->beginTransaction();
        // Déplacer l'image de img-temp vers publiclisteimg
        $old_image_path = $film_temp['image_path'];
        $new_image_path = null;

        if ($old_image_path) {
            // Convertir le chemin relatif en chemin absolu pour la vérification
            $absolute_old_path = realpath(dirname(__FILE__) . '/' . $old_image_path);
            
            // Vérifier si le fichier source existe
            if (!$absolute_old_path || !file_exists($absolute_old_path)) {
                logError("Fichier image source introuvable : " . $old_image_path . " (chemin absolu: " . ($absolute_old_path ?: 'non résolu') . ") pour le film ID: " . $film_temp_id);
                throw new Exception("Fichier image source introuvable : " . $old_image_path);
            }

            // Générer le nouveau nom d'image pour publiclisteimg
            $extension = pathinfo($old_image_path, PATHINFO_EXTENSION);
            
            // Fonction pour formater le nom de l'image (version officielle)
            function formatImageName($nom, $date_sortie, $ordre_suite, $saison, $categorie, $extension) {
                // Supprimer les accents
                $nom = iconv('UTF-8', 'ASCII//TRANSLIT', $nom);
                // Remplacer les espaces et caractères spéciaux par "_"
                $nom = preg_replace('/[^a-zA-Z0-9]/', '_', $nom);
                $nom = preg_replace('/_+/', '_', $nom);
                $nom = trim($nom, '_');
            
                // Si c'est une série, on utilise la saison à la place de l'ordre
                $numero = (in_array($categorie, ['Série', "Série d'Animation"]) && $saison) ? $saison : $ordre_suite;
            
                // Chemin correct depuis le dossier scripts-php vers publiclisteimg
                return "../publiclisteimg/" . $date_sortie . "-" . $nom . "_" . $numero . "." . $extension;
            }

            $new_image_path = formatImageName(
                $film_temp['nom_film'], 
                $film_temp['date_sortie'], 
                $film_temp['ordre_suite'], 
                $film_temp['saison'], 
                $film_temp['categorie'], 
                $extension
            );

            // Vérifier si le dossier de destination existe
            $destination_dir = dirname($new_image_path);
            $absolute_destination_dir = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $destination_dir);
            
            if (!is_dir($absolute_destination_dir)) {
                logError("Dossier de destination introuvable : " . $destination_dir . " (chemin absolu: " . $absolute_destination_dir . ")");
                throw new Exception("Dossier de destination introuvable : " . $destination_dir);
            }

            // Convertir les chemins en chemins absolus pour l'opération rename
            $absolute_new_path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $new_image_path);
            
            // Déplacer l'image
            if (!rename($absolute_old_path, $absolute_new_path)) {
                logError("Échec du déplacement de l'image de " . $absolute_old_path . " vers " . $absolute_new_path);
                throw new Exception("Impossible de déplacer l'image vers le dossier final.");
            }

            logError("Image déplacée avec succès de " . $old_image_path . " vers " . $new_image_path);
        }

        // Insérer dans la table films
        $stmt = $pdo->prepare("INSERT INTO films (nom_film, description, categorie, image_path, ordre_suite, saison, nbrEpisode, date_sortie, studio_id, auteur_id, pays_id)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $film_temp['nom_film'], 
            $film_temp['description'], 
            $film_temp['categorie'], 
            $new_image_path, 
            $film_temp['ordre_suite'], 
            $film_temp['saison'], 
            $film_temp['nbrEpisode'], 
            $film_temp['date_sortie'], 
            $film_temp['studio_id'], 
            $film_temp['auteur_id'], 
            $film_temp['pays_id']
        ]);

        $new_film_id = $pdo->lastInsertId();

        // Récupérer et insérer les sous-genres
        $stmt = $pdo->prepare("SELECT sous_genre_id FROM films_temp_sous_genres WHERE film_temp_id = ?");
        $stmt->execute([$film_temp_id]);
        $sous_genres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt_insert_sg = $pdo->prepare("INSERT INTO films_sous_genres (film_id, sous_genre_id) VALUES (?, ?)");
        foreach ($sous_genres as $sg_row) {
            $stmt_insert_sg->execute([$new_film_id, $sg_row['sous_genre_id']]);
        }

        // Marquer le film temporaire comme approuvé et nettoyer les colonnes spécifiées
        $stmt = $pdo->prepare("UPDATE films_temp SET statut = 'approuve', commentaire_admin = ?, description = NULL, image_path = NULL, saison = NULL, nbrEpisode = NULL, studio_id = NULL, auteur_id = NULL, pays_id = NULL WHERE id = ?");
        $stmt->execute([$commentaire_admin, $film_temp_id]);

        // Valider la transaction
        $pdo->commit();

        logError("Film approuvé et publié : " . $film_temp['nom_film'] . " (ID temp: " . $film_temp_id . " -> ID film: " . $new_film_id . ") par admin " . $userId);
        echo json_encode(["success" => "Film approuvé et publié avec succès !"]);

    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $pdo->rollback();
        
        // Restaurer l'image si elle a été déplacée
        if (isset($new_image_path) && isset($old_image_path) && file_exists($new_image_path)) {
            rename($new_image_path, $old_image_path);
        }

        logError("Erreur lors de l'approbation du film (ID temp: " . $film_temp_id . ") : " . $e->getMessage());
        die(json_encode(["error" => "Erreur lors de l'approbation : " . $e->getMessage()]));
    }
}
?>