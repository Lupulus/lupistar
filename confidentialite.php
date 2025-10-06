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
    <title>Politique de confidentialité - lupistar.fr</title>
    <meta name="description" content="Politique de confidentialité du site lupistar.fr - Protection des données personnelles">
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
            <h1>Politique de confidentialité</h1>
        </div>

        <a href="./index.php" class="back-button">Retour à l'accueil</a>

        <div class="content-container">
            <div class="section">
                <p>La présente politique de confidentialité décrit la façon dont <strong>lupistar.fr</strong> 
                collecte, utilise et protège les informations que vous nous fournissez lorsque vous utilisez ce site web.</p>
                <p><strong>Dernière mise à jour :</strong> Octobre 2025</p>
            </div>

            <div class="section">
                <h2>1. Responsable du traitement</h2>
                <p>
                    <strong>Responsable :</strong> Clément VOLLE<br>
                    <strong>Contact :</strong> <a href="mailto:clementvolle@gmail.com">clementvolle@gmail.com</a><br>
                    <strong>Site web :</strong> lupistar.fr
                </p>
            </div>

            <div class="section">
                <h2>2. Données collectées</h2>
                <h3>2.1 Données d'inscription</h3>
                <p>Lors de votre inscription sur le site, nous collectons :</p>
                <ul>
                    <li>Nom d'utilisateur</li>
                    <li>Mot de passe (crypté)</li>
                    <li>Adresse email (facultative)</li>
                    <li>Date d'inscription</li>
                    <li>Acceptation de la politique de confidentialité</li>
                </ul>

                <h3>2.2 Données de navigation</h3>
                <p>Nous pouvons collecter automatiquement :</p>
                <ul>
                    <li>Adresse IP</li>
                    <li>Type de navigateur</li>
                    <li>Pages visitées</li>
                    <li>Durée de visite</li>
                    <li>Données de cookies techniques</li>
                </ul>
            </div>

            <div class="section">
                <h2>3. Finalités du traitement</h2>
                <p>Vos données personnelles sont utilisées pour :</p>
                <ul>
                    <li>Créer et gérer votre compte utilisateur</li>
                    <li>Personnaliser votre expérience sur le site</li>
                    <li>Sauvegarder vos préférences et listes de films</li>
                    <li>Assurer la sécurité du site</li>
                    <li>Améliorer nos services</li>
                    <li>Répondre à vos demandes de support</li>
                </ul>
            </div>

            <div class="section">
                <h2>4. Base légale</h2>
                <p>Le traitement de vos données personnelles est fondé sur :</p>
                <ul>
                    <li><strong>Votre consentement</strong> pour l'inscription et l'utilisation du service</li>
                    <li><strong>L'intérêt légitime</strong> pour l'amélioration du site et la sécurité</li>
                    <li><strong>L'exécution du contrat</strong> pour la fourniture du service</li>
                </ul>
            </div>

            <div class="section">
                <h2>5. Partage des données</h2>
                <p>Nous ne vendons, n'échangeons ni ne transférons vos données personnelles à des tiers, sauf :</p>
                <ul>
                    <li>Avec votre consentement explicite</li>
                    <li>Pour répondre à une obligation légale</li>
                    <li>Pour protéger nos droits ou la sécurité du site</li>
                </ul>
                <p>Ce site étant à des fins de loisir et auto-hébergé, aucune donnée n'est partagée avec des partenaires commerciaux.</p>
            </div>

            <div class="section">
                <h2>6. Durée de conservation</h2>
                <p>Vos données sont conservées :</p>
                <ul>
                    <li><strong>Données de compte :</strong> Tant que votre compte est actif</li>
                    <li><strong>Données de navigation :</strong> Maximum 13 mois</li>
                    <li><strong>Cookies :</strong> Selon leur durée de vie spécifique</li>
                </ul>
                <p>Vous pouvez demander la suppression de votre compte et de toutes vos données à tout moment.</p>
            </div>

            <div class="section">
                <h2>7. Vos droits</h2>
                <p>Conformément au RGPD, vous disposez des droits suivants :</p>
                <ul>
                    <li><strong>Droit d'accès :</strong> Connaître les données que nous détenons sur vous</li>
                    <li><strong>Droit de rectification :</strong> Corriger vos données inexactes</li>
                    <li><strong>Droit à l'effacement :</strong> Supprimer vos données</li>
                    <li><strong>Droit à la portabilité :</strong> Récupérer vos données</li>
                    <li><strong>Droit d'opposition :</strong> Vous opposer au traitement</li>
                    <li><strong>Droit de limitation :</strong> Limiter le traitement</li>
                </ul>
                <p>Pour exercer ces droits, contactez-nous à : <a href="mailto:clementvolle@gmail.com">clementvolle@gmail.com</a></p>
            </div>

            <div class="section">
                <h2>8. Cookies</h2>
                <h3>8.1 Types de cookies utilisés</h3>
                <ul>
                    <li><strong>Cookies techniques :</strong> Nécessaires au fonctionnement du site</li>
                    <li><strong>Cookies de session :</strong> Pour maintenir votre connexion</li>
                    <li><strong>Cookies de préférences :</strong> Pour sauvegarder vos paramètres</li>
                </ul>

                <h3>8.2 Gestion des cookies</h3>
                <p>Vous pouvez configurer votre navigateur pour :</p>
                <ul>
                    <li>Accepter ou refuser les cookies</li>
                    <li>Être averti avant l'acceptation de cookies</li>
                    <li>Supprimer les cookies existants</li>
                </ul>
            </div>

            <div class="section">
                <h2>9. Sécurité</h2>
                <p>Nous mettons en œuvre des mesures de sécurité appropriées pour protéger vos données :</p>
                <ul>
                    <li>Cryptage des mots de passe</li>
                    <li>Connexions sécurisées (HTTPS)</li>
                    <li>Accès restreint aux données</li>
                    <li>Sauvegardes régulières</li>
                    <li>Mise à jour de sécurité régulières</li>
                </ul>
            </div>

            <div class="section">
                <h2>10. Modifications</h2>
                <p>Cette politique de confidentialité peut être mise à jour occasionnellement. 
                Nous vous informerons de tout changement significatif par email ou via une notification sur le site.</p>
            </div>

            <div class="section">
                <h2>11. Contact</h2>
                <p>Pour toute question concernant cette politique de confidentialité ou le traitement de vos données, 
                vous pouvez nous contacter :</p>
                <ul>
                    <li><strong>Email :</strong> <a href="mailto:clementvolle@gmail.com">clementvolle@gmail.com</a></li>
                    <li><strong>Objet :</strong> "Protection des données - lupistar.fr"</li>
                </ul>
                <p>Vous avez également le droit de déposer une plainte auprès de la CNIL 
                (<a href="https://www.cnil.fr" target="_blank" rel="noopener">www.cnil.fr</a>) 
                si vous estimez que vos droits ne sont pas respectés.</p>
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