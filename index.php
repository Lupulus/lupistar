<?php
session_start();
// Définir la page actuelle pour marquer l'onglet actif
$current_page = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="./css/style-navigation.css">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico">
    <title>Lupistar</title>
</head>
<body>
    <div class="modal-overlay" id="film-modal">
        <div id="modal-content">
            <!-- La fênettre modal du film sera injecté ici via AJAX (film-detail-modal.php) -->
        </div>
    </div>
    <div class="background"></div>
    <header>
        <nav class="navbar">
            <img src="./gif/logogif.GIF" alt="" class="gif">
            <ul class="menu">
            <a class="btn <?php if ($current_page == 'index') echo 'active'; ?>" id="btn1" href="./index.php">Accueil</a>
            <a class="btn <?php if ($current_page == 'liste') echo 'active'; ?>" id="btn2" href="./liste.php">Liste</a>
            <a class="btn <?php if ($current_page == 'ma-liste') echo 'active'; ?>" id="btn3" href="./ma-liste.php">Ma Liste</a>
            <a class="btn <?php if ($current_page == 'forum') echo 'active'; ?>" id="btn4" href="./forum.php">Forum</a>
            </ul>
            <div class="profil" id="profil">
            <?php 
                        $img_id = 'profilImg';
                        include './scripts-php/img-profil.php'; 
                        ?>
            <div class="menu-deroulant" id="deroulant">
                <!-- Options de menu dans la page menuprofil.php -->
                <?php include './scripts-php/menu-profil.php'; ?>
            </div>
        </div>
        </nav>
    </header>

    <section class="recently-added">
        <h2>Ajouts récent du moment</h2>
        <div class="categorie-container">
            <?php include './scripts-php/display-recent-film.php'; ?>
        </div>
    </section>

<footer>
  <p>&copy; 2025 lupistar.fr — Tous droits réservés.</p>
  <p>Les illustrations sont la propriété de leurs auteurs et éditeurs respectifs.</p>
  <nav>
    <a href="/mentions-legales.php">Mentions légales</a> | 
    <a href="/confidentialite.php">Politique de confidentialité</a>
  </nav>
</footer>
    <!-- Ajout du script pour le flou dynamique -->
    <script src="./scripts-js/profile-image-persistence.js" defer></script>
    <script src="./scripts-js/background.js" defer></script>
    <script src="./scripts-js/carousel-recentfilm.js" defer></script>
    <script src="./scripts-js/film-modal.js" defer></script>
    <script src="./scripts-js/notification-badge.js" defer></script>
    <?php include './scripts-php/scroll-to-top.php'; ?>

</body>
</html>