<?php
session_start();
include '../co-bdd.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Vérifier si l'utilisateur est connecté
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    
    if ($user_id === null) {
        echo json_encode([
            'studios' => [],
            'annees' => [],
            'pays' => []
        ]);
        exit;
    }

    $categorie = isset($_GET['categorie']) ? $_GET['categorie'] : 'Animation';

    // Récupérer les studios distincts pour les films de l'utilisateur dans cette catégorie
    $sqlStudios = "SELECT DISTINCT s.nom 
                   FROM films f
                   LEFT JOIN studios s ON f.studio_id = s.id
                   INNER JOIN membres_films_list mfl ON f.id = mfl.films_id AND mfl.membres_id = ?
                   WHERE f.categorie = ? AND s.nom IS NOT NULL
                   ORDER BY s.nom ASC";
    $stmtStudios = $pdo->prepare($sqlStudios);
    $stmtStudios->execute([$user_id, $categorie]);
    
    $studios = [];
    while ($row = $stmtStudios->fetch(PDO::FETCH_ASSOC)) {
        $studios[] = $row['nom'];
    }

    // Récupérer les années distinctes pour les films de l'utilisateur dans cette catégorie
    $sqlAnnees = "SELECT DISTINCT YEAR(f.date_sortie) AS annee 
                  FROM films f
                  INNER JOIN membres_films_list mfl ON f.id = mfl.films_id AND mfl.membres_id = ?
                  WHERE f.categorie = ? AND f.date_sortie IS NOT NULL
                  ORDER BY annee DESC";
    $stmtAnnees = $pdo->prepare($sqlAnnees);
    $stmtAnnees->execute([$user_id, $categorie]);
    
    $annees = [];
    while ($row = $stmtAnnees->fetch(PDO::FETCH_ASSOC)) {
        $annees[] = $row['annee'];
    }

    // Récupérer les pays distincts pour les films de l'utilisateur dans cette catégorie
    $sqlPays = "SELECT DISTINCT p.nom 
                FROM films f
                LEFT JOIN pays p ON f.pays_id = p.id
                INNER JOIN membres_films_list mfl ON f.id = mfl.films_id AND mfl.membres_id = ?
                WHERE f.categorie = ? AND p.nom IS NOT NULL
                ORDER BY p.nom ASC";
    $stmtPays = $pdo->prepare($sqlPays);
    $stmtPays->execute([$user_id, $categorie]);
    
    $pays = [];
    while ($row = $stmtPays->fetch(PDO::FETCH_ASSOC)) {
        $pays[] = $row['nom'];
    }

    // Retourner les données en JSON
    echo json_encode([
        'studios' => $studios,
        'annees' => $annees,
        'pays' => $pays
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO dans ma-liste filters.php: " . $e->getMessage());
    echo json_encode([
        'studios' => [],
        'annees' => [],
        'pays' => []
    ]);
}
?>