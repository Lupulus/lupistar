<?php
session_start();
require_once './co-bdd.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour commenter.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$discussion_id = isset($_POST['discussion_id']) ? (int)$_POST['discussion_id'] : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

// Validation des données
if ($discussion_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Discussion invalide.']);
    exit;
}

if (empty($content) || strlen($content) < 3) {
    echo json_encode(['success' => false, 'message' => 'Le commentaire doit contenir au moins 3 caractères.']);
    exit;
}

if (strlen($content) > 2000) {
    echo json_encode(['success' => false, 'message' => 'Le commentaire ne peut pas dépasser 2000 caractères.']);
    exit;
}

try {
    // Vérifier que la discussion existe et n'est pas verrouillée
    $stmt = $pdo->prepare("
        SELECT d.*, c.admin_only, c.nom as category_name 
        FROM forum_discussions d 
        LEFT JOIN forum_categories c ON d.category_id = c.id 
        WHERE d.id = ?
    ");
    $stmt->execute([$discussion_id]);
    $discussion = $stmt->fetch();
    
    if (!$discussion) {
        echo json_encode(['success' => false, 'message' => 'Discussion introuvable.']);
        exit;
    }
    
    if ($discussion['locked']) {
        echo json_encode(['success' => false, 'message' => 'Cette discussion est verrouillée.']);
        exit;
    }
    
    // Vérifier l'accès à la catégorie si elle est réservée aux admins
    if ($discussion['admin_only']) {
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
        echo json_encode(['success' => false, 'message' => 'Vous êtes suspendu et ne pouvez pas commenter.']);
        exit;
    }
    
    // Vérifier le parent_id si fourni
    $parent_author_id = null;
    if ($parent_id) {
        $stmt = $pdo->prepare("SELECT author_id FROM forum_comments WHERE id = ? AND discussion_id = ?");
        $stmt->execute([$parent_id, $discussion_id]);
        $parent_comment = $stmt->fetch();
        
        if (!$parent_comment) {
            echo json_encode(['success' => false, 'message' => 'Commentaire parent introuvable.']);
            exit;
        }
        $parent_author_id = $parent_comment['author_id'];
    }
    
    // Vérifier le rate limiting (max 10 commentaires par heure)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM forum_comments 
        WHERE author_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$user_id]);
    $recent_count = $stmt->fetch()['count'];
    
    if ($recent_count >= 10) {
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez poster que 10 commentaires par heure. Veuillez patienter.']);
        exit;
    }
    
    // Créer le commentaire
    $stmt = $pdo->prepare("
        INSERT INTO forum_comments (discussion_id, author_id, content, parent_id, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$discussion_id, $user_id, $content, $parent_id]);
    
    $comment_id = $pdo->lastInsertId();
    
    // Mettre à jour la date de dernière activité de la discussion
    $stmt = $pdo->prepare("UPDATE forum_discussions SET updated_at = NOW() WHERE id = ?");
    $stmt->execute([$discussion_id]);
    
    // Envoyer une notification à l'auteur de la discussion (si ce n'est pas lui-même)
    if ($discussion['author_id'] != $user_id) {
        $stmt = $pdo->prepare("SELECT username FROM membres WHERE id = ?");
        $stmt->execute([$user_id]);
        $commenter = $stmt->fetch();
        
        if ($commenter) {
            $notification_message = "💬 {$commenter['username']} a commenté votre discussion \"{$discussion['titre']}\" dans {$discussion['category_name']}.";
            
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, created_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$discussion['author_id'], $notification_message]);
        }
    }
    
    // Envoyer une notification à l'auteur du commentaire parent (si c'est une réponse et pas le même utilisateur)
    if ($parent_author_id && $parent_author_id != $user_id && $parent_author_id != $discussion['author_id']) {
        $stmt = $pdo->prepare("SELECT username FROM membres WHERE id = ?");
        $stmt->execute([$user_id]);
        $commenter = $stmt->fetch();
        
        if ($commenter) {
            $notification_message = "💬 {$commenter['username']} a répondu à votre commentaire dans la discussion \"{$discussion['titre']}\".";
            
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, created_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$parent_author_id, $notification_message]);
        }
    }
    
    // Récupérer les informations du commentaire créé pour la réponse
    $stmt = $pdo->prepare("
        SELECT c.*, m.username as author_name, m.titre as author_role
        FROM forum_comments c
        LEFT JOIN membres m ON c.author_id = m.id
        WHERE c.id = ?
    ");
    $stmt->execute([$comment_id]);
    $new_comment = $stmt->fetch();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Commentaire ajouté avec succès !',
        'comment' => [
            'id' => $new_comment['id'],
            'content' => $new_comment['content'],
            'author_name' => $new_comment['author_name'],
            'author_role' => $new_comment['author_role'],
            'created_at' => $new_comment['created_at'],
            'parent_id' => $new_comment['parent_id']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur lors de l'ajout de commentaire: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du commentaire.']);
}
?>