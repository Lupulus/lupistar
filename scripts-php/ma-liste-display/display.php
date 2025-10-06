<?php
session_start();
// Connexion à la base de données
include '../co-bdd.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Vérifier si l'utilisateur est connecté
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    
    if ($user_id === null) {
        echo "<div class='pre-requis'>";
        echo "<h2>Veuillez vous connecter pour voir votre liste personnelle.</h2>";
        echo "<div class='buttons'>";
        echo "<a href='login.php'><button class='btn'>Se connecter</button></a>";
        echo "<a href='register.php'><button class='btn'>S'inscrire</button></a>";
        echo "</div>";
        echo "</div>";
        return;
    }

    // Variables dynamiques
    $categorie = isset($_GET['categorie']) ? $_GET['categorie'] : 'Animation';
    $recherche = isset($_GET['recherche']) ? htmlspecialchars($_GET['recherche']) : '';
    $studios = isset($_GET['studio']) ? array_map('htmlspecialchars', explode(',', $_GET['studio'])) : [];
    $annee = isset($_GET['annee']) ? (int)$_GET['annee'] : '';
    $noteMoyenne = isset($_GET['note']) ? (int)$_GET['note'] : '';
    $pays = isset($_GET['pays']) ? htmlspecialchars($_GET['pays']) : '';
    $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';
    $episodes = isset($_GET['episodes']) ? htmlspecialchars($_GET['episodes']) : '';
    $pageCourante = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $filmsParPage = (int)25;
    $offset = (int)(($pageCourante - 1) * $filmsParPage);

    // Construction de la requête optimisée avec COUNT() OVER() pour éviter une requête séparée
    // Inclure la note personnelle de l'utilisateur au lieu de la note moyenne
    $sql = "SELECT f.id, f.nom_film, s.nom AS studio, a.nom AS auteur, p.nom AS pays, 
                   f.date_sortie, f.note_moyenne, f.description, f.image_path, f.saison, f.nbrEpisode,
                   mfl.note, COUNT(*) OVER() as total_count
            FROM films f
            INNER JOIN membres_films_list mfl ON f.id = mfl.films_id
            LEFT JOIN studios s ON f.studio_id = s.id
            LEFT JOIN auteurs a ON f.auteur_id = a.id
            LEFT JOIN pays p ON f.pays_id = p.id
            WHERE mfl.membres_id = ? AND f.categorie = ?";

    $params = [$user_id, $categorie];

    // Ajout des filtres dynamiques
    if (!empty($recherche)) {
        $sql .= " AND f.nom_film LIKE ?";
        $params[] = '%' . $recherche . '%';
    }

    // Filtre par studio (nouvelle relation avec `studio_id`)
    if (!empty($studios) && !in_array('Tous les studios', $studios)) {
        $placeholders = implode(',', array_fill(0, count($studios), '?'));
        $sql .= " AND s.nom IN ($placeholders)";
        $params = array_merge($params, $studios);
    }

    // Filtre par année
    if (!empty($annee)) {
        $sql .= " AND YEAR(f.date_sortie) = ?";
        $params[] = $annee;
    }

    // Filtre par note moyenne
    if (!empty($noteMoyenne)) {
        $sql .= " AND f.note_moyenne >= ?";
        $params[] = $noteMoyenne;
    }

    // Filtre par pays
    if (!empty($pays) && $pays !== 'Tous les pays') {
        $sql .= " AND p.nom = ?";
        $params[] = $pays;
    }

    // Filtre par type (Film/Série) pour Anime
    if (!empty($type) && $categorie === 'Anime') {
        if ($type === 'film') {
            $sql .= " AND (f.saison IS NULL OR f.saison = 0) AND (f.nbrEpisode IS NULL OR f.nbrEpisode = 0)";
        } elseif ($type === 'serie') {
            $sql .= " AND ((f.saison IS NOT NULL AND f.saison > 0) OR (f.nbrEpisode IS NOT NULL AND f.nbrEpisode > 0))";
        }
    }

    // Filtre par nombre d'épisodes
    if (!empty($episodes)) {
        switch ($episodes) {
            case '0-13':
                $sql .= " AND f.nbrEpisode BETWEEN 0 AND 13";
                break;
            case '0-24':
                $sql .= " AND f.nbrEpisode BETWEEN 0 AND 24";
                break;
            case '13-24':
                $sql .= " AND f.nbrEpisode BETWEEN 13 AND 24";
                break;
            case '24+':
                $sql .= " AND f.nbrEpisode > 24";
                break;
        }
    }

    // Tri par date de sortie décroissante puis par nom
    $sql .= " ORDER BY f.date_sortie DESC, f.nom_film ASC";
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $filmsParPage;
    $params[] = $offset;

    // Exécution de la requête
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer le nombre total pour la pagination
    $totalFilms = 0;
    if (!empty($result)) {
        $totalFilms = $result[0]['total_count'];
    }

    if (!empty($result)) {
        // Début de la div contenant les films
        echo "<div class='film-container-tab'>";

        // Boucle à travers les résultats pour afficher chaque film
        foreach ($result as $row) {
            // Affichage des informations du film dans une box
            echo "<div class='film-box' data-id='" . $row['id'] . "'>";
            
            // Colonne 1: Image du film
            echo "<div class='film-image'>";
            echo "<img src='" . $row['image_path'] . "' alt='" . $row['nom_film'] . "'>";
            echo "</div>";
            
            // Colonne 2: Métadonnées (Studio, Sortie, Pays)
            echo "<div class='film-metadata'>";
            
            // Titre avec informations série si applicable
            $titre = $row['nom_film'];
            $isSerie = !empty($row['saison']) && $row['saison'] > 0;
            
            if ($isSerie) {
                $titre .= " <span class='serie-info'>";
                if (!empty($row['saison'])) {
                    $titre .= "S" . $row['saison'];
                }
                if (!empty($row['nbrEpisode'])) {
                    $titre .= " (" . $row['nbrEpisode'] . " ép.)";
                }
                $titre .= "</span>";
            }
            
            echo "<h3 class='nom'>" . $titre . "</h3>";
            echo "<p class='studio'><strong>Studio:</strong> " . $row['studio'] . "</p>";
            echo "<p class='date'><strong>Sortie:</strong> " . $row['date_sortie'] . "</p>";
            
            // Afficher le nombre d'épisodes pour les films aussi (si disponible)
            if (!$isSerie && !empty($row['nbrEpisode'])) {
                echo "<p class='episodes'><strong>Épisodes:</strong> " . $row['nbrEpisode'] . "</p>";
            }
            
            echo "</div>";
            
            // Colonne 3: Description
            echo "<div class='film-description'>";
            echo "<p class='description'><strong>Description:</strong> " . $row['description'] . "</p>";
            echo "</div>";
            
            // Colonne 4: Note personnelle avec étoiles
            echo "<div class='film-rating'>";
            $notePersonnelle = floatval($row['note']);
            $starsToFill = floor($notePersonnelle); // Nombre d'étoiles à remplir
            
            echo "<div class='note'>";
            echo "<div class='note-stars'>";
            
            // Génération des 10 étoiles disposées en cercle
            for ($i = 1; $i <= 10; $i++) {
                $starClass = ($i <= $starsToFill) ? 'star filled' : 'star';
                echo "<span class='" . $starClass . "'>★</span>";
            }
            
            echo "</div>";
            echo "<span class='note-value'>" . number_format($notePersonnelle, 1) . "</span>";
            echo "</div>";
            echo "</div>";
            
            // Colonne 5: Vide (pas de patte de loup pour ma-liste.php)
            echo "<div class='film-actions'>";
            echo "</div>";
            
            echo "</div>";
        }

        // Fin de la div contenant les films
        echo "</div>";

        // Afficher les liens de pagination
        $nombreDePages = ceil($totalFilms / $filmsParPage);
        
        if ($nombreDePages > 1) {
            echo "<div class='pagination'>";
            
            // Bouton précédent
            if ($pageCourante > 1) {
                echo "<a href='#' onclick='changerPage(" . ($pageCourante - 1) . ")' class='pagination-btn'>« Précédent</a>";
            }
            
            // Numéros de pages
            $debut = max(1, $pageCourante - 2);
            $fin = min($nombreDePages, $pageCourante + 2);
            
            if ($debut > 1) {
                echo "<a href='#' onclick='changerPage(1)' class='pagination-btn'>1</a>";
                if ($debut > 2) {
                    echo "<span class='pagination-dots'>...</span>";
                }
            }
            
            for ($page = $debut; $page <= $fin; $page++) {
                $activeClass = ($page == $pageCourante) ? 'active' : '';
                echo "<a href='#' onclick='changerPage($page)' class='pagination-btn $activeClass'>$page</a>";
            }
            
            if ($fin < $nombreDePages) {
                if ($fin < $nombreDePages - 1) {
                    echo "<span class='pagination-dots'>...</span>";
                }
                echo "<a href='#' onclick='changerPage($nombreDePages)' class='pagination-btn'>$nombreDePages</a>";
            }
            
            // Bouton suivant
            if ($pageCourante < $nombreDePages) {
                echo "<a href='#' onclick='changerPage(" . ($pageCourante + 1) . ")' class='pagination-btn'>Suivant »</a>";
            }
            
            echo "</div>";
        }
        
        // Afficher les informations de pagination
        $debut = ($pageCourante - 1) * $filmsParPage + 1;
        $fin = min($pageCourante * $filmsParPage, $totalFilms);
        echo "<div class='pagination-info'>";
        echo "Affichage de $debut à $fin sur $totalFilms films de votre liste";
        echo "</div>";
        
    } else {
        echo "<div class='film-container-tab'>";
        echo "<p style='text-align: center; color: var(--text-white); font-size: 1.2rem; margin: 2rem 0;'>Aucun film trouvé dans votre liste personnelle pour la catégorie " . htmlspecialchars($categorie) . "</p>";
        echo "</div>"; 
    }

} catch (PDOException $e) {
    error_log("Erreur PDO dans ma-liste display.php: " . $e->getMessage());
    echo "<div class='error-message'>";
    echo "<h3>Erreur lors de la récupération des films</h3>";
    echo "<p>Une erreur s'est produite. Veuillez réessayer plus tard.</p>";
    echo "</div>";
}
?>