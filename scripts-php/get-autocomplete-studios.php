<?php
header('Content-Type: application/json');

// Connexion à la base de données
require_once 'co-bdd.php';

// Récupérer le terme de recherche et la catégorie
$search = $_GET['search'] ?? '';
$categorie = $_GET['categorie'] ?? '';

if (empty($search) || strlen($search) < 2) {
    die(json_encode([]));
}

try {
    // Préparer la requête pour rechercher les studios (recherche globale, pas de filtrage par catégorie)
    $stmt = $pdo->prepare("SELECT DISTINCT nom FROM studios WHERE nom LIKE ? ORDER BY nom ASC LIMIT 10");
    $searchTerm = $search . '%';
    $stmt->execute([$searchTerm]);
    
    $studios = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $studios[] = $row['nom'];
    }
    
    echo json_encode($studios);
} catch (PDOException $e) {
    die(json_encode(["error" => "Erreur de connexion à la base de données."]));
}
?>