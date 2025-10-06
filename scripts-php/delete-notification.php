<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Utilisateur non connecté."]);
    exit;
}

// Inclure le fichier de configuration de la base de données
include 'co-bdd.php';

// Fonction pour enregistrer les erreurs
function logError($message) {
    error_log(date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, 3, '../logs/error.log');
}

// Vérifie si le formulaire a été soumis via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Nettoyer le buffer de sortie pour éviter les caractères parasites
        ob_clean();
        header('Content-Type: application/json');

        $notification_id = (int) $_POST['notification_id'];
        $user_id = (int) $_SESSION['user_id'];

        if (!$notification_id) {
            echo json_encode(["success" => false, "error" => "ID de notification manquant."]);
            exit;
        }

        // Vérifier que la notification appartient bien à l'utilisateur connecté
        $stmt = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(["success" => false, "error" => "Notification non trouvée ou non autorisée."]);
            exit;
        }

        // Supprimer la notification
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        
        if ($stmt->execute([$notification_id, $user_id])) {
            // Log d'information (pas d'erreur) pour la suppression réussie
            error_log(date('Y-m-d H:i:s') . " - INFO: Notification supprimée : ID " . $notification_id . " par utilisateur " . $user_id . PHP_EOL, 3, '../logs/info.log');
            echo json_encode(["success" => true, "message" => "Notification supprimée avec succès."]);
        } else {
            logError("Erreur lors de la suppression de la notification ID " . $notification_id);
            echo json_encode(["success" => false, "error" => "Erreur lors de la suppression de la notification."]);
            exit;
        }

    } catch (PDOException $e) {
        logError("Erreur PDO lors de la suppression de la notification ID " . $notification_id . " : " . $e->getMessage());
        echo json_encode(["success" => false, "error" => "Erreur lors de la suppression de la notification."]);
        exit;
    }

} else {
    echo json_encode(["success" => false, "error" => "Méthode non autorisée."]);
    exit;
}
?>