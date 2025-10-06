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

    // Préparer la requête pour récupérer les studios selon la catégorie
    $stmt = $pdo->prepare("SELECT id, nom FROM studios WHERE FIND_IN_SET(?, categorie) > 0 ORDER BY nom ASC");
    $stmt->execute([$categorie]);

    $studios = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $studios[] = [
            'id' => $row['id'],
            'nom' => $row['nom']
        ];
    }

    echo json_encode($studios);
} catch (PDOException $e) {
    echo json_encode(["error" => "Erreur de base de données: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["error" => "Erreur système: " . $e->getMessage()]);
}
?>