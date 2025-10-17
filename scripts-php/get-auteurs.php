<?php
header('Content-Type: application/json');

// Inclure la configuration de la base de données
require_once './co-bdd.php';

try {
    // Vérifier si la connexion PDO est disponible
    if (!isset($pdo)) {
        echo json_encode(["error" => "Service de base de données indisponible"]);
        exit;
    }

    // Récupérer la catégorie depuis les paramètres GET
    $categorie = $_GET['categorie'] ?? '';

    if (empty($categorie)) {
        echo json_encode([]);
        exit;
    }

    // Préparer la requête pour récupérer les auteurs selon la catégorie avec tri par réputation
    // La réputation est calculée par le nombre de films de cette catégorie associés à l'auteur
    $stmt = $pdo->prepare("
        SELECT a.id, a.nom, COUNT(f.id) as reputation
        FROM auteurs a
        LEFT JOIN films f ON a.id = f.auteur_id AND f.categorie = ?
        WHERE FIND_IN_SET(?, a.categorie) > 0
        GROUP BY a.id, a.nom
        ORDER BY reputation DESC, a.nom DESC
    ");
    $stmt->execute([$categorie, $categorie]);

    $auteurs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $auteurs[] = [
            'id' => $row['id'],
            'nom' => $row['nom'],
            'reputation' => $row['reputation']
        ];
    }

    echo json_encode($auteurs);
} catch (PDOException $e) {
    echo json_encode(["error" => "Erreur de base de données: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["error" => "Erreur système: " . $e->getMessage()]);
}
?>