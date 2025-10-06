<?php
session_start(); // Démarrer la session si ce n'est pas déjà fait

// Vérifier si l'utilisateur est connecté et a le titre prérequis
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$isSuperAdmin = isset($_SESSION['titre']) && $_SESSION['titre'] === 'Super-Admin';
$isAdmin = isset($_SESSION['titre']) && $_SESSION['titre'] === 'Admin';

// Rediriger vers la page de connexion si l'utilisateur n'est pas connecté ou n'a pas le titre prérequis
if (!$isLoggedIn) {
    header("Location: ./login.php");
    exit;
}

// Traitement AJAX - doit être fait AVANT tout contenu HTML
include './scripts-php/co-bdd.php';

// Traitement des mises à jour de titre
if(isset($_GET['id']) && isset($_GET['newTitle'])) {
    try {
        $id = intval($_GET['id']);
        $newTitle = $_GET['newTitle'];
        
        // Vérifier les permissions pour les Admin
        if ($isAdmin) {
            // Récupérer le titre actuel de l'utilisateur cible
            $checkSql = "SELECT titre FROM membres WHERE id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->rowCount() > 0) {
                $targetUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
                $currentTitle = $targetUser['titre'];
                
                // Empêcher les Admin de modifier les titres Super-Admin et Admin
                if ($currentTitle === 'Super-Admin' || $currentTitle === 'Admin') {
                    echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les permissions pour modifier ce titre']);
                    exit;
                }
                
                // Empêcher les Admin de donner les titres Super-Admin et Admin
                if ($newTitle === 'Super-Admin' || $newTitle === 'Admin') {
                    echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les permissions pour attribuer ce titre']);
                    exit;
                }
            }
        }
        
        $validTitles = ['Membre', 'Amateur', 'Fan', 'NoLife', 'Admin', 'Super-Admin'];
        if (!in_array($newTitle, $validTitles)) {
            echo json_encode(['success' => false, 'message' => 'Titre invalide']);
            exit;
        }
        
        $updateSql = "UPDATE membres SET titre = ? WHERE id = ?";
        $stmt = $pdo->prepare($updateSql);
        
        if ($stmt->execute([$newTitle, $id])) {
            // Ajouter une notification pour le changement de titre par admin
            $notification_message = "Votre titre a été modifié par un administrateur : " . $newTitle;
            $notification_sql = "INSERT INTO notifications (user_id, type, titre, message, date_creation) VALUES (?, 'title_change_admin', 'Titre modifié', ?, NOW())";
            $stmt_notification = $pdo->prepare($notification_sql);
            $stmt_notification->execute([$id, $notification_message]);
            
            echo json_encode(['success' => true, 'newValue' => $newTitle, 'message' => 'Titre mis à jour avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du titre']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    }
    exit;
}

// Traitement des mises à jour de restriction
if(isset($_GET['id']) && isset($_GET['newRestriction'])) {
    try {
        $id = intval($_GET['id']);
        $newRestriction = $_GET['newRestriction'];
        
        // Vérifier les permissions pour les Admin
        if ($isAdmin) {
            // Récupérer le titre actuel de l'utilisateur cible
            $checkSql = "SELECT titre FROM membres WHERE id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->rowCount() > 0) {
                $targetUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
                $currentTitle = $targetUser['titre'];
                
                // Empêcher les Admin de modifier les restrictions des Super-Admin et Admin
                if ($currentTitle === 'Super-Admin' || $currentTitle === 'Admin') {
                    echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les permissions pour modifier cette restriction']);
                    exit;
                }
            }
        }
        
        $validRestrictions = ['Aucune', 'Salon Général', 'Salon Anime', 'Salon Films', 'Salon Séries', 'Modération Complète'];
        if (!in_array($newRestriction, $validRestrictions)) {
            echo json_encode(['success' => false, 'message' => 'Restriction invalide']);
            exit;
        }
        
        $updateSql = "UPDATE membres SET restriction = ? WHERE id = ?";
        $stmt = $pdo->prepare($updateSql);
        
        if ($stmt->execute([$newRestriction, $id])) {
            echo json_encode(['success' => true, 'newValue' => $newRestriction, 'message' => 'Restriction mise à jour avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la restriction']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    }
    exit;
}

// Traitement des mises à jour d'email
if(isset($_GET['id']) && isset($_GET['newEmail'])) {
    try {
        $id = intval($_GET['id']);
        $newEmail = $_GET['newEmail'];
        
        // Vérifier les permissions pour les Admin
        if ($isAdmin) {
            // Récupérer le titre actuel de l'utilisateur cible
            $checkSql = "SELECT titre FROM membres WHERE id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->rowCount() > 0) {
                $targetUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
                $currentTitle = $targetUser['titre'];
                
                // Empêcher les Admin de modifier les emails des Super-Admin et Admin
                if ($currentTitle === 'Super-Admin' || $currentTitle === 'Admin') {
                    echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les permissions pour modifier cet email']);
                    exit;
                }
            }
        }
        
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Format d\'email invalide']);
            exit;
        }
        
        $updateSql = "UPDATE membres SET email = ? WHERE id = ?";
        $stmt = $pdo->prepare($updateSql);
        
        if ($stmt->execute([$newEmail, $id])) {
            // Ajouter une notification pour le changement d'email par admin
            $notification_message = "Votre adresse email a été modifiée par un administrateur.";
            $notification_sql = "INSERT INTO notifications (user_id, type, titre, message, date_creation) VALUES (?, 'email_change_admin', 'Email modifié', ?, NOW())";
            $stmt_notification = $pdo->prepare($notification_sql);
            $stmt_notification->execute([$id, $notification_message]);
            
            echo json_encode(['success' => true, 'newValue' => $newEmail, 'message' => 'Email mis à jour avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'email']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    }
    exit;
}

// Traitement des mises à jour de pseudo
if(isset($_GET['id']) && isset($_GET['newUsername'])) {
    try {
        $id = intval($_GET['id']);
        $newUsername = $_GET['newUsername'];
        
        // Vérifier les permissions pour les Admin
        if ($isAdmin) {
            // Récupérer le titre actuel de l'utilisateur cible
            $checkSql = "SELECT titre FROM membres WHERE id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->rowCount() > 0) {
                $targetUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
                $currentTitle = $targetUser['titre'];
                
                // Empêcher les Admin de modifier les pseudos des Super-Admin et Admin
                if ($currentTitle === 'Super-Admin' || $currentTitle === 'Admin') {
                    echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les permissions pour modifier ce pseudo']);
                    exit;
                }
            }
        }
        
        // Vérifier que le pseudo n'est pas vide et n'est pas déjà utilisé
        if (empty(trim($newUsername))) {
            echo json_encode(['success' => false, 'message' => 'Le pseudo ne peut pas être vide']);
            exit;
        }
        
        // Vérifier l'unicité du pseudo
        $checkUsernameSql = "SELECT id FROM membres WHERE username = ? AND id != ?";
        $checkUsernameStmt = $pdo->prepare($checkUsernameSql);
        $checkUsernameStmt->execute([$newUsername, $id]);
        
        if ($checkUsernameStmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Ce pseudo est déjà utilisé']);
            exit;
        }
        
        $updateSql = "UPDATE membres SET username = ? WHERE id = ?";
        $stmt = $pdo->prepare($updateSql);
        
        if ($stmt->execute([$newUsername, $id])) {
            // Ajouter une notification pour le changement de pseudo par admin
            $notification_message = "Votre pseudo a été modifié par un administrateur : " . $newUsername;
            $notification_sql = "INSERT INTO notifications (user_id, type, titre, message, date_creation) VALUES (?, 'username_change_admin', 'Pseudo modifié', ?, NOW())";
            $stmt_notification = $pdo->prepare($notification_sql);
            $stmt_notification->execute([$id, $notification_message]);
            
            echo json_encode(['success' => true, 'newValue' => $newUsername, 'message' => 'Pseudo mis à jour avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du pseudo']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    }
    exit;
}

// Traitement des avertissements et récompenses
if(isset($_GET['id']) && isset($_GET['type']) && isset($_GET['increment'])) {
    try {
        $id = intval($_GET['id']);
        $type = $_GET['type'];
        $increment = intval($_GET['increment']);
        
        if ($type !== 'avertissements' && $type !== 'recompenses') {
            echo json_encode(['success' => false, 'message' => 'Type invalide']);
            exit;
        }
        
        // Vérifier les permissions pour les Admin
        if ($isAdmin) {
            // Récupérer le titre actuel de l'utilisateur cible
            $checkSql = "SELECT titre FROM membres WHERE id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->rowCount() > 0) {
                $targetUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
                $currentTitle = $targetUser['titre'];
                
                // Empêcher les Admin de modifier les avertissements/récompenses des Super-Admin et Admin
                if ($currentTitle === 'Super-Admin' || $currentTitle === 'Admin') {
                    echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les permissions pour modifier les ' . $type . ' de cet utilisateur']);
                    exit;
                }
            }
        }
        
        $selectSql = "SELECT $type FROM membres WHERE id = ?";
        $stmt = $pdo->prepare($selectSql);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $currentValue = intval($row[$type]);
            $newValue = max(0, $currentValue + $increment);
            
            $updateSql = "UPDATE membres SET $type = ? WHERE id = ?";
            $updateStmt = $pdo->prepare($updateSql);
            
            if ($updateStmt->execute([$newValue, $id])) {
                echo json_encode(['success' => true, 'newValue' => $newValue]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Membre non trouvé']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    }
    exit;
}

// Définir la page actuelle pour marquer l'onglet actif
$current_page = basename($_SERVER['PHP_SELF'], ".php");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style-navigation.css">
    <link rel="stylesheet" href="./css/style-admin.css">
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico">
    <title>Membres</title>
</head>
<body>
<div class="background"></div>
<header>
    <nav class="navbar">
        <img src="./gif/logogif.GIF" alt="" class="gif">
        <ul class="menu">
            <a class="btn <?php if ($current_page == 'index') echo 'active'; ?>" id="btn1" href="./index.php">Accueil</a>
            <a class="btn <?php if ($current_page == 'administration') echo 'active'; ?>" id="btn2" href="./administration.php">Administration</a>
            <a class="btn <?php if ($current_page == 'membres') echo 'active'; ?>" id="btn3" href="./membres.php">Membres</a>
            <a class="btn <?php echo $isAdmin ? 'disabled' : ''; ?>" href="<?php echo $isAdmin ? '#' : '/bddadmin'; ?>" <?php echo $isAdmin ? '' : 'target="_blank" rel="noopener noreferrer"'; ?>>Base de données</a>
        </ul>
        <div class="profil" id="profil">
            <?php 
                        $img_id = 'profilImg';
                        include './scripts-php/img-profil.php'; 
                        ?>
            <div class="menu-deroulant" id="deroulant">
                <?php include './scripts-php/menu-profil.php'; ?>
            </div>
        </div>
    </nav>
</header>
<div class="membre-container">
    <h2>Liste des Membres</h2>
    
    <!-- Barre de recherche -->
    <div class="search-container-membres">
        <input type="text" id="searchMembres" placeholder="Rechercher par pseudo, email, titre ou restriction..." onkeyup="filterMembres()">
    </div>
    
    <?php
    // Connexion à la base de données MySQL
    include './scripts-php/co-bdd.php';

    // Récupérer les membres depuis la base de données
    try {
        $sql = "SELECT id, username, email, titre, restriction, avertissements, recompenses, photo_profil FROM membres";
        $result = $pdo->query($sql);

        if ($result && $result->rowCount() > 0) {
        // Afficher le tableau des membres
        echo "<table class='membres-table' id='membresTable'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Nom d'utilisateur</th>";
        echo "<th>Email</th>";
        echo "<th>Titre actuel</th>";
        echo "<th>Restriction</th>";
        echo "<th>Avertissements <span class='sort-icon' onclick='sortTable(4)' title='Trier par avertissements'>⇅</span></th>";
        echo "<th>Récompenses <span class='sort-icon' onclick='sortTable(5)' title='Trier par récompenses'>⇅</span></th>";
        echo "<th>Actions</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            // Vérifier si l'utilisateur actuel peut modifier ce membre
            $canModify = !$isAdmin || ($row['titre'] !== 'Super-Admin' && $row['titre'] !== 'Admin');
            
            echo "<tr data-id='" . htmlspecialchars($row['id']) . "'>";
            echo "<td class='membre-username'>";
            // Afficher la photo de profil à gauche du nom d'utilisateur
            $photo_profil = $row['photo_profil'] ?? 'img/img-profile/profil.png';
            echo "<img src='" . htmlspecialchars($photo_profil) . "' alt='Photo de profil' class='membre-photo-profil'>";
            echo "<span class='membre-nom'>" . htmlspecialchars($row['username']) . "</span>";
            echo "</td>";
            echo "<td class='membre-email'>" . htmlspecialchars($row['email'] ?? 'Non renseigné') . "</td>";
            echo "<td><span class='membre-titre titre-" . strtolower(str_replace('-', '-', $row['titre'])) . "' id='titre-" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['titre']) . "</span></td>";
            echo "<td><span class='membre-restriction restriction-" . strtolower(str_replace(' ', '-', $row['restriction'] ?? 'aucune')) . "' id='restriction-" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['restriction'] ?? 'Aucune') . "</span></td>";
            // Colonne Avertissements avec boutons intégrés
            echo "<td class='membre-avertissements'>";
            echo "<div class='warning-reward-inline'>";
            if ($canModify) {
                echo "<button class='control-btn minus-btn' onclick='updateWarningReward(" . htmlspecialchars($row['id']) . ", \"avertissements\", -1)'>-</button>";
            } else {
                echo "<button class='control-btn minus-btn disabled' disabled title='Permissions insuffisantes'>-</button>";
            }
            echo "<span id='avertissements-" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['avertissements'] ?? '0') . "</span>";
            if ($canModify) {
                echo "<button class='control-btn plus-btn' onclick='updateWarningReward(" . htmlspecialchars($row['id']) . ", \"avertissements\", 1)'>+</button>";
            } else {
                echo "<button class='control-btn plus-btn disabled' disabled title='Permissions insuffisantes'>+</button>";
            }
            echo "</div>";
            echo "</td>";
            
            // Colonne Récompenses avec boutons intégrés
            echo "<td class='membre-recompenses'>";
            echo "<div class='warning-reward-inline'>";
            if ($canModify) {
                echo "<button class='control-btn minus-btn' onclick='updateWarningReward(" . htmlspecialchars($row['id']) . ", \"recompenses\", -1)'>-</button>";
            } else {
                echo "<button class='control-btn minus-btn disabled' disabled title='Permissions insuffisantes'>-</button>";
            }
            echo "<span id='recompenses-" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['recompenses'] ?? '0') . "</span>";
            if ($canModify) {
                echo "<button class='control-btn plus-btn' onclick='updateWarningReward(" . htmlspecialchars($row['id']) . ", \"recompenses\", 1)'>+</button>";
            } else {
                echo "<button class='control-btn plus-btn disabled' disabled title='Permissions insuffisantes'>+</button>";
            }
            echo "</div>";
            echo "</td>";
            
            echo "<td class='actions-cell'>";
            
            // Menu déroulant pour modifier le titre
            echo "<div class='dropdown action-dropdown'>";
            if ($canModify) {
                echo "<button class='dropbtn' onclick='toggleDropdown(\"dropdown-titre-" . htmlspecialchars($row['id']) . "\")'>Modifier Titre</button>";
                echo "<div class='dropdown-content' id='dropdown-titre-" . htmlspecialchars($row['id']) . "'>";
                if (!$isSuperAdmin || ($isSuperAdmin && $row['titre'] !== 'Super-Admin')) {
                    echo "<a href='#' onclick='confirmUpdateTitle(" . htmlspecialchars($row['id']) . ", \"Membre\")'>Membre</a>";
                    echo "<a href='#' onclick='confirmUpdateTitle(" . htmlspecialchars($row['id']) . ", \"Amateur\")'>Amateur</a>";
                    echo "<a href='#' onclick='confirmUpdateTitle(" . htmlspecialchars($row['id']) . ", \"Fan\")'>Fan</a>";
                    echo "<a href='#' onclick='confirmUpdateTitle(" . htmlspecialchars($row['id']) . ", \"NoLife\")'>NoLife</a>";
                    // Les Admin ne peuvent pas attribuer les titres Admin ou Super-Admin
                    if (!$isAdmin) {
                        echo "<a href='#' onclick='confirmUpdateTitle(" . htmlspecialchars($row['id']) . ", \"Admin\")'>Admin</a>";
                    }
                }
                echo "</div>";
            } else {
                echo "<button class='dropbtn disabled' disabled title='Permissions insuffisantes'>Modifier Titre</button>";
            }
            echo "</div>";
            
            // Menu déroulant pour modifier la restriction
            echo "<div class='dropdown action-dropdown'>";
            if ($canModify) {
                echo "<button class='dropbtn' onclick='toggleDropdown(\"dropdown-restriction-" . htmlspecialchars($row['id']) . "\")'>Modifier Restriction</button>";
                echo "<div class='dropdown-content' id='dropdown-restriction-" . htmlspecialchars($row['id']) . "'>";
                echo "<a href='#' onclick='confirmUpdateRestriction(" . htmlspecialchars($row['id']) . ", \"Aucune\")'>Aucune</a>";
                echo "<a href='#' onclick='confirmUpdateRestriction(" . htmlspecialchars($row['id']) . ", \"Salon Général\")'>Salon Général</a>";
                echo "<a href='#' onclick='confirmUpdateRestriction(" . htmlspecialchars($row['id']) . ", \"Salon Anime\")'>Salon Anime</a>";
                echo "<a href='#' onclick='confirmUpdateRestriction(" . htmlspecialchars($row['id']) . ", \"Salon Films\")'>Salon Films</a>";
                echo "<a href='#' onclick='confirmUpdateRestriction(" . htmlspecialchars($row['id']) . ", \"Salon Séries\")'>Salon Séries</a>";
                echo "<a href='#' onclick='confirmUpdateRestriction(" . htmlspecialchars($row['id']) . ", \"Modération Complète\")'>Modération Complète</a>";
                echo "</div>";
            } else {
                echo "<button class='dropbtn disabled' disabled title='Permissions insuffisantes'>Modifier Restriction</button>";
            }
            echo "</div>";
            
            // Menu déroulant pour modifier l'email
            echo "<div class='dropdown action-dropdown'>";
            if ($canModify) {
                echo "<button class='dropbtn' onclick='showEmailForm(" . htmlspecialchars($row['id']) . ")''>Modifier Email</button>";
            } else {
                echo "<button class='dropbtn disabled' disabled title='Permissions insuffisantes'>Modifier Email</button>";
            }
            echo "</div>";
            
            // Menu déroulant pour modifier le pseudo
            echo "<div class='dropdown action-dropdown'>";
            if ($canModify) {
                echo "<button class='dropbtn' onclick='showUsernameForm(" . htmlspecialchars($row['id']) . ")''>Modifier Pseudo</button>";
            } else {
                echo "<button class='dropbtn disabled' disabled title='Permissions insuffisantes'>Modifier Pseudo</button>";
            }
            echo "</div>";
            
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p style='text-align: center; color: var(--text-medium-gray); font-size: 1.2em; margin: 2rem 0;'>Aucun membre trouvé dans la base de données.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='text-align: center; color: red; font-size: 1.2em; margin: 2rem 0;'>Erreur de base de données : " . htmlspecialchars($e->getMessage()) . "</p>";
}
    ?>
</div>

<footer>
  <p>&copy; 2025 lupistar.fr — Tous droits réservés.</p>
  <p>Les illustrations sont la propriété de leurs auteurs et éditeurs respectifs.</p>
  <nav>
    <a href="/mentions-legales.php">Mentions légales</a> | 
    <a href="/confidentialite.php">Politique de confidentialité</a>
  </nav>   
</footer>

<script>
    function toggleDropdown(id) {
        // Fermer tous les autres dropdowns et retirer la classe active
        var allDropdowns = document.querySelectorAll('.dropdown-content');
        var allActionDropdowns = document.querySelectorAll('.action-dropdown');
        
        allDropdowns.forEach(function(dropdown) {
            if (dropdown.id !== id) {
                dropdown.classList.remove("show");
            }
        });
        
        allActionDropdowns.forEach(function(actionDropdown) {
            actionDropdown.classList.remove("active");
        });
        
        // Toggle le dropdown actuel
        var dropdown = document.getElementById(id);
        var parentActionDropdown = dropdown.closest('.action-dropdown');
        
        if (dropdown.classList.contains("show")) {
            dropdown.classList.remove("show");
            if (parentActionDropdown) {
                parentActionDropdown.classList.remove("active");
            }
        } else {
            // Calculer la position du bouton pour positionner le dropdown
            var button = parentActionDropdown.querySelector('.dropbtn');
            var rect = button.getBoundingClientRect();
            
            dropdown.style.left = (rect.right - 160) + 'px'; // 160px = min-width du dropdown
            dropdown.style.top = (rect.bottom + 5) + 'px';
            
            dropdown.classList.add("show");
            if (parentActionDropdown) {
                parentActionDropdown.classList.add("active");
            }
        }
    }

    async function confirmUpdateTitle(id, newTitle) {
        const confirmed = await customConfirm("Êtes-vous sûr de vouloir modifier le titre de ce membre?", "Confirmation de modification");
        if (confirmed) {
            updateTitle(id, newTitle);
        }
    }

    async function confirmUpdateRestriction(id, newRestriction) {
        const confirmed = await customConfirm("Êtes-vous sûr de vouloir modifier la restriction de ce membre?", "Confirmation de modification");
        if (confirmed) {
            updateRestriction(id, newRestriction);
        }
    }

    function showEmailForm(id) {
        var currentEmail = document.getElementById("email-" + id) ? document.getElementById("email-" + id).textContent : '';
        var newEmail = prompt("Entrez la nouvelle adresse email:", currentEmail);
        if (newEmail !== null && newEmail.trim() !== '') {
            if (validateEmail(newEmail)) {
                updateEmail(id, newEmail);
            } else {
                customAlert("Veuillez entrer une adresse email valide.", "Email invalide");
            }
        }
    }

    function showUsernameForm(id) {
        var currentUsername = document.querySelector("tr[data-id='" + id + "'] .membre-username").textContent;
        var newUsername = prompt("Entrez le nouveau pseudo:", currentUsername);
        if (newUsername !== null && newUsername.trim() !== '') {
            updateUsername(id, newUsername);
        }
    }

    function validateEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function updateTitle(id, newTitle) {
        // Fonction JavaScript pour mettre à jour le titre du membre via AJAX
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                try {
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Mettre à jour l'affichage du titre dans la page
                        var titreElement = document.getElementById("titre-" + id);
                        if (titreElement) {
                            titreElement.innerHTML = response.newValue;
                            // Mettre à jour la classe CSS pour le style du titre
                            titreElement.className = "membre-titre titre-" + response.newValue.toLowerCase().replace('-', '-');
                        }
                        // Afficher un message de succès
                        showNotification(response.message, 'success');
                    } else {
                        showNotification("Erreur: " + response.message, 'error');
                    }
                } catch (e) {
                    console.error("Erreur lors du parsing de la réponse:", e);
                    showNotification("Erreur de communication avec le serveur", 'error');
                }
            }
        };
        // Envoyer une requête GET avec les paramètres ID et newTitle à cette même page
        xhttp.open("GET", "membres.php?id=" + id + "&newTitle=" + encodeURIComponent(newTitle), true);
        xhttp.send();
    }

    function updateRestriction(id, newRestriction) {
        // Fonction JavaScript pour mettre à jour la restriction du membre via AJAX
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                try {
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Mettre à jour l'affichage de la restriction dans la page
                        var restrictionElement = document.getElementById("restriction-" + id);
                        if (restrictionElement) {
                            restrictionElement.innerHTML = response.newValue;
                            // Mettre à jour la classe CSS pour le style de la restriction
                            restrictionElement.className = "membre-restriction restriction-" + response.newValue.toLowerCase().replace(/\s+/g, '-');
                        }
                        // Afficher un message de succès
                        showNotification(response.message, 'success');
                    } else {
                        showNotification("Erreur: " + response.message, 'error');
                    }
                } catch (e) {
                    console.error("Erreur lors du parsing de la réponse:", e);
                    showNotification("Erreur de communication avec le serveur", 'error');
                }
            }
        };
        xhttp.open("GET", "membres.php?id=" + id + "&newRestriction=" + encodeURIComponent(newRestriction), true);
        xhttp.send();
    }

    function updateEmail(id, newEmail) {
        // Fonction JavaScript pour mettre à jour l'email du membre via AJAX
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                try {
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Mettre à jour l'affichage de l'email dans la page
                        var emailElement = document.getElementById("email-" + id);
                        if (emailElement) {
                            emailElement.innerHTML = response.newValue;
                        }
                        // Afficher un message de succès
                        showNotification(response.message, 'success');
                    } else {
                        showNotification("Erreur: " + response.message, 'error');
                    }
                } catch (e) {
                    console.error("Erreur lors du parsing de la réponse:", e);
                    showNotification("Erreur de communication avec le serveur", 'error');
                }
            }
        };
        xhttp.open("GET", "membres.php?id=" + id + "&newEmail=" + encodeURIComponent(newEmail), true);
        xhttp.send();
    }

    function updateUsername(id, newUsername) {
        // Fonction JavaScript pour mettre à jour le pseudo du membre via AJAX
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                try {
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Mettre à jour l'affichage du pseudo dans la page
                        var usernameElement = document.querySelector("tr[data-id='" + id + "'] .membre-username");
                        if (usernameElement) {
                            usernameElement.innerHTML = response.newValue;
                        }
                        // Afficher un message de succès
                        showNotification(response.message, 'success');
                    } else {
                        showNotification("Erreur: " + response.message, 'error');
                    }
                } catch (e) {
                    console.error("Erreur lors du parsing de la réponse:", e);
                    showNotification("Erreur de communication avec le serveur", 'error');
                }
            }
        };
        xhttp.open("GET", "membres.php?id=" + id + "&newUsername=" + encodeURIComponent(newUsername), true);
        xhttp.send();
    }

    function showNotification(message, type) {
        // Créer un élément de notification
        var notification = document.createElement('div');
        notification.className = 'notification notification-' + type;
        notification.textContent = message;
        
        // Ajouter la notification au body
        document.body.appendChild(notification);
        
        // Afficher la notification avec une animation
        setTimeout(function() {
            notification.classList.add('show');
        }, 100);
        
        // Supprimer la notification après 3 secondes
        setTimeout(function() {
            notification.classList.remove('show');
            setTimeout(function() {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    function updateWarningReward(id, type, increment) {
        // Fonction JavaScript pour mettre à jour les avertissements ou récompenses via AJAX
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4) {
                if (this.status == 200) {
                    console.log("Réponse brute du serveur:", this.responseText);
                    try {
                        var response = JSON.parse(this.responseText);
                        if (response.success) {
                            // Mettre à jour l'affichage dans la page
                            var element = document.getElementById(type + "-" + id);
                            if (element) {
                                element.textContent = response.newValue;
                            }
                            showNotification("Mise à jour réussie", 'success');
                        } else {
                            showNotification("Erreur: " + response.message, 'error');
                        }
                    } catch (e) {
                        console.error("Erreur lors du parsing de la réponse:", e);
                        console.error("Réponse reçue:", this.responseText);
                        showNotification("Erreur de communication avec le serveur. Vérifiez la console pour plus de détails.", 'error');
                    }
                } else {
                    console.error("Erreur HTTP:", this.status, this.statusText);
                    showNotification("Erreur de connexion au serveur (Code: " + this.status + ")", 'error');
                }
            }
        };
        xhttp.open("GET", "membres.php?id=" + id + "&type=" + encodeURIComponent(type) + "&increment=" + increment, true);
        xhttp.send();
    }

    // Fonction de recherche dynamique
    function filterMembres() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("searchMembres");
        filter = input.value.toUpperCase();
        table = document.getElementById("membresTable");
        tr = table.getElementsByTagName("tr");

        // Parcourir toutes les lignes du tableau (sauf l'en-tête)
        for (i = 1; i < tr.length; i++) {
            tr[i].style.display = "none";
            td = tr[i].getElementsByTagName("td");
            
            // Vérifier dans les colonnes : pseudo (0), email (1), titre (2), restriction (3)
            for (var j = 0; j < 4; j++) {
                if (td[j]) {
                    txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                        break;
                    }
                }
            }
        }
    }

    // Variable pour suivre l'ordre de tri
    var sortOrder = {};

    // Fonction de tri des colonnes
    function sortTable(columnIndex) {
        var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
        table = document.getElementById("membresTable");
        switching = true;
        
        // Déterminer la direction du tri
        if (!sortOrder[columnIndex]) {
            sortOrder[columnIndex] = "asc";
        } else {
            sortOrder[columnIndex] = sortOrder[columnIndex] === "asc" ? "desc" : "asc";
        }
        dir = sortOrder[columnIndex];

        while (switching) {
            switching = false;
            rows = table.rows;

            for (i = 1; i < (rows.length - 1); i++) {
                shouldSwitch = false;
                x = rows[i].getElementsByTagName("TD")[columnIndex];
                y = rows[i + 1].getElementsByTagName("TD")[columnIndex];

                // Extraire les valeurs numériques pour les colonnes avertissements et récompenses
                var xValue = parseInt(x.textContent || x.innerText) || 0;
                var yValue = parseInt(y.textContent || y.innerText) || 0;

                if (dir == "asc") {
                    if (xValue > yValue) {
                        shouldSwitch = true;
                        break;
                    }
                } else if (dir == "desc") {
                    if (xValue < yValue) {
                        shouldSwitch = true;
                        break;
                    }
                }
            }
            if (shouldSwitch) {
                rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                switching = true;
                switchcount++;
            } else {
                if (switchcount == 0 && dir == "asc") {
                    dir = "desc";
                    switching = true;
                }
            }
        }

        // Mettre à jour l'icône de tri
        updateSortIcon(columnIndex, dir);
    }

    // Fonction pour mettre à jour l'icône de tri
    function updateSortIcon(columnIndex, direction) {
        var headers = document.querySelectorAll('.sort-icon');
        
        // Réinitialiser toutes les icônes
        headers.forEach(function(icon) {
            icon.innerHTML = '⇅';
        });

        // Mettre à jour l'icône de la colonne triée
        var currentIcon = headers[columnIndex - 4]; // -4 car les icônes commencent à la colonne 4
        if (currentIcon) {
            currentIcon.innerHTML = direction === 'asc' ? '↑' : '↓';
        }
    }
</script>
<script src="./scripts-js/profile-image-persistence.js" defer></script>
<script src="./scripts-js/background.js" defer></script>
<script src="./scripts-js/notification-badge.js" defer></script>

<!-- Inclusion du système de popup personnalisé -->
<?php include './scripts-php/popup.php'; ?>
<script src="./scripts-js/custom-popup.js"></script>

<!-- Inclusion du bouton "retour en haut" -->
<?php include './scripts-php/scroll-to-top.php'; ?>
</body>
</html>
