<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
include './co-bdd.php';

// Configuration email (à adapter selon votre serveur)
$smtp_host = 'smtp.gmail.com'; // Remplacer par votre serveur SMTP
$smtp_port = 587;
$smtp_username = 'clementvolle@gmail.com'; // Remplacer par votre email
$smtp_password = 'pntjfivzkcbhouxk'; // Remplacer par votre mot de passe d'application
$from_email = 'clementvolle@gmail.com'; // Email d'expéditeur
$from_name = 'Wolf Film - Récupération de mot de passe';

// Fonction pour envoyer un email
function sendPasswordResetEmail($to_email, $reset_token) {
    global $smtp_host, $smtp_port, $smtp_username, $smtp_password, $from_email, $from_name;
    
    $reset_link = "https://lupistar.fr/reset-password.php?token=" . $reset_token;
    
    $subject = "Réinitialisation de votre mot de passe - Wolf Film";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎬 Wolf Film</h1>
                <h2>Réinitialisation de mot de passe</h2>
            </div>
            <div class='content'>
                <p>Bonjour,</p>
                <p>Vous avez demandé la réinitialisation de votre mot de passe sur Wolf Film.</p>
                <p>Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe :</p>
                <p style='text-align: center;'>
                    <a href='" . $reset_link . "' class='button'>Réinitialiser mon mot de passe</a>
                </p>
                <p>Ou copiez ce lien dans votre navigateur :</p>
                <p style='word-break: break-all; background: #eee; padding: 10px; border-radius: 5px;'>" . $reset_link . "</p>
                <p><strong>Ce lien expirera dans 1 heure.</strong></p>
                <p>Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email en toute sécurité.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2025 Lupulus Corporation - Wolf Film</p>
                <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    try {
        $mail = new PHPMailer(true);
        
        // Configuration SMTP
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtp_port;
        
        // Configuration de l'email
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to_email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        // Envoi de l'email
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Erreur d'envoi d'email: " . $e->getMessage());
        return false;
    }
}

// Fonction pour générer un token sécurisé
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = trim($_POST['identifier']);
    
    if (empty($identifier)) {
        $message = "Veuillez entrer votre nom d'utilisateur ou votre adresse e-mail.";
        $type = "error";
    } else {
        try {
            // Vérifier si l'utilisateur existe et a un email
            $sql = "SELECT id, username, email FROM membres WHERE (username = ? OR email = ?) AND email IS NOT NULL AND email != ''";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$identifier, $identifier]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $user_id = $user['id'];
                $username = $user['username'];
                $email = $user['email'];
                
                // Générer un token unique
                $token = generateResetToken();
                
                // Définir le fuseau horaire pour éviter les décalages
                date_default_timezone_set('Europe/Paris');
                
                $expires_at = date('Y-m-d H:i:s', strtotime('+2 hours'));
                $created_at = date('Y-m-d H:i:s');
                
                // Supprimer les anciens tokens de cet utilisateur (optionnel)
                $delete_old_tokens = "DELETE FROM password_resets WHERE user_id = ?";
                $stmt_delete = $pdo->prepare($delete_old_tokens);
                $stmt_delete->execute([$user_id]);
                
                // Insérer le nouveau token dans la table password_resets
                $insert_token = "INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (?, ?, ?, ?)";
                $stmt_insert = $pdo->prepare($insert_token);
                
                if ($stmt_insert->execute([$user_id, $token, $expires_at, $created_at])) {
                    // Envoyer l'email
                    if (sendPasswordResetEmail($user['email'], $token)) {
                        $message = "Un lien de réinitialisation a été envoyé à votre adresse e-mail.";
                        $type = "success";
                    } else {
                        $message = "Erreur lors de l'envoi de l'email. Veuillez réessayer plus tard.";
                        $type = "error";
                    }
                } else {
                    $message = "Erreur lors de la génération du lien de réinitialisation.";
                    $type = "error";
                }
            } else {
                // Pour des raisons de sécurité, on ne révèle pas si l'utilisateur existe ou non
                $message = "Si ce compte existe, un lien de réinitialisation a été envoyé à l'adresse e-mail associée.";
                $type = "success";
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la réinitialisation du mot de passe: " . $e->getMessage());
            $message = "Une erreur est survenue. Veuillez réessayer plus tard.";
            $type = "error";
        }
    }
}

// Rediriger avec le message
header("Location: ../forgot-password.php?message=" . urlencode($message) . "&type=" . urlencode($type));
exit;
?>