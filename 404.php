<?php
// Définir le code de réponse HTTP 404
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style-erreur.css">
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico">
    <title>404 - Page non trouvée | Lupistar</title>
    <meta name="description" content="La page que vous recherchez n'existe pas ou a été déplacée.">
</head>
<body>
    <div class="background"></div>
    
    <div class="error-container">
        <img src="./gif/logogif.GIF" alt="Logo Lupistar" class="error-logo">
        
        <h1 class="error-code">404</h1>
        
        <h2 class="error-title">Page non trouvée</h2>
        
        <p class="error-message">
            Oops ! La page que vous recherchez n'existe pas ou a été déplacée.<br>
            Il se peut que l'URL soit incorrecte ou que le contenu ait été supprimé.
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