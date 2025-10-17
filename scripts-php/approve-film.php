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
    
    // Récupérer les données modifiées de la modal
    $nom_film = trim($_POST['nom_film'] ?? '');
    $categorie = trim($_POST['categorie'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date_sortie = trim($_POST['date_sortie'] ?? '');
    $ordre_suite = (int) ($_POST['ordre_suite'] ?? 0);
    $saison = !empty($_POST['saison']) ? (int) $_POST['saison'] : null;
    $nbrEpisode = !empty($_POST['nbrEpisode']) ? (int) $_POST['nbrEpisode'] : null;

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

        // Vérifier si le film n'existe pas déjà dans la table films (avec le nom modifié)
        $final_nom_film = $nom_film ?: $film_temp['nom_film'];
        $stmt = $pdo->prepare("SELECT id FROM films WHERE LOWER(nom_film) = LOWER(?) LIMIT 1");
        $stmt->execute([$final_nom_film]);

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
        
        // Gérer l'image (nouvelle ou existante)
        $old_image_path = $film_temp['image_path'];
        $new_image_path = null;
        
        // Vérifier si une nouvelle image a été téléchargée
        if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {
            // Traiter la nouvelle image
            $upload_file = $_FILES['new_image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($upload_file['type'], $allowed_types)) {
                throw new Exception("Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WEBP.");
            }
            
            if ($upload_file['size'] > 5 * 1024 * 1024) { // 5MB max
                throw new Exception("Le fichier est trop volumineux. Taille maximale: 5MB.");
            }
            
            // Déterminer l'extension basée sur le type MIME
            $mime_to_ext = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];
            $extension = $mime_to_ext[$upload_file['type']];
            
            // Générer le nouveau nom d'image
            $new_image_path = formatImageName(
                $final_nom_film, 
                $date_sortie ?: $film_temp['date_sortie'], 
                $ordre_suite ?: $film_temp['ordre_suite'], 
                $saison !== null ? $saison : $film_temp['saison'], 
                $categorie ?: $film_temp['categorie'], 
                $extension
            );
            
            // Vérifier si le dossier de destination existe
            $destination_dir = dirname($new_image_path);
            $absolute_destination_dir = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $destination_dir);
            
            if (!is_dir($absolute_destination_dir)) {
                if (!mkdir($absolute_destination_dir, 0755, true)) {
                    throw new Exception("Impossible de créer le dossier de destination : " . $destination_dir);
                }
            }
            
            // Déplacer la nouvelle image
            $absolute_new_path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $new_image_path);
            
            if (!move_uploaded_file($upload_file['tmp_name'], $absolute_new_path)) {
                throw new Exception("Impossible de sauvegarder la nouvelle image.");
            }
            
            // Supprimer l'ancienne image si elle existe
            if ($old_image_path) {
                $absolute_old_path = realpath(dirname(__FILE__) . '/' . $old_image_path);
                if ($absolute_old_path && file_exists($absolute_old_path)) {
                    unlink($absolute_old_path);
                }
            }
            
        } else {
            // Utiliser l'image existante et la déplacer si nécessaire
            if ($old_image_path) {
                // Convertir le chemin relatif en chemin absolu pour la vérification
                $absolute_old_path = realpath(dirname(__FILE__) . '/' . $old_image_path);
                
                // Vérifier si le fichier source existe
                if (!$absolute_old_path || !file_exists($absolute_old_path)) {
                    logError("Fichier image source introuvable : " . $old_image_path . " (chemin absolu: " . ($absolute_old_path ?: 'non résolu') . ") pour le film ID: " . $film_temp_id);
                    throw new Exception("Fichier image source introuvable : " . $old_image_path);
                }

                // Générer le nouveau nom d'image pour publiclisteimg (avec le nom modifié)
                 $extension = pathinfo($old_image_path, PATHINFO_EXTENSION);
                 
                 $new_image_path = formatImageName(
                     $final_nom_film, 
                     $date_sortie ?: $film_temp['date_sortie'], 
                     $ordre_suite ?: $film_temp['ordre_suite'], 
                     $saison !== null ? $saison : $film_temp['saison'], 
                     $categorie ?: $film_temp['categorie'], 
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
        }

        // Insérer dans la table films avec les données modifiées
        $stmt = $pdo->prepare("INSERT INTO films (nom_film, description, categorie, image_path, ordre_suite, saison, nbrEpisode, date_sortie, studio_id, auteur_id, pays_id)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $nom_film ?: $film_temp['nom_film'], 
            $description ?: $film_temp['description'], 
            $categorie ?: $film_temp['categorie'], 
            $new_image_path, 
            $ordre_suite ?: $film_temp['ordre_suite'], 
            $saison !== null ? $saison : $film_temp['saison'], 
            $nbrEpisode !== null ? $nbrEpisode : $film_temp['nbrEpisode'], 
            $date_sortie ?: $film_temp['date_sortie'], 
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

        // Vérifier les récompenses pour l'auteur du film approuvé
        include_once 'controller-recompense.php';
        $rewardController = new RewardController($pdo);
        $rewardController->verifierEtAttribuerRecompenses($film_temp['auteur_id']);

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