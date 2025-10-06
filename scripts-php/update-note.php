<?php
session_start();
error_reporting(E_ALL);
header("Content-Type: application/json"); //Assure un retour JSON
ini_set('display_errors', 1);
include './co-bdd.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    die(json_encode(["error" => "Utilisateur non connecté."]));
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["film_id"], $_POST["note"])) {
    $film_id = intval($_POST["film_id"]);
    $note = floatval($_POST["note"]);

    if ($note < 0 || $note > 10) {
        die(json_encode(["error" => "Note invalide."]));
    }

    error_log("User ID: $user_id | Film ID: $film_id | Note: $note");
    
    try {
        // Vérifier si l'utilisateur a déjà noté ce film
        $sql_check = "SELECT note FROM membres_films_list WHERE films_id = ? AND membres_id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$film_id, $user_id]);

        if ($stmt_check->rowCount() > 0) {
            // Mise à jour de la note existante
            $sql_update = "UPDATE membres_films_list SET note = ? WHERE films_id = ? AND membres_id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$note, $film_id, $user_id]);
        } else {
            // Insertion d'une nouvelle note
            $sql_insert = "INSERT INTO membres_films_list (films_id, membres_id, note) VALUES (?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([$film_id, $user_id, $note]);
        }
        
        // Recalculer la note moyenne
        $sql_avg = "SELECT ROUND(AVG(note), 2) AS moyenne FROM membres_films_list WHERE films_id = ?";
        $stmt_avg = $pdo->prepare($sql_avg);
        $stmt_avg->execute([$film_id]);
        $row_avg = $stmt_avg->fetch(PDO::FETCH_ASSOC);
        $note_moyenne = $row_avg['moyenne'];

        // Mettre à jour la colonne note_moyenne dans la table films
        $sql_update_moyenne = "UPDATE films SET note_moyenne = ? WHERE id = ?";
        $stmt_update_moyenne = $pdo->prepare($sql_update_moyenne);
        $stmt_update_moyenne->execute([$note_moyenne, $film_id]);

        echo json_encode([
            "success" => true, 
            "nouvelle_note_moyenne" => floatval($note_moyenne)
        ]);
        exit;
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour de la note: " . $e->getMessage());
        echo json_encode(["error" => "Erreur lors de la mise à jour de la note."]);
        exit;
    }
} else {
    echo json_encode(["error" => "Requête invalide."]);
    exit;
}
?>
