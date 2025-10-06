<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style-navigation.css">
    <link rel="stylesheet" href="./css/style-con-reg.css">
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico">
    <title>Enregistrement</title>
</head>
<body>
<div class="background"></div>
<div class="form-container">
    <h2>Enregistrement</h2>
    <form action="./scripts-php/register-script.php" method="post">
        <div>
            <label for="username">Nom d'utilisateur :</label>
            <input type="text" id="username" name="username" placeholder="Nom d'utilisateur (Pseudo)" required>
        </div>
        <div>
            <label for="email">Adresse e-mail (facultatif) :</label>
            <input type="email" id="email" name="email" placeholder="votre@email.com">
            <div class="email-info">
                Il est recommandé de fournir un e-mail pour pouvoir récupérer votre mot de passe en cas d'oubli.
            </div>
        </div>
        <div>
            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" placeholder="Mot de passe" required>
        </div>
        <div class="checkbox-container">
            <input type="checkbox" id="politique_acceptee" name="politique_acceptee" required>
            <label for="politique_acceptee">
                J'accepte la <a href="./confidentialite.php" target="_blank"> politique de confidentialité</a> *
            </label>
        </div>
        <div>
            <button type="submit" id="submit-btn" disabled>S'enregistrer</button>
        </div>
    </form>
    <div class="links">
        <a href="./login.php">Se connecter</a>
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
    // Fonction pour gérer l'état du bouton d'inscription
    function toggleSubmitButton() {
        const checkbox = document.getElementById('politique_acceptee');
        const submitBtn = document.getElementById('submit-btn');
        
        if (checkbox.checked) {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        } else {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor = 'not-allowed';
        }
    }

    // Attendre que le DOM soit chargé
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('politique_acceptee');
        const submitBtn = document.getElementById('submit-btn');
        
        // État initial du bouton
        toggleSubmitButton();
        
        // Écouter les changements sur la checkbox
        checkbox.addEventListener('change', toggleSubmitButton);
    });

    // Récupère le message d'erreur ou de succès de l'URL si présent
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');

    // Affiche le message dans la zone appropriée
    if (message) {
        const messageContainer = document.getElementById('message-container');
        messageContainer.style.display = 'block';
        
        // Détermine le type de message basé sur le contenu du message
        const type = message === "Enregistrement réussi." ? 'success' : 'error';
        messageContainer.className = type; // success ou error
        messageContainer.innerHTML = `<p>${message}</p>`;

        // Redirige vers la page de connexion après 5 secondes si l'enregistrement est réussi
        if (message === "Enregistrement réussi.") {
            setTimeout(function() {
                window.location.href = './login.php';
            }, 1500);
        }
    }
</script>
<script src="./scripts-js/background.js" defer></script>
</body>
</html>
