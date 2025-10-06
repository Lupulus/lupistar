<?php
session_start();

// Inclure la configuration de la base de données
require_once 'co-bdd.php';

// Nom du fichier de log des erreurs
$errorLogFile = "/var/www/html/logs/error.txt";
if (!file_exists(dirname($errorLogFile))) {
    mkdir(dirname($errorLogFile), 0777, true);
}
if (!file_exists($errorLogFile)) {
    touch($errorLogFile);
}

// Fonction pour enregistrer les erreurs
function logError($message) {
    global $errorLogFile;
    file_put_contents($errorLogFile, "[" . date("Y-m-d H:i:s") . "] " . $message . "\n", FILE_APPEND | LOCK_EX);
}

// Vérification de la connexion utilisateur et du niveau admin
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$userTitle = $_SESSION['titre'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

// Définir la hiérarchie des titres
$titres_hierarchie = [
    'Débutant' => 1,
    'Amateur' => 2,
    'Passionné' => 3,
    'Expert' => 4,
    'Maître' => 5,
    'Admin' => 6,
    'Super-Admin' => 7
];

$user_level = $titres_hierarchie[$userTitle] ?? 0;

// Vérifier si l'utilisateur est admin
if (!$isLoggedIn || $user_level < 6 || !$userId) {
    logError("Tentative d'accès non autorisée à get-pending-films.php - Utilisateur: " . ($userId ?? 'inconnu') . " - Titre: " . $userTitle);
    die(json_encode(["error" => "Accès non autorisé."]));
}

header('Content-Type: application/json');

try {
    // Récupérer tous les films en attente avec les informations des membres, studios, auteurs et pays
    $query = "SELECT 
                ft.id,
                ft.nom_film,
                ft.description,
                ft.categorie,
                ft.image_path,
                ft.ordre_suite,
                ft.saison,
                ft.nbrEpisode,
                ft.date_sortie,
                ft.date_proposition,
                ft.statut,
                ft.commentaire_admin,
                m.username as propose_par_pseudo,
                s.nom as studio_nom,
                a.nom as auteur_nom,
                p.nom as pays_nom
              FROM films_temp ft
              LEFT JOIN membres m ON ft.propose_par = m.id
              LEFT JOIN studios s ON ft.studio_id = s.id
              LEFT JOIN auteurs a ON ft.auteur_id = a.id
              LEFT JOIN pays p ON ft.pays_id = p.id
              WHERE ft.statut = 'en_attente'
              ORDER BY ft.date_proposition DESC";

    $result = $pdo->query($query);
    
    if (!$result) {
        throw new Exception("Erreur lors de la récupération des films en attente");
    }

    $films = [];
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        // Récupérer les sous-genres pour ce film
        $sous_genres_query = "SELECT sg.nom 
                             FROM films_temp_sous_genres ftsg
                             JOIN sous_genres sg ON ftsg.sous_genre_id = sg.id
                             WHERE ftsg.film_temp_id = ?";
        
        $stmt = $pdo->prepare($sous_genres_query);
        $stmt->execute([$row['id']]);
        
        $sous_genres = [];
        while ($sg_row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sous_genres[] = $sg_row['nom'];
        }
        
        // Ajouter les sous-genres au film
        $row['sous_genres'] = $sous_genres;
        
        // Formater la date de proposition
        $row['date_proposition_formatted'] = date('d/m/Y H:i', strtotime($row['date_proposition']));
        
        $films[] = $row;
    }

    echo json_encode([
        "success" => true,
        "films" => $films,
        "count" => count($films)
    ]);

} catch (PDOException $e) {
    logError("Erreur dans get-pending-films.php : " . $e->getMessage());
    echo json_encode(["error" => "Erreur lors de la récupération des films en attente."]);
} catch (Exception $e) {
    logError("Erreur dans get-pending-films.php : " . $e->getMessage());
    echo json_encode(["error" => "Erreur lors de la récupération des films en attente."]);
}
?>