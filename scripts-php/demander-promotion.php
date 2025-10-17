<?php
session_start();
require_once 'co-bdd.php';
require_once 'controller-recompense.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $rewardController = new RecompenseController($pdo);
    
    // Demander la promotion directement avec la méthode appropriée
    $result = $rewardController->demanderPromotion($user_id);
    
    // Retourner le résultat JSON
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Erreur demande promotion: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur interne du serveur']);
}
?>