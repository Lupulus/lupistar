<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../scripts-php/co-bdd.php';

// Récupérer l'ID utilisateur s'il est connecté
$user_id = $_SESSION['user_id'] ?? null;

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        // Vérifier si ce film est dans la liste personnelle de l'utilisateur
        $filmDansListe = false;
        if ($user_id !== null) {
            $sql_check = "SELECT COUNT(*) as count FROM membres_films_list WHERE films_id = ? AND membres_id = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$id, $user_id]);
            $result_check = $stmt_check->fetch(PDO::FETCH_ASSOC);
            $filmDansListe = $result_check['count'] > 0;
        }

        // Récupérer les infos du film
        $sql = "SELECT f.id, f.nom_film, f.image_path, f.categorie, f.date_sortie, f.description,
                       s.nom AS studio, p.nom AS pays, a.nom AS auteur
                FROM films f
                LEFT JOIN studios s ON f.studio_id = s.id
                LEFT JOIN pays p ON f.pays_id = p.id
                LEFT JOIN auteurs a ON f.auteur_id = a.id
                WHERE f.id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $film = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($film) {
            // Récupérer les sous-genres du film
            $sql_sous_genres = "SELECT sg.nom FROM sous_genres sg
                                INNER JOIN films_sous_genres fsg ON sg.id = fsg.sous_genre_id
                                WHERE fsg.film_id = ?";
            $stmt_sous_genres = $pdo->prepare($sql_sous_genres);
            $stmt_sous_genres->execute([$id]);

        $sous_genres = [];
        $result_sous_genres = $stmt_sous_genres->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result_sous_genres as $row) {
            $sous_genres[] = $row['nom'];
        }

        // Définir la classe et l'action pour l'icône "favori"
        $class = 'wolf-view';
        $action = 'add';
        if ($filmDansListe) {
            $class .= ' invert-filter';
            $action = 'remove';
        }

        ?>
        <link rel="stylesheet" href="./css/film-modal.css?v=4">
        <div class="modal-content" data-id="<?= (int)$film['id'] ?>">
            <span class="modal-close">&times;</span>
            <div class="modal-header">
                <h2 class="modal-title"><?= htmlspecialchars($film['nom_film']) ?></h2>
            </div>
            <div class="modal-left">
                <img class="modal-image" src="<?= htmlspecialchars($film['image_path']) ?>" alt="<?= htmlspecialchars($film['nom_film']) ?>">

                <div class="note-bar-container">
                    <h3>Répartition des Notes :</h3>
                    <div class="note-bar-chart" id="note-bar-chart">
                        <p>Chargement des notes...</p> <!-- Indicateur de chargement -->
                    </div>
                </div>

                <?php
                    // Récupérer la note de l'utilisateur connecté
                    $user_note = null;
                    if ($user_id !== null) {
                        $sql_user_note = "SELECT note FROM membres_films_list WHERE films_id = ? AND membres_id = ?";
                        $stmt_user_note = $pdo->prepare($sql_user_note);
                        $stmt_user_note->execute([$id, $user_id]);
                        $result_user_note = $stmt_user_note->fetch(PDO::FETCH_ASSOC);
                        if ($result_user_note) {
                            $user_note = $result_user_note['note'];
                        }
                    }
                ?>

                <?php if ($filmDansListe): ?>
                    <!-- Affichage de la note de l'utilisateur -->
                    <div class="user-note-container">
                        <p><strong>Ma note :</strong> 
                            <span id="user-note"><?= $user_note !== null ? htmlspecialchars($user_note) . "/10" : "Non noté" ?></span>
                            <span id="edit-note" class="edit-icon" title="Modifier ma note">✏️</span>
                        </p>
                        <input type="number" id="note-input" min="0" max="10" step="0.25" style="display:none;">
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-right">
                <p id="modal-categorie"><strong>Catégorie :</strong> <?= htmlspecialchars($film['categorie']) ?></p>
                <p id="modal-studio"><strong>Studio :</strong> <?= htmlspecialchars($film['studio'] ?? "Inconnu") ?></p>
                <p id="modal-date"><strong>Date de sortie :</strong> <?= htmlspecialchars($film['date_sortie']) ?></p>
                <p id="modal-pays"><strong>Pays :</strong> <?= htmlspecialchars($film['pays'] ?? "Inconnu") ?></p>
                <p id="modal-auteur"><strong>Auteur :</strong> <?= htmlspecialchars($film['auteur'] ?? "Inconnu") ?></p>
                <p id="modal-description"><strong>Description :</strong> <?= nl2br(htmlspecialchars($film['description'])) ?></p>

                <!-- Icône "favori" pour ajouter/supprimer de la liste -->
                <?php if ($user_id !== null): ?>
                    <img src="./img/empreinte-wolf.png" alt="Favori" 
                        class="<?= $class ?>" 
                        data-id="<?= (int) $film['id'] ?>" 
                        data-nom="<?= htmlspecialchars($film['nom_film']) ?>" 
                        data-action="<?= $action ?>" 
                        title="<?= $action === 'add' ? 'Ajouter à Ma Liste !' : 'Supprimer de Ma Liste' ?>">
                <?php endif; ?>

                <p id="modal-sous-genres-label"><strong>Sous-genres :</strong></p>
                <ul id="modal-sous-genres">
                    <?php if (!empty($sous_genres)) {
                        foreach ($sous_genres as $sg) {
                            echo "<li>" . htmlspecialchars($sg) . "</li>";
                        }
                    } else {
                        echo "<li>Aucun sous-genre</li>";
                    } ?>
                </ul>
            </div>
        </div>
        <?php
    } else {
            echo "<p class='error-message'>Film introuvable</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error-message'>Erreur lors de la récupération du film : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='error-message'>ID manquant</p>";
}
?>