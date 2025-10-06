<?php
include './co-bdd.php';

// Récupérer les paramètres de la requête
$category = isset($_GET['categorie']) ? $_GET['categorie'] : 'Animation';

try {
    // Calcul des statistiques
    $sqlTotalFilms = "SELECT COUNT(*) AS totalFilms FROM films WHERE categorie = ?";
    $stmtTotalFilms = $pdo->prepare($sqlTotalFilms);
    $stmtTotalFilms->execute([$category]);
    $rowTotalFilms = $stmtTotalFilms->fetch(PDO::FETCH_ASSOC);

    $totalFilms = $rowTotalFilms['totalFilms'];

    $sqlNoteMoyenne = "SELECT AVG(note) AS noteMoyenne FROM films WHERE categorie = ?";
    $stmtNoteMoyenne = $pdo->prepare($sqlNoteMoyenne);
    $stmtNoteMoyenne->execute([$category]);
    $rowNoteMoyenne = $stmtNoteMoyenne->fetch(PDO::FETCH_ASSOC);

    $noteMoyenne = $rowNoteMoyenne['noteMoyenne'] !== null ? round($rowNoteMoyenne['noteMoyenne'], 1) : 0;

    // Calculer le pourcentage de films ajoutés (si utilisateur connecté)
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $pourcentageAjoutes = 0;

    if ($user_id) {
        $sqlFilmsAjoutes = "SELECT COUNT(*) AS filmsAjoutes FROM films_utilisateur WHERE user_id = ?";
        $stmtFilmsAjoutes = $pdo->prepare($sqlFilmsAjoutes);
        $stmtFilmsAjoutes->execute([$user_id]);
        $rowFilmsAjoutes = $stmtFilmsAjoutes->fetch(PDO::FETCH_ASSOC);

        $filmsAjoutes = $rowFilmsAjoutes['filmsAjoutes'];
        if ($totalFilms > 0) {
            $pourcentageAjoutes = round(($filmsAjoutes / $totalFilms) * 100, 2);
        }
    }

    // Préparer la réponse au format texte avec des paramètres URL
    $response = http_build_query([
        'totalFilms' => $totalFilms,
        'noteMoyenne' => $noteMoyenne,
        'pourcentageAjoutes' => $pourcentageAjoutes
    ]);
} catch (PDOException $e) {
    error_log("Erreur lors du calcul des statistiques: " . $e->getMessage());
    $response = http_build_query([
        'totalFilms' => 0,
        'noteMoyenne' => 0,
        'pourcentageAjoutes' => 0
    ]);
}

header('Content-Type: application/x-www-form-urlencoded');
echo $response;
?>