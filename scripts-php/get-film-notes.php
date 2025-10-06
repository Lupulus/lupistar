<?php
session_start();
error_reporting(E_ALL);
header("Content-Type: application/json"); // Assure un retour JSON
ini_set('display_errors', 1);
require_once 'co-bdd.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die(json_encode(["error" => "ID de film invalide."]));
}

$film_id = intval($_GET['id']);

// Générer les intervalles de notes
$intervals = [
    "0-1", "1-2", "2-3", "3-4", "4-5",
    "5-6", "6-7", "7-8", "8-9", "9-10"
];

// Initialiser un tableau des votes
$votes_par_intervalle = array_fill_keys($intervals, 0);

try {
    // Récupérer les votes des membres
    $sql = "SELECT note FROM membres_films_list WHERE films_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$film_id]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $note = floatval($row['note']);

        // Trouver l'intervalle correspondant
        foreach ($intervals as $interval) {
            list($min, $max) = explode("-", $interval);
            $min = floatval($min);
            $max = floatval($max);
        
            // Vérifie si la note appartient bien à l'intervalle
            if (($note >= $min && $note < $max) || ($note == 10.0 && $max == 10.0)) {
                $votes_par_intervalle[$interval]++;
                break;
            }
        }    
    }

    // Retourner les nouvelles données en JSON
    echo json_encode(["success" => true, "votes_par_intervalle" => $votes_par_intervalle]);
} catch (PDOException $e) {
    echo json_encode(["error" => "Erreur de base de données: " . $e->getMessage()]);
}
?>