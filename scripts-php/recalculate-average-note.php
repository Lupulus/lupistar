<?php
/**
 * Script pour recalculer la note moyenne d'un film
 * Utilisé lors de l'ajout ou suppression d'un film de la liste personnelle
 */

function recalculateFilmAverageNote($pdo, $film_id) {
    try {
        // Recalculer la note moyenne basée sur toutes les notes personnelles
        $sql_avg = "SELECT ROUND(AVG(note), 2) AS moyenne FROM membres_films_list WHERE films_id = ? AND note IS NOT NULL";
        $stmt_avg = $pdo->prepare($sql_avg);
        $stmt_avg->execute([$film_id]);
        $row_avg = $stmt_avg->fetch(PDO::FETCH_ASSOC);
        
        $note_moyenne = $row_avg['moyenne'];
        
        // Si aucune note n'existe, mettre la note moyenne à NULL
        if ($note_moyenne === null) {
            $sql_update_moyenne = "UPDATE films SET note_moyenne = NULL WHERE id = ?";
            $stmt_update_moyenne = $pdo->prepare($sql_update_moyenne);
            $stmt_update_moyenne->execute([$film_id]);
        } else {
            // Mettre à jour la colonne note_moyenne dans la table films
            $sql_update_moyenne = "UPDATE films SET note_moyenne = ? WHERE id = ?";
            $stmt_update_moyenne = $pdo->prepare($sql_update_moyenne);
            $stmt_update_moyenne->execute([$note_moyenne, $film_id]);
        }
        
        return $note_moyenne;
        
    } catch (PDOException $e) {
        error_log("Erreur lors du recalcul de la note moyenne pour le film $film_id: " . $e->getMessage());
        return false;
    }
}
?>