<?php
// Définir le code de réponse HTTP 500
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style-erreur.css">
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico">
    <title>500 - Erreur serveur | Lupistar</title>
    <meta name="description" content="Une erreur interne du serveur s'est produite.">
</head>
<body>
    <div class="background"></div>
    
    <div class="error-container">
        <img src="./gif/logogif.GIF" alt="Logo Lupistar" class="error-logo">
        
        <h1 class="error-code">500</h1>
        
        <h2 class="error-title">Erreur serveur</h2>
        
        <p class="error-message">
            Oops ! Une erreur interne du serveur s'est produite.<br>
            Nous travaillons à résoudre ce problème. Veuillez réessayer plus tard.
        </p>
        
        <a href="./index.php" class="btn-home">
            🏠 Retour à l'accueil
        </a>
    </div>

    <footer class="error-footer">
        <p>&copy; 2025 lupistar.fr — Tous droits réservés.</p>
        <nav>
            <a href="/mentions-legales.php">Mentions légales</a> | 
            <a href="/confidentialite.php">Politique de confidentialité</a>
        </nav>
    </footer>
</body>
</html>