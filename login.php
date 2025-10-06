<?php
session_start();

// Définir l'URL précédente, sauf si elle est la page d'inscription ou la page de connexion
if (!isset($_SESSION['initial_previous_page'])) {
    if (isset($_SERVER['HTTP_REFERER']) && basename($_SERVER['HTTP_REFERER']) != 'register.php' && basename($_SERVER['HTTP_REFERER']) != 'login.php') {
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
    <title>Connexion</title>
</head>
<body>
<div class="background"></div>
<div class="form-container">
    <h2>Connexion</h2>
    <form action="./scripts-php/login-script.php" method="post">
        <div>
            <label for="username">Nom d'utilisateur :</label>
            <input type="text" id="username" name="username" placeholder="Nom d'utilisateur (Pseudo)" required>
        </div>
        <div>
            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" placeholder="Mot de passe" required>
        </div>
        <!-- Champ caché pour l'URL de la page précédente -->
        <input type="hidden" name="previous_page" value="<?php echo $_SESSION['initial_previous_page']; ?>">
        <div>
            <button type="submit">Se connecter</button>
        </div>
    </form>
    <div class="links">
        <a href="./register.php">Créer un compte</a>
        <a href="./forgot-password.php">Mot de passe oublié ?</a>
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

<!-- Script JavaScript pour afficher les messages et rediriger -->
<script>
    // Récupère le message d'erreur ou de succès de l'URL si présent
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const success = urlParams.get('success');

    // Affiche le message dans la zone appropriée
    if (message) {
        const messageContainer = document.getElementById('message-container');
        messageContainer.style.display = 'block';
        
        // Détermine le type de message basé sur le paramètre success
        const type = success === 'true' ? 'success' : 'error';
        messageContainer.className = type; // success ou error
        messageContainer.innerHTML = `<p>${message}</p>`;

        // Redirige vers la page précédente ou index.php après 5 secondes seulement en cas de succès
        if (success === 'true') {
            const previousPage = '<?php echo $_SESSION['initial_previous_page']; ?>';
            setTimeout(function() {
                // Vérifie que l'URL est bien de votre site
                const allowedHost = new URL('http://lupistar.fr'); // Remplacez par votre domaine
                const previousURL = new URL(previousPage);

                if (previousURL.host === allowedHost.host) {
                    window.location.href = previousPage;
                } else {
                    window.location.href = './index.php';
                }
            }, 2000);
        }
    }
</script>
<script src="./scripts-js/background.js" defer></script>
</body>
</html>