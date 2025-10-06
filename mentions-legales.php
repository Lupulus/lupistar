<?php
session_start();
// Définir la page actuelle pour marquer l'onglet actif
$current_page = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style-navigation.css">
    <link rel="stylesheet" href="./css/style-reglementations.css">
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico">
    <title>Mentions légales - lupistar.fr</title>
    <meta name="description" content="Mentions légales du site lupistar.fr - Informations légales et réglementaires">
</head>

<body>
    <div class="background"></div>
    
    <header>
        <nav class="navbar">
            <img src="./gif/logogif.GIF" alt="Logo lupistar" class="gif">
            <ul class="menu">
                <a class="btn <?php if ($current_page == 'index') echo 'active'; ?>" href="./index.php">Accueil</a>
                <a class="btn <?php if ($current_page == 'liste') echo 'active'; ?>" href="./liste.php">Liste</a>
                <a class="btn <?php if ($current_page == 'ma-liste') echo 'active'; ?>" href="./ma-liste.php">Ma Liste</a>
                <a class="btn <?php if ($current_page == 'discussion') echo 'active'; ?>" href="./discussion.php">Discussion</a>
            </ul>
            <div class="profil" id="profil">
                <?php 
                        $img_id = 'profilImg';
                        include './scripts-php/img-profil.php'; 
                        ?>
                <div class="menu-deroulant" id="deroulant">
                    <?php include './scripts-php/menu-profil.php'; ?>
                </div>
            </div>
        </nav>
    </header>

    <main class="main-container">
        <div class="page-title">
            <h1>Mentions légales</h1>
        </div>

        <a href="./index.php" class="back-button">Retour à l'accueil</a>

        <div class="content-container">
            <div class="section">
                <p>Conformément aux dispositions des articles 6-III et 19 de la loi n°2004-575 du 21 juin 2004 
                pour la Confiance dans l'économie numérique (LCEN), il est porté à la connaissance des 
                utilisateurs et visiteurs du site <strong>lupistar.fr</strong> les présentes mentions légales.</p>
            </div>

            <div class="section">
                <h2>1. Informations sur l'éditeur</h2>
                <p>
                    <strong>Nom du site :</strong> lupistar.fr<br>
                    <strong>Éditeur :</strong> Clément VOLLE<br>
                    <strong>Contact :</strong> <a href="mailto:clementvolle@gmail.com">clementvolle@gmail.com</a><br>
                    <strong>Directeur de publication :</strong> Clément VOLLE
                </p>
            </div>

            <div class="section">
                <h2>2. Hébergement</h2>
                <p>
                    Le site est auto-hébergé par son éditeur.<br>
                    Nom de domaine enregistré auprès de : <strong>OVH</strong>
                </p>
            </div>

            <div class="section">
                <h2>3. Propriété intellectuelle</h2>
                <p>
                    L'ensemble des éléments du site (textes, images, graphismes, logo, icônes, sons, logiciels, etc.) 
                    sont protégés par les lois françaises et internationales relatives à la propriété intellectuelle.
                </p>
                <p>
                    Les illustrations sont la propriété de leurs auteurs et éditeurs respectifs.
                    Toute reproduction, représentation, modification, publication ou adaptation, 
                    totale ou partielle, de tout ou partie des éléments du site est interdite, 
                    sauf autorisation écrite préalable.
                </p>
            </div>

            <div class="section">
                <h2>4. Responsabilité</h2>
                <p>
                    L'éditeur du site ne pourra être tenu responsable des dommages directs et indirects 
                    causés au matériel de l'utilisateur lors de l'accès au site.
                </p>
                <p>
                    Le site lupistar.fr contient des liens hypertextes vers d'autres sites, 
                    mais n'assume aucune responsabilité quant à leur contenu.
                </p>
            </div>

            <div class="section">
                <h2>5. Données personnelles</h2>
                <p>
                    Ce site est réalisé à titre de loisir et ne collecte pas de données personnelles nominatives 
                    sauf via les outils de mesure d'audience ou le cas échéant un formulaire de contact.
                </p>
                <p>
                    Conformément au Règlement Général sur la Protection des Données (RGPD), 
                    vous disposez d'un droit d'accès, de rectification et de suppression de vos données personnelles.
                </p>
                <p>
                    Pour exercer ce droit, vous pouvez contacter : <a href="mailto:clementvolle@gmail.com">clementvolle@gmail.com</a>.
                </p>
            </div>

            <div class="section">
                <h2>6. Cookies</h2>
                <p>
                    Le site peut utiliser des cookies techniques et/ou statistiques afin d'améliorer la navigation.
                    L'utilisateur peut configurer son navigateur pour refuser l'enregistrement des cookies.
                </p>
            </div>

            <div class="section">
                <h2>7. Droit applicable</h2>
                <p>
                    Les présentes mentions légales sont régies par le droit français. 
                    En cas de litige, les tribunaux français seront seuls compétents.
                </p>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 lupistar.fr — Tous droits réservés.</p>
        <p>Les illustrations sont la propriété de leurs auteurs et éditeurs respectifs.</p>
        <nav>
            <a href="/mentions-legales.php">Mentions légales</a> | 
            <a href="/confidentialite.php">Politique de confidentialité</a>
        </nav>
    </footer>

    <script src="./scripts-js/profile-image-persistence.js" defer></script>
    <script src="./scripts-js/background.js" defer></script>
    <script src="./scripts-js/notification-badge.js" defer></script>
    <?php include './scripts-php/scroll-to-top.php'; ?>
</body>
</html>
