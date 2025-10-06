<?php
// Récupérer les informations de session
$photo_profil = $_SESSION['photo_profil'] ?? 'img/img-profile/profil.png';
$nom_utilisateur = $_SESSION['nom_utilisateur'] ?? 'Utilisateur';

// Paramètres par défaut
$img_class = $img_class ?? '';
$img_id = $img_id ?? 'profilImg';
$img_alt = $img_alt ?? 'Photo de Profil';
$img_style = $img_style ?? '';

// Déterminer si l'image est par défaut ou personnalisée
if ($photo_profil === 'img/img-profile/profil.png' || $photo_profil === 'img/profil.png') {
    // Image par défaut
    $img_class .= 'profil-default';
} else {
    // Image personnalisée
    $img_class .= 'profil-custom';
}

// Construire les attributs
$class_attr = !empty($img_class) ? ' class="' . $img_class . '"' : '';
$id_attr = !empty($img_id) ? ' id="' . $img_id . '"' : '';
$style_attr = !empty($img_style) ? ' style="' . $img_style . '"' : '';

// Afficher l'image
echo '<img src="' . $photo_profil . '"' . $class_attr . $id_attr . ' alt="' . $img_alt . '"' . $style_attr . '>';

// Ajouter le badge de notification si l'utilisateur est connecté
if (isset($_SESSION['user_id'])) {
    echo '<div class="notification-badge hidden" id="profileNotificationBadge">0</div>';
}

// Ajouter le script de persistance de l'image de profil (une seule fois par page)
// Ne recharge l'image que si elle a été changée dans cette session
static $script_added = false;
if (!$script_added) {
    echo '<script>
    // Appliquer immédiatement le timestamp persisté seulement si l\'image a été changée
    (function() {
        // Vérifier si l\'image a été changée dans cette session
        const imageChanged = sessionStorage.getItem("profile_image_changed_this_session");
        if (imageChanged === "true") {
            const timestamp = localStorage.getItem("profile_image_timestamp");
            if (timestamp) {
                const img = document.getElementById("' . $img_id . '");
                if (img && !img.src.includes("?v=")) {
                    const baseSrc = img.src.split("?")[0];
                    img.src = baseSrc + "?v=" + timestamp + "&cache=" + Math.random();
                }
            }
        }
    })();
    </script>';
    $script_added = true;
}
?>