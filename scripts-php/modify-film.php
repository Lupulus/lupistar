<?php
require_once 'co-bdd.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fichier de logs
$errorLogFile = "/var/www/html/logs/error.txt";

// Fonction pour enregistrer les erreurs
function logError($message) {
    global $errorLogFile;
    file_put_contents($errorLogFile, "[" . date("Y-m-d H:i:s") . "] " . $message . "\n", FILE_APPEND | LOCK_EX);
}

// Vérifier si la requête est bien une POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die(json_encode(["error" => "Requête invalide."]));
}

// Vérification et récupération de l'ID du film
if (!isset($_POST['film_id']) || !is_numeric($_POST['film_id'])) {
    logError("ID du film invalide.");
    die(json_encode(["error" => "ID du film invalide."]));
}

$filmId = (int) $_POST['film_id'];
$nom_film = trim($_POST['nom_film']);
$description = !empty($_POST['description']) ? trim($_POST['description']) : "Pas de description";
$categorie = $_POST['categorie'];
$ordre_suite = !empty($_POST['ordre_suite']) ? (int) $_POST['ordre_suite'] : 1;
$date_sortie = (int) $_POST['date_sortie'];

// Gérer saison et nbrEpisode
$saison = null;
$nbrEpisode = null;
$ordre_suite = null;

if (in_array($categorie, ['Série', "Série d'Animation"])) {
    $saison = isset($_POST['saison']) ? (int) $_POST['saison'] : 1;
    $nbrEpisode = isset($_POST['nbrEpisode']) ? (int) $_POST['nbrEpisode'] : null;

    if (!$nbrEpisode) {
        die(json_encode(["error" => "Nombre d'épisodes requis pour une série."]));
    }
} else {
    $ordre_suite = isset($_POST['ordre_suite']) ? (int) $_POST['ordre_suite'] : 1;
}

// ✅ Vérification que les IDs existent bien en BDD avant modification
function validateEntityExists($pdo, $table, $id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn() > 0;
}

// Récupération stricte des IDs existants
$studio_id = isset($_POST['studio_id']) && is_numeric($_POST['studio_id']) ? (int) $_POST['studio_id'] : null;
$auteur_id = isset($_POST['auteur_id']) && is_numeric($_POST['auteur_id']) ? (int) $_POST['auteur_id'] : null;
$pays_id = isset($_POST['pays_id']) && is_numeric($_POST['pays_id']) ? (int) $_POST['pays_id'] : null;

// Vérification des entités
if (!validateEntityExists($pdo, "studios", $studio_id)) {
    logError("ID de studio invalide : " . $studio_id);
    die(json_encode(["error" => "Studio sélectionné invalide."]));
}
if (!validateEntityExists($pdo, "auteurs", $auteur_id)) {
    logError("ID d'auteur invalide : " . $auteur_id);
    die(json_encode(["error" => "Auteur sélectionné invalide."]));
}
if (!validateEntityExists($pdo, "pays", $pays_id)) {
    logError("ID de pays invalide : " . $pays_id);
    die(json_encode(["error" => "Pays sélectionné invalide."]));
}

// 🎯 Mise à jour dynamique selon la catégorie
$sql = "UPDATE films SET nom_film=?, description=?, categorie=?, date_sortie=?, studio_id=?, auteur_id=?, pays_id=?";
$params = [$nom_film, $description, $categorie, $date_sortie, $studio_id, $auteur_id, $pays_id];

if (in_array($categorie, ['Série', "Série d'Animation"])) {
    $sql .= ", saison=?, nbrEpisode=?, ordre_suite=NULL";
    $params[] = $saison;
    $params[] = $nbrEpisode;
} else {
    $sql .= ", ordre_suite=?, saison=NULL, nbrEpisode=NULL";
    $params[] = $ordre_suite;
}

$sql .= " WHERE id=?";
$params[] = $filmId;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // ✅ **Suppression des anciens sous-genres**
    $stmt = $pdo->prepare("DELETE FROM films_sous_genres WHERE film_id = ?");
    $stmt->execute([$filmId]);

    // ✅ **Insertion des nouveaux sous-genres**
    if (!empty($_POST['sous_genres'])) {
        $sous_genres = is_array($_POST['sous_genres']) ? $_POST['sous_genres'] : explode(',', $_POST['sous_genres']);
        $stmt = $pdo->prepare("INSERT INTO films_sous_genres (film_id, sous_genre_id) VALUES (?, ?)");

        foreach ($sous_genres as $sous_genre_id) {
            $stmt->execute([$filmId, $sous_genre_id]);
        }
    }

    // ✅ **Réponse JSON après succès**
    echo json_encode(["success" => "Film modifié avec succès !"]);
    exit();
} catch (PDOException $e) {
    logError("Erreur lors de la modification du film : " . $e->getMessage());
    die(json_encode(["error" => "Erreur lors de la mise à jour du film."]));
}
?>