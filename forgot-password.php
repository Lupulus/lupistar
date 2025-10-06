<?php
session_start();

// Définir l'URL précédente
if (!isset($_SESSION['initial_previous_page'])) {
    if (isset($_SERVER['HTTP_REFERER']) && basename($_SERVER['HTTP_REFERER']) != 'forgot-password.php') {
        $_SESSION['initial_previous_page'] = $_SERVER['HTTP_REFERER'];
    } else {
        $_SESSION['initial_previous_page'] = 'index.php';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style-navigation.css">
    <link rel="stylesheet" href="./css/style-con-reg.css">
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico">
    <title>Récupération de mot de passe</title>
</head>
<body>
<div class="background"></div>
<div class="form-container">
    <h2>Récupération de mot de passe</h2>
    <p style="text-align: center; color: var(--text-medium-gray); margin-bottom: 25px; font-size: 14px; line-height: 1.5;">
        Entrez votre nom d'utilisateur ou votre adresse e-mail pour recevoir un lien de réinitialisation de votre mot de passe.
    </p>
    
    <form action="./scripts-php/forgot-password-script.php" method="post">
        <div>
            <label for="identifier">Nom d'utilisateur ou E-mail :</label>
            <input type="text" id="identifier" name="identifier" placeholder="Pseudo ou adresse e-mail" required>
        </div>
        <div>
            <button type="submit">Envoyer le lien de récupération</button>
        </div>
    </form>
    
    <div class="links">
        <a href="./login.php">Retour à la connexion</a>
        <a href="./register.php">Créer un compte</a>
        <a href="./index.php">Retour à l'accueil</a>
    </div>

    <!-- Zone pour afficher les messages d'erreur ou de succès -->
    <div id="message-container" style="display: none;"></div>
</div>

<footer>
  <p>&copy; 2025 lupistar.fr — Tous droits réservés.</p>
  <p>Les illustrations sont la propriété de leurs auteurs et éditeurs respectifs.</p>
  <nav>
    <a href="/mentions-legales.php">Mentions légales</a> | 
    <a href="/confidentialite.php">Politique de confidentialité</a>
  </nav>    
</footer>

<!-- Script JavaScript pour afficher les messages -->
<script>
    // Récupère le message d'erreur ou de succès de l'URL si présent
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const type = urlParams.get('type') || 'error';

    // Affiche le message dans la zone appropriée
    if (message) {
        const messageContainer = document.getElementById('message-container');
        messageContainer.style.display = 'block';
        messageContainer.className = type; // success, error, ou warning
        messageContainer.innerHTML = `<p>${message}</p>`;

        // Redirige vers la page de connexion après 5 secondes si l'envoi est réussi
        if (type === "success") {
            setTimeout(function() {
                window.location.href = './login.php';
            }, 5000);
        }
    }
</script>
<script src="./scripts-js/background.js" defer></script>
</body>
</html>