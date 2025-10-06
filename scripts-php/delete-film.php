<?php
// Connexion à la base de données
require_once 'co-bdd.php';

// Fonction pour enregistrer les erreurs
$errorLogFile = "/var/www/html/logs/error.txt";
function logError($message) {
    global $errorLogFile;
    file_put_contents($errorLogFile, "[" . date("Y-m-d H:i:s") . "] " . $message . "\n", FILE_APPEND | LOCK_EX);
}

// Fonction de suppression
function supprimerFilm($pdo, $filmId) {
    try {
        // Récupérer le chemin de l'image associée
        $stmt = $pdo->prepare("SELECT image_path FROM films WHERE id = ?");
        $stmt->execute([$filmId]);
        $image_path = $stmt->fetchColumn();

        // Supprimer les entrées correspondantes dans `membres_films_list`
        $stmt = $pdo->prepare("DELETE FROM membres_films_list WHERE films_id = ?");
        $stmt->execute([$filmId]);

        // Supprimer les entrées dans `films_sous_genres`
        $stmt = $pdo->prepare("DELETE FROM films_sous_genres WHERE film_id = ?");
        $stmt->execute([$filmId]);

        // Supprimer le film de la base de données
        $stmt = $pdo->prepare("DELETE FROM films WHERE id = ?");
        $stmt->execute([$filmId]);

        // Supprimer l'image du serveur
        if ($image_path && file_exists($image_path)) {
            unlink($image_path);
        }

        http_response_code(204); // No Content
        exit();
    } catch (PDOException $e) {
        logError("Erreur lors de la suppression du film ID: $filmId - " . $e->getMessage());
        die(json_encode(["error" => "Erreur lors de la suppression du film."]));
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$filmId = null;

if ($method === 'DELETE') {
    parse_str(file_get_contents("php://input"), $deleteData);
    if (isset($deleteData['id']) && is_numeric($deleteData['id'])) {
        $filmId = (int) $deleteData['id'];
    }
} elseif ($method === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $filmId = (int) $_POST['id'];
} else {
    http_response_code(405);
    echo json_encode(["error" => "Méthode non autorisée ou ID manquant."]);
    exit();
}

if ($filmId) {
    supprimerFilm($pdo, $filmId);
} else {
    echo json_encode(["error" => "ID de film invalide."]);
    exit();
}
?>