<?php
session_start(); // Démarrer la session si ce n'est pas déjà fait

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403); // Interdit
    echo "Vous devez être connecté pour ajouter un film à votre liste.";
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
if (isset($_SESSION['user_id'])) {
    $membres_id = $_SESSION['user_id']; // Utiliser la clé correcte 'user_id'
} else {
    http_response_code(500); // Erreur serveur interne
    echo "ID de l'utilisateur non défini dans la session.";
    exit;
}

// Récupérer l'ID du film depuis la requête POST
if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $films_id = $_POST['id'];
} else {
    http_response_code(400); // Mauvaise requête
    echo "ID de film invalide.";
    exit;
}

// Récupérer le nom du film depuis la requête POST
if (isset($_POST['nom_film'])) {
    $nom_film = $_POST['nom_film'];
} else {
    $nom_film = ""; // Définir une valeur par défaut si le nom du film n'est pas reçu
}

// Connexion à la base de données
include 'co-bdd.php';

try {
    // Ajouter le film à la liste personnelle de l'utilisateur
    $sql = "INSERT INTO membres_films_list (membres_id, films_id) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$membres_id, $films_id])) {
        // Recalculer la note moyenne du film après ajout
        include 'recalculate-average-note.php';
        $nouvelle_note_moyenne = recalculateFilmAverageNote($pdo, $films_id);
        
        echo "Le film '" . htmlspecialchars(urldecode($nom_film)) . "' a été ajouté à votre liste personnelle.";
    }
} catch (PDOException $e) {
    // Si le film est déjà dans la liste, ne pas ajouter de doublon
    if ($e->getCode() == 23000) { // Duplicate entry error code for PDO
        echo "Le film est déjà dans votre liste.";
    } else {
        error_log("Erreur PDO dans add-perso-liste-disabled.php: " . $e->getMessage());
        http_response_code(500); // Erreur serveur interne
        echo "Erreur lors de l'ajout du film à la liste personnelle.";
    }
}
?>