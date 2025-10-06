<?php
session_start();

include './co-bdd.php';

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Récupérer les valeurs du formulaire
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Rechercher l'utilisateur dans la base de données avec requête préparée
        $sql = "SELECT * FROM membres WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);

        if ($stmt->rowCount() == 1) {
            // Utilisateur trouvé, vérifier le mot de passe
            $row = $stmt->fetch();
            if (password_verify($password, $row['password'])) {
                // Mot de passe correct, connecter l'utilisateur
                $_SESSION['loggedin'] = true; // Marquer l'utilisateur comme connecté
                $_SESSION['username'] = $row['username']; // Stocker le nom d'utilisateur dans la session
                $_SESSION['user_id'] = $row['id']; // Stocker l'identifiant de l'utilisateur dans la session
                $_SESSION['titre'] = $row['titre']; // Stocker le titre de l'utilisateur dans la session
                $_SESSION['photo_profil'] = $row['photo_profil'] ?? './img/profil.png'; // Stocker la photo de profil dans la session
                // Vous pouvez stocker d'autres informations d'utilisateur importantes ici
                $message = "Connexion réussie. Bienvenue, " . $username . "!";
                $success = "true";
            } else {
                // Mot de passe incorrect
                $message = "Mot de passe ou utilisateur incorrect.";
                $success = "false";
            }
        } else {
            // Utilisateur non trouvé
            $message = "Mot de passe ou utilisateur incorrect.";
            $success = "false";
        }
    } catch (PDOException $e) {
        $message = "Erreur de connexion à la base de données.";
        $success = "false";
        error_log("Erreur PDO dans login-script.php : " . $e->getMessage());
    }
}

// Rediriger avec le message d'erreur ou de succès
$_SESSION['previous_page'] = ''; // Réinitialiser la variable de session
header("Location: ../login.php?message=" . urlencode($message) . "&success=" . $success);
exit;
?>