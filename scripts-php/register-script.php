<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include './co-bdd.php';

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Récupérer les valeurs du formulaire
        $username = $_POST['username'];
        $password = $_POST['password'];
        $email = isset($_POST['email']) ? $_POST['email'] : null; // Email facultatif
        $politique_acceptee = isset($_POST['politique_acceptee']) ? 1 : 0; // Case cochée = 1, sinon 0

        // Vérifier que la politique de confidentialité a été acceptée
        if ($politique_acceptee == 0) {
            $message = "Vous devez accepter la politique de confidentialité pour vous inscrire.";
        } else {
            // Vérifier si le nom d'utilisateur existe déjà
            $sql_check_username = "SELECT * FROM membres WHERE username = ?";
            $stmt_check_username = $pdo->prepare($sql_check_username);
            $stmt_check_username->execute([$username]);
            
            if ($stmt_check_username->rowCount() > 0) {
                $message = "Ce nom d'utilisateur est déjà utilisé. Veuillez en choisir un autre.";
            } else {
                // Vérifier si l'email existe déjà (seulement si un email est fourni)
                if (!empty($email)) {
                    $sql_check_email = "SELECT * FROM membres WHERE email = ?";
                    $stmt_check_email = $pdo->prepare($sql_check_email);
                    $stmt_check_email->execute([$email]);
                    
                    if ($stmt_check_email->rowCount() > 0) {
                        $message = "Cette adresse e-mail est déjà utilisée. Veuillez en choisir une autre.";
                    }
                }
                
                // Si pas d'erreur d'email, continuer avec la vérification du mot de passe
                if (!isset($message)) {
                    // Vérifier la longueur du mot de passe
                    if (strlen($password) < 8) {
                        $message = "Le mot de passe doit contenir au moins 8 caractères.";
                    } else {
                        // Hasher le mot de passe avant de le stocker
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $titre = 'Membre';

                        // Préparer et exécuter la requête SQL pour insérer les données dans la table
                        if (!empty($email)) {
                            $sql = "INSERT INTO membres (username, password, email, titre, politique_acceptee) VALUES (?, ?, ?, ?, ?)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$username, $hashed_password, $email, $titre, $politique_acceptee]);
                        } else {
                            $sql = "INSERT INTO membres (username, password, titre, politique_acceptee) VALUES (?, ?, ?, ?)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$username, $hashed_password, $titre, $politique_acceptee]);
                        }

                        // Récupérer l'ID du nouvel utilisateur créé
                        $user_id = $pdo->lastInsertId();
                        
                        // Créer une notification de bienvenue pour le nouvel utilisateur
                        $welcome_message = "🎉 Bienvenue sur Lupistar ! Nous sommes ravis de vous compter parmi nous. Explorez notre collection de films et profitez de votre expérience cinématographique !";
                        $notification_sql = "INSERT INTO notifications (user_id, type, message, titre, date_creation) VALUES (?, 'welcome', ?, 'Bienvenue !', NOW())";
                        $stmt_notification = $pdo->prepare($notification_sql);
                        
                        if ($stmt_notification->execute([$user_id, $welcome_message])) {
                            error_log("Notification de bienvenue créée pour l'utilisateur ID: $user_id");
                        } else {
                            error_log("Erreur lors de la création de la notification de bienvenue");
                        }
                        
                        $message = "Enregistrement réussi.";
                    }
                }
            }
        }
    } catch (PDOException $e) {
        $message = "Erreur lors de l'enregistrement : " . $e->getMessage();
        error_log("Erreur PDO dans register-script.php : " . $e->getMessage());
    }
}

// Rediriger avec le message d'erreur ou de succès
header("Location: ../register.php?message=" . urlencode($message));
exit;
?>
