<?php
session_start();

// Inclure le fichier de connexion à la base de données
require_once 'co-bdd.php';

$message = "";
$type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation des données
    if (empty($token) || empty($password) || empty($confirm_password)) {
        $message = "Tous les champs sont obligatoires.";
        $type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas.";
        $type = "error";
    } elseif (strlen($password) < 8) {
        $message = "Le mot de passe doit contenir au moins 8 caractères.";
        $type = "error";
    } else {
        try {
            // Vérifier si le token est valide et non expiré
            $sql = "SELECT pr.user_id, m.username FROM password_resets pr 
                    JOIN membres m ON pr.user_id = m.id 
                    WHERE pr.token = ? AND pr.expires_at > NOW()";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() > 0) {
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                $user_id = $user_data['user_id'];
                $username = $user_data['username'];
                
                // Hasher le nouveau mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Mettre à jour le mot de passe de l'utilisateur
                $update_stmt = $pdo->prepare("UPDATE membres SET password = ? WHERE id = ?");
                
                if ($update_stmt->execute([$hashed_password, $user_id])) {
                    // Supprimer le token utilisé
                    $delete_token_stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                    $delete_token_stmt->execute([$token]);
                    
                    // Optionnel : Supprimer tous les tokens de cet utilisateur
                    $delete_all_tokens_stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
                    $delete_all_tokens_stmt->execute([$user_id]);
                    
                    $message = "Votre mot de passe a été modifié avec succès. Vous allez être redirigé vers la page de connexion.";
                    $type = "success";
                    
                    // Log de sécurité (optionnel)
                    error_log("Password reset successful for user: " . $username . " (ID: " . $user_id . ")");
                } else {
                    $message = "Erreur lors de la mise à jour du mot de passe. Veuillez réessayer.";
                    $type = "error";
                    error_log("Password reset failed - Database error for user ID: " . $user_id);
                }
            } else {
                $message = "Ce lien de réinitialisation est invalide ou a expiré.";
                $type = "error";
                error_log("Password reset failed - Invalid or expired token: " . $token);
            }
        } catch (PDOException $e) {
            $message = "Erreur de base de données. Veuillez réessayer.";
            $type = "error";
            error_log("Password reset failed - PDO error: " . $e->getMessage());
        }
    }
} else {
    $message = "Accès non autorisé.";
    $type = "error";
}

// Rediriger avec le message
header("Location: ../reset-password.php?token=" . urlencode($_POST['token'] ?? '') . "&message=" . urlencode($message) . "&type=" . urlencode($type));
exit;
?>