<?php
session_start();
include 'co-bdd.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Désactiver l'affichage des erreurs pour éviter de corrompre la réponse JSON

try {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Vous devez être connecté pour effectuer cette action.'
        ]);
        exit;
    }

    // Vérifier si la méthode est POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Méthode non autorisée.'
        ]);
        exit;
    }

    // Récupérer les données
    $user_id = (int)$_SESSION['user_id'];
    $film_id = isset($_POST['film_id']) ? (int)$_POST['film_id'] : 0;

    // Vérifier que l'ID du film est valide
    if ($film_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID du film invalide.'
        ]);
        exit;
    }

    // Vérifier que le film existe dans la liste de l'utilisateur
    $sqlCheck = "SELECT COUNT(*) as count FROM membres_films_list WHERE membres_id = ? AND films_id = ?";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$user_id, $film_id]);
    $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Ce film n\'est pas dans votre liste.'
        ]);
        exit;
    }

    // Supprimer le film de la liste personnelle
    $sqlDelete = "DELETE FROM membres_films_list WHERE membres_id = ? AND films_id = ?";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $success = $stmtDelete->execute([$user_id, $film_id]);

    if ($success && $stmtDelete->rowCount() > 0) {
        // Recalculer la note moyenne du film après suppression
        include 'recalculate-average-note.php';
        $nouvelle_note_moyenne = recalculateFilmAverageNote($pdo, $film_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Le film a été retiré de votre liste avec succès.',
            'nouvelle_note_moyenne' => $nouvelle_note_moyenne
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la suppression du film de votre liste.'
        ]);
    }

} catch (PDOException $e) {
    error_log("Erreur PDO dans remove-from-list.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données. Veuillez réessayer plus tard.'
    ]);
} catch (Exception $e) {
    error_log("Erreur générale dans remove-from-list.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur inattendue s\'est produite. Veuillez réessayer plus tard.'
    ]);
}
?>