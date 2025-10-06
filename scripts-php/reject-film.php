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
    logError("Tentative d'accès non autorisée à reject-film.php - Utilisateur: " . ($userId ?? 'inconnu') . " - Titre: " . $userTitle);
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
    $raison_rejet = trim($_POST['raison_rejet'] ?? '');
    $commentaire_admin = trim($_POST['commentaire_admin'] ?? '');

    if (!$film_temp_id) {
        die(json_encode(["error" => "ID de film temporaire manquant."]));
    }

    if (empty($raison_rejet)) {
        die(json_encode(["error" => "Une raison de rejet est requise."]));
    }

    try {
        // Récupérer les données du film temporaire
        $stmt = $pdo->prepare("SELECT * FROM films_temp WHERE id = ? AND statut = 'en_attente'");
        $stmt->execute([$film_temp_id]);

        if ($stmt->rowCount() === 0) {
            die(json_encode(["error" => "Film temporaire non trouvé ou déjà traité."]));
        }

        $film_temp = $stmt->fetch(PDO::FETCH_ASSOC);

        // Commencer une transaction
        $pdo->beginTransaction();

        // Supprimer l'image temporaire si elle existe
        $image_path = $film_temp['image_path'];
        if ($image_path && file_exists($image_path)) {
            if (!unlink($image_path)) {
                logError("Impossible de supprimer l'image temporaire : " . $image_path);
                // Ne pas arrêter le processus pour cette erreur
            }
        }

        // Créer une notification pour l'utilisateur qui a proposé le film
        $titre_notification = "Film non approuvé";
        $message_notification = "Votre proposition de film pour \"" . $film_temp['nom_film'] . "\" n'a pas été approuvée pour la raison : " . $raison_rejet;
        if (!empty($commentaire_admin)) {
            $message_notification .= ". Commentaire administrateur : " . $commentaire_admin;
        }
        
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, titre, message, type) VALUES (?, ?, ?, 'film_rejection')");
        $stmt->execute([$film_temp['propose_par'], $titre_notification, $message_notification]);

        // Supprimer les sous-genres associés
        $stmt = $pdo->prepare("DELETE FROM films_temp_sous_genres WHERE film_temp_id = ?");
        $stmt->execute([$film_temp_id]);

        // Supprimer complètement le film temporaire de la table films_temp
        $stmt = $pdo->prepare("DELETE FROM films_temp WHERE id = ?");
        $stmt->execute([$film_temp_id]);

        // Valider la transaction
        $pdo->commit();

        logError("Film rejeté et supprimé : " . $film_temp['nom_film'] . " (ID temp: " . $film_temp_id . ") par admin " . $userId . " - Raison: " . $raison_rejet);
        echo json_encode(["success" => "Film rejeté et supprimé avec succès."]);

    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $pdo->rollback();

        logError("Erreur lors du rejet du film (ID temp: " . $film_temp_id . ") : " . $e->getMessage());
        die(json_encode(["error" => "Erreur lors du rejet : " . $e->getMessage()]));
    }
}
?>