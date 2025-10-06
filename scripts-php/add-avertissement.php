<?php
/**
 * Script pour ajouter des avertissements aux utilisateurs
 * Utilisé lors de la modération de contenu inapproprié
 */

function addWarning($pdo, $user_id, $reason, $moderator_id = null) {
    try {
        // Vérifier si l'utilisateur existe
        $stmt = $pdo->prepare("SELECT id, username, avertissements FROM membres WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            error_log("Tentative d'ajout d'avertissement à un utilisateur inexistant: $user_id");
            return false;
        }
        
        // Incrémenter le nombre d'avertissements
        $new_warning_count = $user['avertissements'] + 1;
        
        $stmt = $pdo->prepare("UPDATE membres SET avertissements = ? WHERE id = ?");
        $stmt->execute([$new_warning_count, $user_id]);
        
        // Enregistrer l'avertissement dans l'historique (si une table existe)
        // Note: Cette table pourrait être créée plus tard pour un historique détaillé
        /*
        $stmt = $pdo->prepare("
            INSERT INTO avertissements_historique (user_id, moderator_id, reason, created_at, warning_count) 
            VALUES (?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$user_id, $moderator_id, $reason, $new_warning_count]);
        */
        
        // Envoyer une notification à l'utilisateur
        include_once './send-notification.php';
        $notification_message = "⚠️ Vous avez reçu un avertissement. Raison: " . $reason . " (Total: $new_warning_count avertissement" . ($new_warning_count > 1 ? 's' : '') . ")";
        sendNotification($pdo, $user_id, $notification_message);
        
        // Vérifier si l'utilisateur doit être suspendu (par exemple, après 3 avertissements)
        if ($new_warning_count >= 3) {
            // Suspendre temporairement l'utilisateur
            $suspension_end = date('Y-m-d H:i:s', strtotime('+7 days')); // Suspension de 7 jours
            $stmt = $pdo->prepare("UPDATE membres SET suspended_until = ? WHERE id = ?");
            $stmt->execute([$suspension_end, $user_id]);
            
            // Notifier la suspension
            $suspension_message = "🚫 Votre compte a été suspendu jusqu'au " . date('d/m/Y à H:i', strtotime($suspension_end)) . " en raison de multiples avertissements.";
            sendNotification($pdo, $user_id, $suspension_message);
            
            error_log("Utilisateur suspendu automatiquement: " . $user['username'] . " (ID: $user_id) - $new_warning_count avertissements");
        }
        
        // Log de l'action
        error_log("Avertissement ajouté à l'utilisateur " . $user['username'] . " (ID: $user_id). Raison: $reason. Total: $new_warning_count");
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Erreur lors de l'ajout d'avertissement: " . $e->getMessage());
        return false;
    }
}

function removeWarning($pdo, $user_id, $reason = "Avertissement retiré par un administrateur") {
    try {
        // Vérifier si l'utilisateur existe et a des avertissements
        $stmt = $pdo->prepare("SELECT id, username, avertissements FROM membres WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            error_log("Tentative de retrait d'avertissement à un utilisateur inexistant: $user_id");
            return false;
        }
        
        if ($user['avertissements'] <= 0) {
            error_log("Tentative de retrait d'avertissement à un utilisateur sans avertissement: " . $user['username']);
            return false;
        }
        
        // Décrémenter le nombre d'avertissements
        $new_warning_count = max(0, $user['avertissements'] - 1);
        
        $stmt = $pdo->prepare("UPDATE membres SET avertissements = ? WHERE id = ?");
        $stmt->execute([$new_warning_count, $user_id]);
        
        // Si l'utilisateur était suspendu et n'a plus d'avertissements critiques, lever la suspension
        if ($new_warning_count < 3) {
            $stmt = $pdo->prepare("UPDATE membres SET suspended_until = NULL WHERE id = ? AND suspended_until IS NOT NULL");
            $stmt->execute([$user_id]);
        }
        
        // Envoyer une notification à l'utilisateur
        include_once './send-notification.php';
        $notification_message = "✅ Un de vos avertissements a été retiré. Raison: " . $reason . " (Total: $new_warning_count avertissement" . ($new_warning_count > 1 ? 's' : '') . ")";
        sendNotification($pdo, $user_id, $notification_message);
        
        // Log de l'action
        error_log("Avertissement retiré à l'utilisateur " . $user['username'] . " (ID: $user_id). Raison: $reason. Total: $new_warning_count");
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Erreur lors du retrait d'avertissement: " . $e->getMessage());
        return false;
    }
}

function getUserWarnings($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT avertissements, suspended_until FROM membres WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        return $result ? [
            'count' => (int)$result['avertissements'],
            'suspended_until' => $result['suspended_until']
        ] : null;
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des avertissements: " . $e->getMessage());
        return null;
    }
}

function isUserSuspended($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT suspended_until FROM membres WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        if ($result && $result['suspended_until']) {
            $suspension_end = strtotime($result['suspended_until']);
            $now = time();
            
            if ($suspension_end > $now) {
                return [
                    'suspended' => true,
                    'until' => $result['suspended_until'],
                    'remaining_time' => $suspension_end - $now
                ];
            } else {
                // La suspension est expirée, la nettoyer
                $stmt = $pdo->prepare("UPDATE membres SET suspended_until = NULL WHERE id = ?");
                $stmt->execute([$user_id]);
                return ['suspended' => false];
            }
        }
        
        return ['suspended' => false];
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification de suspension: " . $e->getMessage());
        return ['suspended' => false];
    }
}

// Si le script est appelé directement (pour les tests ou l'administration)
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    session_start();
    require_once './co-bdd.php';
    
    // Vérifier si l'utilisateur est administrateur
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Non autorisé']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("SELECT role FROM membres WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Accès administrateur requis']);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur de base de données']);
        exit;
    }
    
    // Traitement des requêtes AJAX
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Données invalides']);
            exit;
        }
        
        $action = $input['action'] ?? '';
        $target_user_id = $input['user_id'] ?? 0;
        $reason = $input['reason'] ?? '';
        
        switch ($action) {
            case 'add_warning':
                if ($target_user_id && $reason) {
                    $success = addWarning($pdo, $target_user_id, $reason, $user_id);
                    echo json_encode(['success' => $success]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Paramètres manquants']);
                }
                break;
                
            case 'remove_warning':
                if ($target_user_id) {
                    $success = removeWarning($pdo, $target_user_id, $reason ?: "Retiré par un administrateur");
                    echo json_encode(['success' => $success]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID utilisateur manquant']);
                }
                break;
                
            case 'get_warnings':
                if ($target_user_id) {
                    $warnings = getUserWarnings($pdo, $target_user_id);
                    echo json_encode(['warnings' => $warnings]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID utilisateur manquant']);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Action non reconnue']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
    }
}
?>