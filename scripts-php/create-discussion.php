<?php
session_start();
require_once './co-bdd.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour créer une discussion.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

// Validation des données
if ($category_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Catégorie invalide.']);
    exit;
}

if (empty($title) || strlen($title) < 3) {
    echo json_encode(['success' => false, 'message' => 'Le titre doit contenir au moins 3 caractères.']);
    exit;
}

if (empty($content) || strlen($content) < 10) {
    echo json_encode(['success' => false, 'message' => 'Le contenu doit contenir au moins 10 caractères.']);
    exit;
}

if (strlen($title) > 200) {
    echo json_encode(['success' => false, 'message' => 'Le titre ne peut pas dépasser 200 caractères.']);
    exit;
}

if (strlen($content) > 5000) {
    echo json_encode(['success' => false, 'message' => 'Le contenu ne peut pas dépasser 5000 caractères.']);
    exit;
}

try {
    // Vérifier que la catégorie existe et est accessible
    $stmt = $pdo->prepare("SELECT id, nom, admin_only FROM forum_categories WHERE id = ? AND active = 1");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        echo json_encode(['success' => false, 'message' => 'Catégorie introuvable.']);
        exit;
    }
    
    // Vérifier si l'utilisateur peut accéder à cette catégorie
    if ($category['admin_only']) {
        $stmt = $pdo->prepare("SELECT titre FROM membres WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || $user['titre'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas accès à cette catégorie.']);
            exit;
        }
    }
    
    // Vérifier si l'utilisateur n'est pas suspendu
    $stmt = $pdo->prepare("SELECT suspension_end FROM membres WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    
    if ($user_data && $user_data['suspension_end'] && strtotime($user_data['suspension_end']) > time()) {
        echo json_encode(['success' => false, 'message' => 'Vous êtes suspendu et ne pouvez pas créer de discussions.']);
        exit;
    }
    
    // Vérifier le rate limiting (max 5 discussions par heure)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM forum_discussions 
        WHERE author_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$user_id]);
    $recent_count = $stmt->fetch()['count'];
    
    if ($recent_count >= 5) {
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez créer que 5 discussions par heure. Veuillez patienter.']);
        exit;
    }
    
    // Créer la discussion
    $stmt = $pdo->prepare("
        INSERT INTO forum_discussions (category_id, titre, description, author_id, created_at, updated_at) 
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$category_id, $title, $content, $user_id]);
    
    $discussion_id = $pdo->lastInsertId();
    
    // Enregistrer l'action dans les logs de modération (pour le suivi)
    $stmt = $pdo->prepare("
        INSERT INTO forum_moderations (discussion_id, moderator_id, action_type, reason, created_at) 
        VALUES (?, ?, 'create', 'Discussion créée par l\'utilisateur', NOW())
    ");
    $stmt->execute([$discussion_id, $user_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Discussion créée avec succès !',
        'discussion_id' => $discussion_id,
        'redirect_url' => "../forum.php?category={$category_id}&discussion={$discussion_id}"
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la création de discussion: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de la discussion.']);
}
?>