<?php
// Connexion à la base de données MySQL
include './scripts-php/co-bdd.php';

// Vérifier si l'utilisateur est connecté et récupérer ses préférences
$categories = array("Animation", "Anime", "Série d'Animation", "Film", "Série"); // Ordre par défaut

if (isset($_SESSION['user_id'])) {
    // Inclure le fichier de gestion des préférences
    include './scripts-php/user-preferences.php';
    
    // Récupérer l'ordre personnalisé des catégories
    $userCategories = getUserCategoriesOrder($_SESSION['user_id']);
    if (!empty($userCategories)) {
        $categories = $userCategories;
    }
}

// Pour chaque catégorie, récupérer les films récemment ajoutés depuis la base de données, triés par ID dans l'ordre décroissant
foreach ($categories as $category) {
    try {
        // Utilisation d'une requête préparée pour éviter les problèmes avec les apostrophes
        $sql = "SELECT f.*, s.nom AS nom_studio 
            FROM films f
            LEFT JOIN studios s ON f.studio_id = s.id
            WHERE f.categorie = ?
            ORDER BY f.id DESC
            LIMIT 15"; // Limite à 15 films par catégorie
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$category]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) > 0) {
            // Afficher la catégorie
            echo "<div class='categorie-h4'>";
            echo "<h4>$category</h4>";
            echo "</div>";
            // Afficher les films récemment ajoutés pour cette catégorie
            echo "<div class='film-container'>";
            echo "<div class='carousel-container'>";
            echo "<button class='carousel-btn left'>&#10094;</button>";
            echo "<div class='film-carousel'>";
            foreach ($result as $row) {
                // Affichage des informations du film
                $nomStudio = !empty($row['nom_studio']) ? $row['nom_studio'] : "Inconnu";

                echo "<div class='recent-film-item' data-id='" . $row['id'] . "'>";
                echo "<div class='film-image'><img src='" . $row['image_path'] . "' alt='" . $row['nom_film'] . "'></div>";
                echo "<div class='film-details'>";
                echo "<h3>" . $row['nom_film'] . "</h3>";
                echo "<p class='studio'><strong><U>Studio:</U>&nbsp; </strong> " . htmlspecialchars($nomStudio) . "</p>";
                echo "<p class='date-sortie'><strong><U>Date de sortie:</U>&nbsp; </strong> " . $row['date_sortie'] . "</p>";
                echo "</div></div>";
            }
            echo "</div>"; // Fin film-carousel
            echo "<button class='carousel-btn right'>&#10095;</button>"; // Flèche droite
            echo "</div>"; // Fin carousel-container
            echo "</div>"; // Fin film-container
        } else {
            echo "<p>Aucun film/série récemment ajouté trouvé dans la catégorie : $category.</p>";
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des films pour la catégorie $category : " . $e->getMessage());
        echo "<p>Erreur lors du chargement des films pour la catégorie : $category.</p>";
    }
}
?>