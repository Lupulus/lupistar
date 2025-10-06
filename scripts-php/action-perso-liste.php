<?php
session_start();

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Définir le type de contenu JSON
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['film_id']) && isset($_POST['nom_film']) && isset($_POST['action'])) {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403); // Interdit
        echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour ajouter un film à votre liste.']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $film_id = $_POST['film_id'];
    $nom_film = $_POST['nom_film'];
    $action = $_POST['action'];

    // Connexion à la base de données
    include __DIR__ . '/co-bdd.php';

    try {
        // Préparer la requête en fonction de l'action
        if ($action === 'add') {
            $sql = "INSERT INTO membres_films_list (membres_id, films_id, note) VALUES (?, ?, 0)";
        } elseif ($action === 'remove') {
            $sql = "DELETE FROM membres_films_list WHERE membres_id = ? AND films_id = ?";
        } else {
            http_response_code(400); // Mauvaise requête
            echo json_encode(['success' => false, 'message' => 'Action invalide.']);
            exit();
        }    

        // Exécuter la requête
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$user_id, $film_id])) {
            // Recalculer la note moyenne du film après ajout/suppression
            include 'recalculate-average-note.php';
            $nouvelle_note_moyenne = recalculateFilmAverageNote($pdo, $film_id);
            
            if ($action === 'add') {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Film ajouté à votre liste !',
                    'nouvelle_note_moyenne' => $nouvelle_note_moyenne
                ]);
            } elseif ($action === 'remove') {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Film supprimé de votre liste !',
                    'nouvelle_note_moyenne' => $nouvelle_note_moyenne
                ]);
            }
        } else {
            http_response_code(500);
            if ($action === 'add') {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du film à votre liste.']);
            } elseif ($action === 'remove') {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du film de votre liste.']);
            }
        }

    } catch (PDOException $e) {
        error_log("Erreur PDO action-perso-liste: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
    }
} else {
    http_response_code(400); // Mauvaise requête
    echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
}
?>