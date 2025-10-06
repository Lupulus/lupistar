<?php
include './co-bdd.php';

$categorie = $_GET['categorie'] ?? '';

try {
    // Récupérer les années distinctes
    $sql_annees = "SELECT DISTINCT YEAR(date_sortie) as annee 
                   FROM films 
                   WHERE categorie = ? AND YEAR(date_sortie) IS NOT NULL 
                   ORDER BY annee DESC";
    $stmt_annees = $pdo->prepare($sql_annees);
    $stmt_annees->execute([$categorie]);
    $result_annees = $stmt_annees->fetchAll(PDO::FETCH_ASSOC);

    $annees = [];
    foreach ($result_annees as $row) {
        $annees[] = (int)$row['annee'];
    }

    // Récupérer les studios distincts
    $sql_studios = "SELECT DISTINCT s.nom 
                    FROM films f
                    JOIN studios s ON f.studio_id = s.id
                    WHERE f.categorie = ?
                    ORDER BY s.nom ASC";
    $stmt_studios = $pdo->prepare($sql_studios);
    $stmt_studios->execute([$categorie]);
    $result_studios = $stmt_studios->fetchAll(PDO::FETCH_ASSOC);

    $studios = [];
    foreach ($result_studios as $row) {
        $studios[] = $row['nom'];
    }

    // Récupérer les pays distincts
    $sql_pays = "SELECT DISTINCT p.nom 
                 FROM films f
                 JOIN pays p ON f.pays_id = p.id
                 WHERE f.categorie = ?
                 ORDER BY p.nom ASC";
    $stmt_pays = $pdo->prepare($sql_pays);
    $stmt_pays->execute([$categorie]);
    $result_pays = $stmt_pays->fetchAll(PDO::FETCH_ASSOC);

    $pays = [];
    foreach ($result_pays as $row) {
        $pays[] = $row['nom'];
    }

    $response = [
        'annees' => $annees,
        'studios' => $studios,
        'pays' => $pays
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur lors de la récupération des filtres : ' . $e->getMessage()]);
}
?>
