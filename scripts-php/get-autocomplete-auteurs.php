<?php
header('Content-Type: application/json');

// Connexion à la base de données
require_once 'co-bdd.php';

// Récupérer le terme de recherche
$search = $_GET['search'] ?? '';

if (empty($search) || strlen($search) < 2) {
    die(json_encode([]));
}

try {
    // Préparer la requête pour rechercher les auteurs
    $stmt = $pdo->prepare("SELECT DISTINCT nom FROM auteurs WHERE nom LIKE ? ORDER BY nom ASC LIMIT 10");
    $searchTerm = $search . '%';
    $stmt->execute([$searchTerm]);
    
    $auteurs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $auteurs[] = $row['nom'];
    }
    
    echo json_encode($auteurs);
} catch (PDOException $e) {
    die(json_encode(["error" => "Erreur de connexion à la base de données."]));
}
?>