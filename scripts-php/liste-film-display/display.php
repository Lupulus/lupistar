<?php
session_start();
// Connexion à la base de données
include '../co-bdd.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Variables dynamiques
    $categorie = isset($_GET['categorie']) ? $_GET['categorie'] : 'Animation';
    $recherche = isset($_GET['recherche']) ? htmlspecialchars($_GET['recherche']) : '';
    $studios = isset($_GET['studio']) ? array_map('htmlspecialchars', explode(',', $_GET['studio'])) : [];
    $annee = isset($_GET['annee']) ? (int)$_GET['annee'] : '';
    $noteMoyenne = isset($_GET['note']) ? (int)$_GET['note'] : '';
    $statutVu = isset($_GET['statut']) ? $_GET['statut'] : '';
    $pays = isset($_GET['pays']) ? htmlspecialchars($_GET['pays']) : '';
    $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';
    $episodes = isset($_GET['episodes']) ? htmlspecialchars($_GET['episodes']) : '';
    $pageCourante = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $filmsParPage = (int)25;
    $offset = (int)(($pageCourante - 1) * $filmsParPage);

    // Gestion de la liste personnelle - optimisée avec une seule requête
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $filmsDansListe = [];

    if ($user_id !== null) {
        $stmtList = $pdo->prepare("SELECT films_id FROM membres_films_list WHERE membres_id = ?");
        $stmtList->execute([$user_id]);
        $resultList = $stmtList->fetchAll(PDO::FETCH_ASSOC);
        foreach ($resultList as $rowList) {
            $filmsDansListe[] = $rowList['films_id'];
        }
    }

    // Construction de la requête optimisée avec COUNT() OVER() pour éviter une requête séparée
    $sql = "SELECT f.id, f.nom_film, s.nom AS studio, a.nom AS auteur, p.nom AS pays, 
                   f.date_sortie, f.note_moyenne, f.description, f.image_path, f.saison, f.nbrEpisode,
                   COUNT(*) OVER() as total_count";

    // Ajouter la jointure pour le statut vu si nécessaire
    if (!empty($statutVu) && $user_id !== null) {
        $sql .= ", mfl.films_id as dans_liste";
        $sql .= " FROM films f
            LEFT JOIN studios s ON f.studio_id = s.id
            LEFT JOIN auteurs a ON f.auteur_id = a.id
            LEFT JOIN pays p ON f.pays_id = p.id
            LEFT JOIN membres_films_list mfl ON f.id = mfl.films_id AND mfl.membres_id = ?";
        $params = [$user_id, $categorie];
    } else {
        $sql .= " FROM films f
            LEFT JOIN studios s ON f.studio_id = s.id
            LEFT JOIN auteurs a ON f.auteur_id = a.id
            LEFT JOIN pays p ON f.pays_id = p.id";
        $params = [$categorie];
    }

    $sql .= " WHERE f.categorie = ?";

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

    // Filtre par statut vu
    if (!empty($statutVu) && $user_id !== null) {
        if ($statutVu === 'vu') {
            $sql .= " AND mfl.films_id IS NOT NULL";
        } elseif ($statutVu === 'non_vu') {
            $sql .= " AND mfl.films_id IS NULL";
        }
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

    // Ajout de l'ORDER BY après les filtres
    $sql .= " ORDER BY f.date_sortie DESC";

    // Pagination
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $filmsParPage;
    $params[] = $offset;

    // Préparation et exécution de la requête
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalFilms = 0;

    // Affichage des résultats
    if (count($result) > 0) {
        echo "<div class='film-container-tab'>";
        foreach ($result as $row) {
            // Récupérer le total depuis la première ligne
            if ($totalFilms === 0) {
                $totalFilms = $row['total_count'];
            }
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
            
            // Colonne 4: Note avec étoiles
            echo "<div class='film-rating'>";
            $note = floatval($row['note_moyenne']);
            $noteOn10 = $note; // La note est déjà sur 10
            $starsToFill = floor($noteOn10); // Nombre d'étoiles à remplir
            
            echo "<div class='note'>";
            echo "<div class='note-stars'>";
            
            // Génération des 10 étoiles disposées en cercle
            for ($i = 1; $i <= 10; $i++) {
                $starClass = ($i <= $starsToFill) ? 'star filled' : 'star';
                echo "<span class='" . $starClass . "'>★</span>";
            }
            
            echo "</div>";
            echo "<span class='note-value'>" . number_format($noteOn10, 1) . "</span>";
            echo "</div>";
            echo "</div>";
            
            // Colonne 5: Patte de loup (sera positionnée avec CSS)
            
            // Patte de loup
            $class = 'wolf-view';

            if ($user_id !== null && in_array($row['id'], $filmsDansListe)) {
                $class .= ' invert-filter';
            }

            echo "<img src='./img/empreinte-wolf.png' alt='Empreinte de Wolf' class='$class' data-id='" . (int)$row['id'] . "'title='";

            echo "'>";

            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<div class='film-container-tab'>";
        echo "<p style='text-align: center; color: var(--text-white); font-size: 1.2rem; margin: 2rem 0;'>Aucun film trouvé pour la catégorie " . htmlspecialchars($categorie) . "</p>";
        echo "</div>"; 
    }

    // Pagination optimisée - utilise le total déjà calculé
    $nombreDePages = ceil($totalFilms / $filmsParPage);

    if ($nombreDePages > 1) {
        echo "<div class='pagination'>";
        for ($page = 1; $page <= $nombreDePages; $page++) {
            $activeClass = ($page == $pageCourante) ? 'active' : '';
            echo "<a class='$activeClass' href='liste.php?page=$page'>$page</a>";
        }
        echo "</div>";
    }

} catch (PDOException $e) {
    echo "<div class='film-container-tab'>";
    echo "<p style='text-align: center; color: var(--text-white); font-size: 1.2rem; margin: 2rem 0;'>Erreur lors de la récupération des films : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>