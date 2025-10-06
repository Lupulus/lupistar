<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'co-bdd.php';

// Vérification des permissions d'administrateur
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$isSuperAdmin = isset($_SESSION['titre']) && $_SESSION['titre'] === 'Super-Admin';
$isAdmin = isset($_SESSION['titre']) && $_SESSION['titre'] === 'Admin';

if (!$isLoggedIn || (!$isSuperAdmin && !$isAdmin)) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Accès non autorisé."]);
    exit;
}

// Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Méthode non autorisée."]);
    exit;
}

// Récupération et validation des données
$recipient_type = $_POST['recipient_type'] ?? '';
$notification_title = trim($_POST['notification_title'] ?? '');
$notification_message = trim($_POST['notification_message'] ?? '');

// Validation des champs obligatoires
if (empty($recipient_type) || empty($notification_title) || empty($notification_message)) {
    echo json_encode(["success" => false, "message" => "Tous les champs obligatoires doivent être remplis."]);
    exit;
}

// Validation de la longueur des champs
if (strlen($notification_title) > 100) {
    echo json_encode(["success" => false, "message" => "Le titre ne peut pas dépasser 100 caractères."]);
    exit;
}

if (strlen($notification_message) > 500) {
    echo json_encode(["success" => false, "message" => "Le message ne peut pas dépasser 500 caractères."]);
    exit;
}

try {
    $user_ids = [];
    
    // Déterminer les utilisateurs destinataires selon le type
    switch ($recipient_type) {
        case 'all':
            // Tous les utilisateurs
            $stmt = $pdo->prepare("SELECT id FROM membres");
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $user_ids[] = $row['id'];
            }
            break;
            
        case 'title':
            // Utilisateurs par titre
            $user_title = $_POST['user_title'] ?? '';
            if (empty($user_title)) {
                echo json_encode(["success" => false, "message" => "Veuillez sélectionner un titre d'utilisateur."]);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT id FROM membres WHERE titre = ?");
            $stmt->execute([$user_title]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $user_ids[] = $row['id'];
            }
            break;
            
        case 'specific':
            // Utilisateur spécifique
            $search_type = $_POST['search_type'] ?? '';
            $user_search = trim($_POST['user_search'] ?? '');
            
            if (empty($search_type) || empty($user_search)) {
                echo json_encode(["success" => false, "message" => "Veuillez spécifier le type de recherche et l'utilisateur."]);
                exit;
            }
            
            if ($search_type === 'username') {
                $stmt = $pdo->prepare("SELECT id FROM membres WHERE username = ?");
            } elseif ($search_type === 'email') {
                $stmt = $pdo->prepare("SELECT id FROM membres WHERE email = ?");
            } else {
                echo json_encode(["success" => false, "message" => "Type de recherche invalide."]);
                exit;
            }
            
            $stmt->execute([$user_search]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                $search_label = ($search_type === 'username') ? 'nom d\'utilisateur' : 'adresse e-mail';
                echo json_encode(["success" => false, "message" => "Aucun utilisateur trouvé avec ce $search_label."]);
                exit;
            }
            
            $user_ids[] = $row['id'];
            break;
            
        default:
            echo json_encode(["success" => false, "message" => "Type de destinataire invalide."]);
            exit;
    }
    
    // Vérifier qu'il y a des destinataires
    if (empty($user_ids)) {
        echo json_encode(["success" => false, "message" => "Aucun destinataire trouvé pour ce type de sélection."]);
        exit;
    }
    
    // Insérer les notifications pour chaque utilisateur
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, titre, date_creation) VALUES (?, 'admin_notification', ?, ?, NOW())");
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($user_ids as $user_id) {
        try {
            $stmt->execute([$user_id, $notification_message, $notification_title]);
            $success_count++;
        } catch (PDOException $e) {
            $error_count++;
            error_log("Erreur lors de l'insertion de notification pour l'utilisateur ID $user_id: " . $e->getMessage());
        }
    }
    
    // Préparer le message de réponse
    $total_recipients = count($user_ids);
    
    if ($success_count === $total_recipients) {
        $message = "Notification envoyée avec succès à $success_count utilisateur(s).";
        echo json_encode(["success" => true, "message" => $message]);
    } elseif ($success_count > 0) {
        $message = "Notification envoyée à $success_count utilisateur(s) sur $total_recipients. $error_count échec(s).";
        echo json_encode(["success" => true, "message" => $message, "partial" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Échec de l'envoi de toutes les notifications."]);
    }
    
} catch (PDOException $e) {
    error_log("Erreur lors de l'envoi de notifications: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Une erreur est survenue lors de l'envoi des notifications."]);
}
?>