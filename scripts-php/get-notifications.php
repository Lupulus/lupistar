<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["error" => "Utilisateur non connecté."]));
}

// Inclure le fichier de configuration de la base de données
include './co-bdd.php';

// Fonction pour enregistrer les erreurs
function logError($message) {
    error_log(date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, 3, '../logs/error.log');
}

// Vérifie si la requête est en GET
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {
        // Nettoyer le buffer de sortie pour éviter les caractères parasites
        ob_clean();
        header('Content-Type: application/json');

        $user_id = (int) $_SESSION['user_id'];

        // Récupérer toutes les notifications de l'utilisateur, triées par date de création (plus récentes en premier)
        $stmt = $pdo->prepare("SELECT id, titre, message, type, lu, date_creation FROM notifications WHERE user_id = ? ORDER BY date_creation DESC");
        $stmt->execute([$user_id]);

        $notifications = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = [
                'id' => $row['id'],
                'titre' => $row['titre'],
                'message' => $row['message'],
                'type' => $row['type'],
                'lu' => (bool) $row['lu'],
                'date_creation' => $row['date_creation']
            ];
        }

        echo json_encode([
            "success" => true,
            "notifications" => $notifications,
            "count" => count($notifications)
        ]);

    } catch (PDOException $e) {
        logError("Erreur PDO get-notifications: " . $e->getMessage());
        die(json_encode(["error" => "Erreur de base de données."]));
    }

} else {
    die(json_encode(["error" => "Méthode non autorisée."]));
}
?>