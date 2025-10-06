<?php
// Définir le code de réponse HTTP 403
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style-erreur.css">
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico">
    <title>403 - Accès interdit | Lupistar</title>
    <meta name="description" content="Vous n'avez pas l'autorisation d'accéder à cette ressource.">
</head>
<body>
    <div class="background"></div>
    
    <div class="error-container">
        <img src="./gif/logogif.GIF" alt="Logo Lupistar" class="error-logo">
        
        <h1 class="error-code">403</h1>
        
        <h2 class="error-title">Accès interdit</h2>
        
        <p class="error-message">
            Désolé, vous n'avez pas l'autorisation d'accéder à cette ressource.<br>
            Veuillez vous connecter ou vérifier vos permissions d'accès.
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