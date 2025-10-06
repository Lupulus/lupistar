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
            'total_films' => 0,
            'top_studios' => '-',
            'best_decade' => '-'
        ]);
        exit;
    }

    $categorie = isset($_GET['categorie']) ? $_GET['categorie'] : 'Animation';

    // Compter le nombre total de films de cette catégorie dans la liste de l'utilisateur
    $sqlTotal = "SELECT COUNT(*) as total 
                 FROM films f
                 INNER JOIN membres_films_list mfl ON f.id = mfl.films_id AND mfl.membres_id = ?
                 WHERE f.categorie = ?";
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute([$user_id, $categorie]);
    $totalFilms = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

    // Récupérer les 3 studios les plus représentés dans la liste de l'utilisateur pour cette catégorie
    $sqlStudios = "SELECT s.nom, COUNT(*) as count 
                   FROM films f
                   LEFT JOIN studios s ON f.studio_id = s.id
                   INNER JOIN membres_films_list mfl ON f.id = mfl.films_id AND mfl.membres_id = ?
                   WHERE f.categorie = ? AND s.nom IS NOT NULL
                   GROUP BY s.nom
                   ORDER BY count DESC, s.nom ASC
                   LIMIT 3";
    $stmtStudios = $pdo->prepare($sqlStudios);
    $stmtStudios->execute([$user_id, $categorie]);
    
    $topStudios = [];
    while ($row = $stmtStudios->fetch(PDO::FETCH_ASSOC)) {
        $topStudios[] = $row['nom'] . ' (' . $row['count'] . ')';
    }
    $topStudiosText = !empty($topStudios) ? implode(', ', $topStudios) : '-';

    // Récupérer la décennie la mieux représentée dans la liste de l'utilisateur pour cette catégorie
    $sqlDecade = "SELECT FLOOR(YEAR(f.date_sortie) / 10) * 10 as decade, COUNT(*) as count 
                  FROM films f
                  INNER JOIN membres_films_list mfl ON f.id = mfl.films_id AND mfl.membres_id = ?
                  WHERE f.categorie = ? AND f.date_sortie IS NOT NULL
                  GROUP BY decade
                  ORDER BY count DESC, decade DESC
                  LIMIT 1";
    $stmtDecade = $pdo->prepare($sqlDecade);
    $stmtDecade->execute([$user_id, $categorie]);
    
    $bestDecade = '-';
    if ($row = $stmtDecade->fetch(PDO::FETCH_ASSOC)) {
        $decade = $row['decade'];
        $count = $row['count'];
        $bestDecade = $decade . 's (' . $count . ' films)';
    }

    // Retourner les statistiques en JSON
    echo json_encode([
        'total_films' => $totalFilms . ' films',
        'top_studios' => $topStudiosText,
        'best_decade' => $bestDecade
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO dans ma-liste statistics.php: " . $e->getMessage());
    echo json_encode([
        'total_films' => '0 films',
        'top_studios' => '-',
        'best_decade' => '-'
    ]);
}
?>