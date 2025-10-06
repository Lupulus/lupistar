<?php
session_start(); // Démarrer la session si ce n'est pas déjà fait

// Déconnecter l'utilisateur en détruisant toutes les variables de session
session_unset();
session_destroy();

// Rediriger vers la page d'accueil ou une autre page après la déconnexion
header("Location: ../index.php");
exit;
?>
