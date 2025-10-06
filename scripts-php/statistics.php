<?php
include './co-bdd.php';

$categorie = $_GET['categorie'] ?? '';

try {
    // Nombre total de films
    $sql_total = "SELECT COUNT(*) as total FROM films WHERE categorie = ?";
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute([$categorie]);
    $result_total = $stmt_total->fetch(PDO::FETCH_ASSOC);
    $total_films = $result_total['total'];

    // Top 3 des studios
    $sql_studios = "SELECT s.nom, COUNT(*) as count 
                    FROM films f 
                    JOIN studios s ON f.studio_id = s.id 
                    WHERE f.categorie = ? 
                    GROUP BY s.nom 
                    ORDER BY count DESC 
                    LIMIT 3";
    $stmt_studios = $pdo->prepare($sql_studios);
    $stmt_studios->execute([$categorie]);
    $result_studios = $stmt_studios->fetchAll(PDO::FETCH_ASSOC);

    $top_studios = [];
    foreach ($result_studios as $row) {
        $top_studios[] = $row['nom'] . ' (' . $row['count'] . ')';
    }

    // Meilleure décennie
    $sql_decade = "SELECT FLOOR(YEAR(date_sortie)/10)*10 as decade, 
                          AVG(note_moyenne) as avg_note, 
                          COUNT(*) as count
                   FROM films 
                   WHERE categorie = ? 
                     AND YEAR(date_sortie) IS NOT NULL 
                     AND note_moyenne IS NOT NULL
                   GROUP BY FLOOR(YEAR(date_sortie)/10)
                   HAVING count >= 3
                   ORDER BY avg_note DESC
                   LIMIT 1";
    $stmt_decade = $pdo->prepare($sql_decade);
    $stmt_decade->execute([$categorie]);
    $decade_data = $stmt_decade->fetch(PDO::FETCH_ASSOC);

    $response = [
        'total_films' => $total_films,
        'top_studios' => !empty($top_studios) ? implode(', ', $top_studios) : 'Aucun studio',
        'best_decade' => $decade_data ? $decade_data['decade'] . 's (' . round($decade_data['avg_note'], 1) . '/10)' : 'Aucune décennie'
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur lors de la récupération des statistiques : ' . $e->getMessage()]);
}
?>