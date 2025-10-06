<?php
session_start();
include './scripts-php/co-bdd.php';

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

if (!$isLoggedIn) {
    header("Location: ./login.php");
    exit;
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM membres WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement des formulaires
$message = '';
$messageType = '';

// Modification de l'email
if (isset($_POST['update_email'])) {
    $new_email = $_POST['new_email'];
    
    // Vérifier si l'email existe déjà
    $check_email = "SELECT id FROM membres WHERE email = ? AND id != ?";
    $stmt_check = $pdo->prepare($check_email);
    $stmt_check->execute([$new_email, $user_id]);
    $result_check = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if ($result_check) {
        $message = "Cette adresse e-mail est déjà utilisée.";
        $messageType = "error";
    } else {
        $update_email = "UPDATE membres SET email = ? WHERE id = ?";
        $stmt_update = $pdo->prepare($update_email);
        
        if ($stmt_update->execute([$new_email, $user_id])) {
            $message = "Adresse e-mail mise à jour avec succès.";
            $messageType = "success";
            $user['email'] = $new_email;
        } else {
            $message = "Erreur lors de la mise à jour de l'e-mail.";
            $messageType = "error";
        }
    }
}

// Modification du mot de passe
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    try {
        // Vérifier le mot de passe actuel
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password = "UPDATE membres SET password = ? WHERE id = ?";
                $stmt_update = $pdo->prepare($update_password);
                
                if ($stmt_update->execute([$hashed_password, $user_id])) {
                    // Ajouter une notification pour le changement de mot de passe
                    $notification_message = "Votre mot de passe a été modifié avec succès.";
                    $notification_sql = "INSERT INTO notifications (user_id, type, titre, message, date_creation) VALUES (?, 'password_change', 'Mot de passe modifié', ?, NOW())";
                    $stmt_notification = $pdo->prepare($notification_sql);
                    $stmt_notification->execute([$user_id, $notification_message]);
                    
                    $message = "Mot de passe mis à jour avec succès.";
                    $messageType = "success";
                } else {
                    $message = "Erreur lors de la mise à jour du mot de passe.";
                    $messageType = "error";
                }
            } else {
                $message = "Les nouveaux mots de passe ne correspondent pas.";
                $messageType = "error";
            }
        } else {
            $message = "Mot de passe actuel incorrect.";
            $messageType = "error";
        }
    } catch (PDOException $e) {
        error_log("Erreur PDO lors de la modification du mot de passe: " . $e->getMessage());
        $message = "Erreur lors de la mise à jour du mot de passe.";
        $messageType = "error";
    }
}

// Upload de photo de profil
if (isset($_POST['upload_photo']) && isset($_FILES['profile_photo'])) {
    $target_dir = "img/img-profile/";
    
    // Créer le dossier s'il n'existe pas avec des permissions appropriées
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            $message = "Erreur lors de la création du dossier de destination.";
            $messageType = "error";
        }
    }
    
    // Vérifier que le dossier est accessible en écriture
    if (!is_writable($target_dir)) {
        $message = "Le dossier de destination n'est pas accessible en écriture.";
        $messageType = "error";
    } else {
        $file_extension = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
        $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Vérifier le type de fichier
         $allowed_types = array("jpg", "jpeg", "png", "gif");
         if (in_array($file_extension, $allowed_types)) {
             if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
                 // Supprimer l'ancienne photo si ce n'est pas la photo par défaut
                 if ($user['photo_profil'] && $user['photo_profil'] !== 'img/img-profile/profil.png' && $user['photo_profil'] !== 'img/profil.png') {
                     if (file_exists($user['photo_profil'])) {
                         unlink($user['photo_profil']);
                     }
                 }
                 
                 // Mettre à jour la base de données
                 $update_photo = "UPDATE membres SET photo_profil = ? WHERE id = ?";
                 $stmt_update = $pdo->prepare($update_photo);
                 
                 if ($stmt_update->execute([$target_file, $user_id])) {
                     $message = "Photo de profil mise à jour avec succès.";
                     $messageType = "success";
                     $user['photo_profil'] = $target_file;
                 } else {
                     $message = "Erreur lors de la mise à jour de la photo.";
                     $messageType = "error";
                 }
             } else {
                 $message = "Erreur lors de l'upload de la photo.";
                 $messageType = "error";
             }
         } else {
             $message = "Seuls les fichiers JPG, JPEG, PNG et GIF sont autorisés.";
             $messageType = "error";
         }
     }
}

// Récupérer les statistiques de l'utilisateur
try {
    // Films par catégorie
    $stats_categories = [];
    $sql_categories = "SELECT f.categorie, COUNT(*) as count 
                       FROM membres_films_list mfl 
                       JOIN films f ON mfl.films_id = f.id 
                       WHERE mfl.membres_id = ? 
                       GROUP BY f.categorie";
    $stmt_categories = $pdo->prepare($sql_categories);
    $stmt_categories->execute([$user_id]);
    while ($row = $stmt_categories->fetch(PDO::FETCH_ASSOC)) {
        $stats_categories[$row['categorie']] = $row['count'];
    }

    // Total des films
    $total_films = array_sum($stats_categories);

    // Meilleur auteur
    $sql_best_author = "SELECT a.nom, COUNT(*) as count 
                        FROM membres_films_list mfl 
                        JOIN films f ON mfl.films_id = f.id 
                        JOIN auteurs a ON f.auteur_id = a.id 
                        WHERE mfl.membres_id = ? 
                        GROUP BY a.id 
                        ORDER BY count DESC 
                        LIMIT 1";
    $stmt_best_author = $pdo->prepare($sql_best_author);
    $stmt_best_author->execute([$user_id]);
    $best_author = $stmt_best_author->fetch(PDO::FETCH_ASSOC);

    // Note moyenne donnée
    $sql_avg_rating = "SELECT AVG(note) as avg_rating FROM membres_films_list WHERE membres_id = ?";
    $stmt_avg_rating = $pdo->prepare($sql_avg_rating);
    $stmt_avg_rating->execute([$user_id]);
    $avg_rating = $stmt_avg_rating->fetch(PDO::FETCH_ASSOC);

    // Films proposés approuvés
    $sql_approved_films = "SELECT COUNT(*) as approved_count FROM films_temp WHERE statut = 'approuve' AND propose_par = ?";
    $stmt_approved_films = $pdo->prepare($sql_approved_films);
    $stmt_approved_films->execute([$user_id]);
    $approved_films = $stmt_approved_films->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération des statistiques: " . $e->getMessage());
    // Initialiser les variables avec des valeurs par défaut
    $stats_categories = [];
    $total_films = 0;
    $best_author = null;
    $avg_rating = ['avg_rating' => 0];
    $approved_films = ['approved_count' => 0];
}

// Définir la page actuelle pour marquer l'onglet actif
$current_page = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="./css/style-navigation.css">
    <link rel="stylesheet" href="./css/style-account.css">
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico">
    <title>Mon Compte - Wolf Film</title>
</head>

<body>
    <div class="background"></div>
    <header>
        <nav class="navbar">
            <img src="./gif/logogif.GIF" alt="" class="gif">
            <ul class="menu">
            <a class="btn <?php if ($current_page == 'index') echo 'active'; ?>" id="btn1" href="./index.php">Accueil</a>
            <a class="btn <?php if ($current_page == 'liste') echo 'active'; ?>" id="btn2" href="./liste.php">Liste</a>
            <a class="btn <?php if ($current_page == 'ma-liste') echo 'active'; ?>" id="btn3" href="./ma-liste.php">Ma Liste</a>
            <a class="btn <?php if ($current_page == 'forum') echo 'active'; ?>" id="btn4" href="./forum.php">Forum</a>
            </ul>
            <div class="profil" id="profil">
            <?php 
            $img_id = 'profilImg';
            include './scripts-php/img-profil.php'; 
            ?>
            <div class="menu-deroulant" id="deroulant">
                <!-- Options de menu dans la page menuprofil.php -->
                <?php include './scripts-php/menu-profil.php'; ?>
            </div>
        </div>
        </nav>
    </header>

    <main class="account-container">
        <!-- Colonne 1: Section informations du compte -->
        <div class="account-info">
            <h2>Informations du compte</h2>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Photo de profil -->
            <div class="profile-photo-section">
                <img src="<?php echo htmlspecialchars($user['photo_profil'] ?? 'img/img-profile/profil.png'); ?>" 
                     alt="Photo de profil" class="profile-photo">
                <form method="POST" enctype="multipart/form-data" style="display: inline;">
                    <input type="file" name="profile_photo" accept="image/*" style="display: none;" id="photo-input">
                    <button type="button" class="photo-upload-btn" onclick="document.getElementById('photo-input').click();">
                        Changer la photo
                    </button>
                    <button type="submit" name="upload_photo" style="display: none;" id="upload-btn"></button>
                </form>
            </div>
            
            <!-- Informations de base -->
            <div class="info-group">
                <label>Pseudo</label>
                <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
            </div>
            
            <div class="info-group">
                <label>Titre</label>
                <input type="text" value="<?php echo htmlspecialchars($user['titre']); ?>" disabled>
            </div>
            
            <!-- Modification de l'email -->
            <form method="POST">
                <div class="info-group">
                    <label for="new_email">Adresse e-mail</label>
                    <input type="email" name="new_email" id="new_email" 
                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                <div class="action-buttons">
                    <button type="submit" name="update_email" class="btn-primary">Mettre à jour l'e-mail</button>
                </div>
            </form>
            
            <!-- Modification du mot de passe -->
            <form method="POST" style="margin-top: 30px;">
                <h3 style="color: var(--accent-orange); margin-bottom: 20px;">Changer le mot de passe</h3>
                <div class="info-group">
                    <label for="current_password">Mot de passe actuel</label>
                    <input type="password" name="current_password" id="current_password" required>
                </div>
                <div class="info-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>
                <div class="info-group">
                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>
                <div class="action-buttons">
                    <button type="submit" name="update_password" class="btn-primary">Changer le mot de passe</button>
                </div>
            </form>
        </div>
        
        <!-- Colonne 2: Conteneur pour notifications et préférences -->
        <div class="middle-column">
            <!-- Ligne 1: Section notifications -->
            <div class="notifications-section">
                <h2>Notifications</h2>
                <div class="notification-container" id="notification-container">
                    <!-- Les notifications utilisateur apparaîtront ici -->
                    <p class="no-notifications" id="no-notifications">Chargement des notifications...</p>
                </div>
            </div>
            
            <!-- Ligne 2: Section préférences -->
            <div class="preferences-section">
                <h2>Préférences</h2>
                <div class="preference-group">
                    <h3>Ordre d'affichage des catégories</h3>
                    <p class="preference-description">Glissez-déposez les catégories pour personnaliser leur ordre d'affichage sur les pages d'accueil et de liste.</p>
                    <div class="categories-reorder-container">
                        <ul id="categories-sortable" class="categories-list">
                            <!-- Les catégories seront chargées dynamiquement -->
                        </ul>
                        <div class="preference-actions">
                            <button type="button" id="save-categories-order" class="btn-primary">Sauvegarder l'ordre</button>
                            <button type="button" id="reset-categories-order" class="btn-secondary">Réinitialiser</button>
                        </div>
                        <div id="categories-notification" class="categories-notification" style="display: none;">
                            <span id="categories-notification-text"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Colonne 3: Section statistiques -->
        <div class="stats-section">
            <h2>Mes statistiques</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $total_films; ?></span>
                    <span class="stat-label">Films vus</span>
                </div>
                
                <div class="stat-card">
                    <span class="stat-number"><?php echo $best_author ? htmlspecialchars($best_author['nom']) : 'Aucun'; ?></span>
                    <span class="stat-label">Auteur favori</span>
                </div>
                
                <div class="stat-card">
                    <span class="stat-number"><?php echo $avg_rating ? number_format($avg_rating['avg_rating'], 1) : '0'; ?>/10</span>
                    <span class="stat-label">Note moyenne</span>
                </div>
                
                <div class="stat-card">
                    <span class="stat-number"><?php echo $approved_films ? $approved_films['approved_count'] : '0'; ?></span>
                    <span class="stat-label">Films proposés approuvés</span>
                </div>
                
                <div class="stat-card">
                    <span class="stat-number"><?php echo $user['recompenses']; ?></span>
                    <span class="stat-label">Récompenses</span>
                </div>
                
                <div class="stat-card">
                    <span class="stat-number"><?php echo $user['avertissements'] ?? '0'; ?></span>
                    <span class="stat-label">Avertissements</span>
                </div>
            </div>
            
            <!-- Statistiques par catégorie -->
            <div class="category-stats">
                <h3>Films par catégorie</h3>
                <?php if (!empty($stats_categories)): ?>
                    <?php foreach ($stats_categories as $category => $count): ?>
                        <div class="category-item">
                            <span class="category-name"><?php echo htmlspecialchars($category); ?></span>
                            <span class="category-count"><?php echo $count; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="category-item">
                        <span class="category-name">Aucun film dans votre liste</span>
                        <span class="category-count">0</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 lupistar.fr — Tous droits réservés.</p>
        <p>Les illustrations sont la propriété de leurs auteurs et éditeurs respectifs.</p>
        <nav>
            <a href="/mentions-legales.php">Mentions légales</a> | 
            <a href="/confidentialite.php">Politique de confidentialité</a>
        </nav>
    </footer>
    
    <!-- Scripts -->
    <script src="./scripts-js/profile-image-persistence.js" defer></script>
<script src="./scripts-js/background.js" defer></script>
<script src="./scripts-js/image-crop.js" defer></script>
<script src="./scripts-js/notification-badge.js" defer></script>
    <script>
        // Le nouveau système de recadrage d'image gère maintenant tous les uploads
        // L'ancien système de fallback a été supprimé pour éviter les conflits
        console.log('Système de recadrage d\'image activé');
        
        // Charger les notifications au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
            loadCategoriesPreferences();
        });
        
        // Fonction pour charger les préférences des catégories
        function loadCategoriesPreferences() {
            fetch('./scripts-php/user-preferences.php?type=categories_order')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.categories_order) {
                        currentCategoriesOrder = [...data.categories_order]; // Mettre à jour la variable globale
                        const categoriesList = document.getElementById('categories-sortable');
                        categoriesList.innerHTML = '';
                        
                        data.categories_order.forEach((category, index) => {
                            const li = document.createElement('li');
                            li.className = 'category-item';
                            li.draggable = true;
                            li.innerHTML = `
                                <span class="drag-handle">⋮⋮</span>
                                <span class="category-name">${category}</span>
                            `;
                            categoriesList.appendChild(li);
                        });
                        
                        // Initialiser le drag & drop
                        initializeDragAndDrop();
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des préférences:', error);
                });
        }
        
        // Fonction pour initialiser le drag & drop
        function initializeDragAndDrop() {
            const categoriesList = document.getElementById('categories-sortable');
            let draggedElement = null;
            
            categoriesList.addEventListener('dragstart', function(e) {
                if (e.target.classList.contains('category-item')) {
                    draggedElement = e.target;
                    e.target.style.opacity = '0.5';
                }
            });
            
            categoriesList.addEventListener('dragend', function(e) {
                if (e.target.classList.contains('category-item')) {
                    e.target.style.opacity = '';
                    draggedElement = null;
                }
            });
            
            categoriesList.addEventListener('dragover', function(e) {
                e.preventDefault();
            });
            
            categoriesList.addEventListener('drop', function(e) {
                e.preventDefault();
                
                if (draggedElement && e.target.classList.contains('category-item') && e.target !== draggedElement) {
                    const allItems = Array.from(categoriesList.children);
                    const draggedIndex = allItems.indexOf(draggedElement);
                    const targetIndex = allItems.indexOf(e.target);
                    
                    if (draggedIndex < targetIndex) {
                        categoriesList.insertBefore(draggedElement, e.target.nextSibling);
                    } else {
                        categoriesList.insertBefore(draggedElement, e.target);
                    }
                    
                    // Mettre à jour la variable globale avec le nouvel ordre
                    updateCurrentCategoriesOrder();
                }
            });
        }
        
        // Fonction pour mettre à jour la variable globale avec l'ordre actuel du DOM
        function updateCurrentCategoriesOrder() {
            const categoryItems = document.querySelectorAll('#categories-sortable .category-item');
            currentCategoriesOrder = Array.from(categoryItems).map(item => 
                item.querySelector('.category-name').textContent
            );
        }
        
        // Variable globale pour stocker l'ordre actuel des catégories
        let currentCategoriesOrder = [];
        
        // Fonction pour sauvegarder l'ordre des catégories
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('save-categories-order').addEventListener('click', function() {
                // Utiliser la variable globale au lieu de relire le DOM
                if (!currentCategoriesOrder || currentCategoriesOrder.length === 0) {
                    showCategoriesNotification('Aucune catégorie à sauvegarder', 'error');
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'save_categories_order');
                formData.append('categories_order', JSON.stringify(currentCategoriesOrder));
                
                fetch('./scripts-php/user-preferences.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showCategoriesNotification('Ordre des catégories sauvegardé avec succès !', 'success');
                    } else {
                        showCategoriesNotification('Erreur lors de la sauvegarde : ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showCategoriesNotification('Erreur lors de la sauvegarde', 'error');
                });
            });
            
            // Fonction pour réinitialiser l'ordre des catégories
            document.getElementById('reset-categories-order').addEventListener('click', async function() {
                const confirmed = await customConfirm('Êtes-vous sûr de vouloir réinitialiser l\'ordre des catégories ?', 'Confirmation');
                
                if (confirmed) {
                    const defaultOrder = ["Animation", "Anime", "Série d'Animation", "Film", "Série"];
                    
                    const formData = new FormData();
                    formData.append('action', 'save_categories_order');
                    formData.append('categories_order', JSON.stringify(defaultOrder));
                    
                    fetch('./scripts-php/user-preferences.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadCategoriesPreferences(); // Recharger l'affichage
                            customAlert('Ordre des catégories réinitialisé !', 'Succès');
                        } else {
                            customAlert('Erreur lors de la réinitialisation : ' + data.error, 'Erreur');
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        customAlert('Erreur lors de la réinitialisation', 'Erreur');
                    });
                }
            });
        });
        
        // Fonction pour charger les notifications
        function loadNotifications() {
            fetch('./scripts-php/get-notifications.php')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('notification-container');
                    const noNotifications = document.getElementById('no-notifications');
                    
                    if (data.success && data.notifications.length > 0) {
                        noNotifications.style.display = 'none';
                        
                        // Supprimer les anciennes notifications mais garder l'élément no-notifications
                        const existingNotifications = container.querySelectorAll('.notification-item');
                        existingNotifications.forEach(item => item.remove());
                        
                        // Créer et ajouter chaque notification
                        data.notifications.forEach(notification => {
                            const dateFormatted = new Date(notification.date_creation).toLocaleDateString('fr-FR', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            
                            const notificationHTML = `
                                <div class="notification-item ${notification.lu ? 'read' : 'unread'}" data-id="${notification.id}">
                                    <div class="notification-header">
                                        <h4 class="notification-title">${notification.titre}</h4>
                                        <span class="notification-date">${dateFormatted}</span>
                                    </div>
                                    <p class="notification-message">${notification.message}</p>
                                    <div class="notification-actions">
                                        <button class="btn-delete-notification" onclick="deleteNotification(${notification.id})">
                                            Supprimer
                                        </button>
                                    </div>
                                </div>
                            `;
                            
                            // Insérer avant l'élément no-notifications
                            noNotifications.insertAdjacentHTML('beforebegin', notificationHTML);
                        });
                    } else {
                        // Supprimer toutes les notifications existantes
                        const existingNotifications = container.querySelectorAll('.notification-item');
                        existingNotifications.forEach(item => item.remove());
                        
                        noNotifications.style.display = 'block';
                        noNotifications.textContent = 'Aucune notification pour le moment';
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des notifications:', error);
                    const noNotifications = document.getElementById('no-notifications');
                    if (noNotifications) {
                        noNotifications.style.display = 'block';
                        noNotifications.textContent = 'Erreur lors du chargement des notifications';
                    }
                });
        }
        
        // Fonction pour supprimer une notification
        async function deleteNotification(notificationId) {
            // Utiliser la popup personnalisée au lieu de confirm()
            const confirmed = await customConfirm('Êtes-vous sûr de vouloir supprimer cette notification ?', 'Confirmation de suppression');
            
            if (!confirmed) {
                return;
            }
            
            const formData = new FormData();
            formData.append('notification_id', notificationId);
            
            fetch('./scripts-php/delete-notification.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Supprimer l'élément de notification du DOM
                    const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
                    if (notificationElement) {
                        notificationElement.remove();
                    }
                    
                    // Vérifier s'il reste des notifications
                    const remainingNotifications = document.querySelectorAll('.notification-item');
                    const noNotifications = document.getElementById('no-notifications');
                    
                    if (remainingNotifications.length === 0 && noNotifications) {
                        noNotifications.style.display = 'block';
                        noNotifications.textContent = 'Aucune notification pour le moment';
                    }
                    
                    // Mettre à jour le badge de notification
                    if (window.notificationBadgeManager) {
                        window.notificationBadgeManager.updateBadges();
                    }
                } else {
                    // Utiliser la popup personnalisée au lieu d'alert()
                    customAlert('Erreur lors de la suppression de la notification: ' + data.error, 'Erreur');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la suppression:', error);
                // Utiliser la popup personnalisée au lieu d'alert()
                customAlert('Erreur lors de la suppression de la notification', 'Erreur');
            });
        }
        
        // Fonction pour afficher les notifications inline des catégories
        function showCategoriesNotification(message, type) {
            const notification = document.getElementById('categories-notification');
            const notificationText = document.getElementById('categories-notification-text');
            
            // Réinitialiser les classes
            notification.className = 'categories-notification';
            
            // Définir le message et le type
            notificationText.textContent = message;
            notification.classList.add(type);
            
            // Afficher la notification
            notification.style.display = 'block';
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            // Masquer automatiquement après 4 secondes
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 300);
            }, 4000);
        }
    </script>

    <!-- Inclusion du système de popup personnalisé -->
    <?php include './scripts-php/popup.php'; ?>
    <script src="./scripts-js/custom-popup.js"></script>

    <!-- Inclusion du bouton "retour en haut" -->
    <?php include './scripts-php/scroll-to-top.php'; ?>

</body>
</html>