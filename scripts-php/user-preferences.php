<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'co-bdd.php';

// Fonction pour récupérer les préférences d'un utilisateur
function getUserPreferences($user_id, $preference_type) {
    global $pdo;
    
    try {
        $sql = "SELECT preference_value FROM user_preferences WHERE user_id = ? AND preference_type = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $preference_type]);
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return json_decode($row['preference_value'], true);
        }
        
        // Retourner les préférences par défaut si aucune n'est trouvée
        if ($preference_type === 'categories_order') {
            return ["Animation", "Anime", "Série d'Animation", "Film", "Série"];
        }
        
        return null;
    } catch (PDOException $e) {
        error_log("Erreur PDO getUserPreferences: " . $e->getMessage());
        return null;
    }
}

// Fonction pour sauvegarder les préférences d'un utilisateur
function saveUserPreferences($user_id, $preference_type, $preference_value) {
    global $pdo;
    
    try {
        $preference_json = json_encode($preference_value);
        
        $sql = "INSERT INTO user_preferences (user_id, preference_type, preference_value) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([$user_id, $preference_type, $preference_json]);
    } catch (PDOException $e) {
        error_log("Erreur PDO saveUserPreferences: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer l'ordre des catégories d'un utilisateur (alias pour compatibilité)
function getUserCategoriesOrder($user_id) {
    return getUserPreferences($user_id, 'categories_order');
}

// Traitement des requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_categories_order':
            $categories_order = $_POST['categories_order'] ?? '';
            
            // Si c'est une chaîne JSON, la décoder
            if (is_string($categories_order)) {
                $categories_order = json_decode($categories_order, true);
            }
            
            if (empty($categories_order) || !is_array($categories_order)) {
                echo json_encode(['success' => false, 'error' => 'Ordre des catégories invalide']);
                exit;
            }
            
            if (saveUserPreferences($user_id, 'categories_order', $categories_order)) {
                echo json_encode(['success' => true, 'message' => 'Préférences sauvegardées avec succès']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la sauvegarde']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
            break;
    }
    exit;
}

// Traitement des requêtes GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['type'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $preference_type = $_GET['type'] ?? '';
    
    switch ($preference_type) {
        case 'categories_order':
            $categories_order = getUserPreferences($user_id, 'categories_order');
            echo json_encode(['success' => true, 'categories_order' => $categories_order]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Type de préférence non reconnu']);
            break;
    }
    exit;
}
?>