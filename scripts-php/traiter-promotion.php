<?php
session_start();
require_once 'co-bdd.php';
require_once 'controller-recompense.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['titre']) || 
    ($_SESSION['titre'] !== 'Admin' && $_SESSION['titre'] !== 'Super-Admin')) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$user_id = intval($input['user_id']);
$action = $input['action']; // 'approve' ou 'reject'

try {
    $rewardController = new RecompenseController($pdo);
    
    // Vérifier que l'utilisateur a bien une demande de promotion
    $sql_check = "SELECT demande_promotion, username, titre, recompenses FROM membres WHERE id = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$user_id]);
    $user = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || $user['demande_promotion'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Aucune demande de promotion trouvée pour cet utilisateur']);
        exit;
    }
    
    if ($action === 'approve') {
        // Approuver la promotion
        $result = $rewardController->approuverPromotion($user_id);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Promotion approuvée avec succès',
                'new_title' => $result['nouveau_titre']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'approbation de la promotion']);
        }
        
    } elseif ($action === 'reject') {
        // Rejeter la promotion
        $result = $rewardController->rejeterPromotion($user_id);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Demande de promotion rejetée']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors du rejet de la promotion']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
    
} catch (Exception $e) {
    error_log("Erreur traitement promotion: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur interne du serveur']);
}
?>