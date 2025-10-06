<?php
// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // Récupérer les informations de l'utilisateur
    $nom_utilisateur = $_SESSION['nom_utilisateur'] ?? 'Utilisateur';
    $status = $_SESSION['titre'] ?? 'Membre';
    $user_id = $_SESSION['user_id'] ?? null;
    
    // Vérifier si l'utilisateur peut proposer un film
    $canProposeFilm = ($status === 'Membre');
    
    echo '<a class="deco" href="./scripts-php/logout.php">Se déconnecter</a><br>';
    
    // Lien Mon compte avec badge de notification
    echo '<a class="mon-compte" href="./mon-compte.php">Mon compte';
    if ($user_id) {
        echo '<div class="notification-badge hidden" id="menuNotificationBadge">0</div>';
    }
    echo '</a><br>';
    
    echo '<div class="deroulant-notif">';
    if (!$canProposeFilm) {
        echo '<a class="demande-film dernier-element" href="./proposer-film.php">Proposer un film</a><br>';
    } else {
        echo '<div class="film-restriction-container">';
        echo '<a class="demande-film dernier-element disabled" href="#" disabled>Proposer un film</a>';
        echo '<div class="restriction-tooltip">';
        echo '<span class="tooltip-text">Titre "Amateur" requis</span>';
        echo '<span class="tooltip-arrow">◀</span>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
    
    // Vérifier si l'utilisateur est admin (seulement si connecté)
    if ($status === 'Super-Admin' || $status === 'Admin') {
        echo '<a class="admin element" href="./administration.php">Administration</a><br>';
    }
} else {
    echo '<a class="co dernier-element" href="./login.php">Se connecter</a><br>'; // Afficher "Se connecter" si l'utilisateur n'est pas connecté
}
?>
