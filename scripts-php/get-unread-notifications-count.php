<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["error" => "Utilisateur non connecté.", "count" => 0]));
}

// Inclure le fichier de configuration de la base de données
include './co-bdd.php';

// Fonction pour enregistrer les erreurs
function logError($message) {
    error_log(date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, 3, '../logs/error.log');
}

// Nettoyer le buffer de sortie pour éviter les caractères parasites
ob_clean();
header('Content-Type: application/json');

$user_id = (int) $_SESSION['user_id'];

try {
    // Récupérer le nombre de notifications non lues de l'utilisateur
    $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND lu = 0");
    $stmt->execute([$user_id]);
    
    $unread_count = 0;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $unread_count = (int) $row['unread_count'];
    }

    echo json_encode([
        "success" => true,
        "count" => $unread_count
    ]);
} catch (PDOException $e) {
    logError("Erreur PDO dans get-unread-notifications-count.php: " . $e->getMessage());
    echo json_encode([
        "error" => "Erreur lors de la récupération des notifications.",
        "count" => 0
    ]);
}
?>