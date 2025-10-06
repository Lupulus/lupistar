<?php
session_start();
include './scripts-php/co-bdd.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$valid_token = false;
$user_id = null;

if (!empty($token)) {
    try {
        // Vérifier si le token est valide et non expiré
        $sql = "SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token]);
        
        if ($stmt->rowCount() > 0) {
            $valid_token = true;
            $user_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'];
        }
    } catch (PDOException $e) {
        error_log("Erreur PDO reset-password: " . $e->getMessage());
        $valid_token = false;
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
    <title>Nouveau mot de passe</title>
</head>
<body>
<div class="background"></div>
<div class="form-container">
    <?php if ($valid_token): ?>
        <h2>Nouveau mot de passe</h2>
        <p style="text-align: center; color: var(--text-medium-gray); margin-bottom: 25px; font-size: 14px; line-height: 1.5;">
            Choisissez un nouveau mot de passe sécurisé pour votre compte.
        </p>
        
        <form action="./scripts-php/reset-password-script.php" method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div>
                <label for="password">Nouveau mot de passe :</label>
                <input type="password" id="password" name="password" placeholder="Minimum 8 caractères" required minlength="8">
            </div>
            <div>
                <label for="confirm_password">Confirmer le mot de passe :</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Retapez votre mot de passe" required minlength="8">
            </div>
            <div>
                <button type="submit">Changer le mot de passe</button>
            </div>
        </form>
    <?php else: ?>
        <h2>Lien invalide</h2>
        <p style="text-align: center; color: var(--error-red); margin-bottom: 25px; font-size: 14px; line-height: 1.5;">
            Ce lien de réinitialisation est invalide ou a expiré. Les liens sont valides pendant 2 heures seulement.
        </p>
    <?php endif; ?>
    
    <div class="links">
        <a href="./login.php">Retour à la connexion</a>
        <a href="./forgot-password.php">Demander un nouveau lien</a>
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

<!-- Script JavaScript pour afficher les messages et validation -->
<script>
    // Récupère le message d'erreur ou de succès de l'URL si présent
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const type = urlParams.get('type') || 'error';

    // Affiche le message dans la zone appropriée
    if (message) {
        const messageContainer = document.getElementById('message-container');
        messageContainer.style.display = 'block';
        messageContainer.className = type;
        messageContainer.innerHTML = `<p>${message}</p>`;

        // Redirige vers la page de connexion après 3 secondes si le changement est réussi
        if (type === "success") {
            setTimeout(function() {
                window.location.href = './login.php';
            }, 3000);
        }
    }

    // Validation des mots de passe
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                const messageContainer = document.getElementById('message-container');
                messageContainer.style.display = 'block';
                messageContainer.className = 'error';
                messageContainer.innerHTML = '<p>Les mots de passe ne correspondent pas.</p>';
            }
        });
    }
</script>
<script src="./scripts-js/background.js" defer></script>
</body>
</html>