<?php
/**
 * Script pour mettre à jour le compteur de vues des discussions
 * Appelé via AJAX depuis forum.php
 */

header('Content-Type: application/json');

// Vérifier la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['discussion_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de discussion manquant']);
    exit;
}

$discussion_id = (int)$input['discussion_id'];

if ($discussion_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de discussion invalide']);
    exit;
}

require_once './co-bdd.php';

try {
    // Vérifier si la discussion existe
    $stmt = $pdo->prepare("SELECT id, view_count FROM forum_discussions WHERE id = ?");
    $stmt->execute([$discussion_id]);
    $discussion = $stmt->fetch();
    
    if (!$discussion) {
        http_response_code(404);
        echo json_encode(['error' => 'Discussion non trouvée']);
        exit;
    }
    
    // Incrémenter le compteur de vues
    $stmt = $pdo->prepare("UPDATE forum_discussions SET view_count = view_count + 1 WHERE id = ?");
    $stmt->execute([$discussion_id]);
    
    $new_view_count = $discussion['view_count'] + 1;
    
    echo json_encode([
        'success' => true,
        'discussion_id' => $discussion_id,
        'view_count' => $new_view_count
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la mise à jour du compteur de vues: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données']);
}
?>