<?php
session_start();
require_once './scripts-php/co-bdd.php';

// Définir la page actuelle pour marquer l'onglet actif
$current_page = basename($_SERVER['PHP_SELF'], ".php");

// Vérifier si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Vérifier si l'utilisateur est administrateur
$is_admin = false;
if ($is_logged_in) {
    try {
        $stmt = $pdo->prepare("SELECT role FROM membres WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        $is_admin = ($user && $user['role'] === 'admin');
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification du rôle admin: " . $e->getMessage());
    }
}

// Récupérer les paramètres de l'URL
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$discussion_id = isset($_GET['discussion']) ? (int)$_GET['discussion'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : 'categories';

// Récupérer les catégories
try {
    $categories_query = "SELECT * FROM forum_categories WHERE active = 1";
    if (!$is_admin) {
        $categories_query .= " AND admin_only = 0";
    }
    $categories_query .= " ORDER BY ordre ASC, nom ASC";
    
    $stmt = $pdo->prepare($categories_query);
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des catégories: " . $e->getMessage());
    $categories = [];
}

// Fonction pour récupérer les discussions d'une catégorie
function getDiscussions($pdo, $category_id, $limit = 20, $offset = 0) {
    try {
        $query = "SELECT d.*, m.username as author_name, 
                         (SELECT COUNT(*) FROM forum_comments WHERE discussion_id = d.id) as comment_count,
                         (SELECT created_at FROM forum_comments WHERE discussion_id = d.id ORDER BY created_at DESC LIMIT 1) as last_comment_date
                  FROM forum_discussions d 
                  LEFT JOIN membres m ON d.author_id = m.id 
                  WHERE d.category_id = ? 
                  ORDER BY d.pinned DESC, d.updated_at DESC 
                  LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$category_id, $limit, $offset]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des discussions: " . $e->getMessage());
        return [];
    }
}

// Fonction pour récupérer une discussion spécifique
function getDiscussion($pdo, $discussion_id) {
    try {
        $query = "SELECT d.*, m.username as author_name, c.nom as category_name
                  FROM forum_discussions d 
                  LEFT JOIN membres m ON d.author_id = m.id 
                  LEFT JOIN forum_categories c ON d.category_id = c.id
                  WHERE d.id = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$discussion_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération de la discussion: " . $e->getMessage());
        return null;
    }
}

// Fonction pour récupérer les commentaires d'une discussion
function getComments($pdo, $discussion_id, $limit = 20, $offset = 0) {
    try {
        $query = "SELECT c.*, m.username as author_name, m.titre as author_role
                  FROM forum_comments c 
                  LEFT JOIN membres m ON c.author_id = m.id 
                  WHERE c.discussion_id = ? 
                  ORDER BY c.created_at ASC 
                  LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$discussion_id, $limit, $offset]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des commentaires: " . $e->getMessage());
        return [];
    }
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    if (isset($_POST['create_discussion']) && $category_id) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        
        if (!empty($title) && !empty($content)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO forum_discussions (category_id, titre, description, author_id, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$category_id, $title, $content, $user_id]);
                
                $new_discussion_id = $pdo->lastInsertId();
                header("Location: forum.php?category=$category_id&discussion=$new_discussion_id");
                exit;
            } catch (PDOException $e) {
                error_log("Erreur lors de la création de la discussion: " . $e->getMessage());
                $error_message = "Erreur lors de la création de la discussion.";
            }
        } else {
            $error_message = "Veuillez remplir tous les champs.";
        }
    }
    
    if (isset($_POST['add_comment']) && $discussion_id) {
        $content = trim($_POST['content']);
        
        if (!empty($content)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO forum_comments (discussion_id, author_id, content, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$discussion_id, $user_id, $content]);
                
                // Mettre à jour la date de dernière activité de la discussion
                $stmt = $pdo->prepare("UPDATE forum_discussions SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$discussion_id]);
                
                // Envoyer une notification au créateur de la discussion (si ce n'est pas lui qui commente)
                $stmt = $pdo->prepare("SELECT author_id FROM forum_discussions WHERE id = ?");
                $stmt->execute([$discussion_id]);
                $discussion_author = $stmt->fetch();
                
                if ($discussion_author && $discussion_author['author_id'] != $user_id) {
                    // Inclure le script de notification
                    include_once './scripts-php/send-notification.php';
                    $notification_message = "Nouveau commentaire dans votre discussion";
                    sendNotification($pdo, $discussion_author['author_id'], $notification_message);
                }
                
                header("Location: forum.php?category=$category_id&discussion=$discussion_id");
                exit;
            } catch (PDOException $e) {
                error_log("Erreur lors de l'ajout du commentaire: " . $e->getMessage());
                $error_message = "Erreur lors de l'ajout du commentaire.";
            }
        } else {
            $error_message = "Veuillez saisir un commentaire.";
        }
    }
}

// Déterminer le contenu à afficher
if ($discussion_id) {
    $current_discussion = getDiscussion($pdo, $discussion_id);
    if ($current_discussion) {
        $comments = getComments($pdo, $discussion_id);
        $action = 'discussion';
    } else {
        $action = 'categories';
    }
} elseif ($category_id) {
    $discussions = getDiscussions($pdo, $category_id);
    $action = 'discussions';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style-navigation.css">
    <link rel="stylesheet" href="./css/style-forum.css">
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico">
    <title>Forum - Lupistar</title>
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
                    <?php include './scripts-php/menu-profil.php'; ?>
                </div>
            </div>
        </nav>
    </header>

    <div class="forum-container">
        <!-- En-tête du forum -->
        <div class="forum-header">
            <h1>🐺 Forum Lupistar</h1>
            <p>Discutez de vos films et séries préférés avec la communauté</p>
        </div>

        <!-- Avertissement de développement -->
        <div id="dev-warning" class="dev-warning-banner">
            <div class="dev-warning-content">
                <div class="dev-warning-icon">⚠️</div>
                <div class="dev-warning-text">
                    <strong>Forum en développement</strong>
                    <p>Le forum est actuellement en cours de développement. Vous pourriez rencontrer des bugs, des problèmes ou des fonctionnalités manquantes. Merci de votre compréhension !</p>
                </div>
                <button class="dev-warning-close" onclick="closeDevelopmentWarning()">&times;</button>
            </div>
        </div>

        <!-- Navigation du forum -->
        <div class="forum-navigation">
            <div class="forum-breadcrumb">
                <a href="forum.php">💬 Forum</a>
                <?php if ($category_id): ?>
                    <?php 
                    $current_category = array_filter($categories, function($cat) use ($category_id) {
                        return $cat['id'] == $category_id;
                    });
                    $current_category = reset($current_category);
                    ?>
                    <span class="separator">›</span>
                    <span><?php echo htmlspecialchars($current_category['nom']); ?></span>
                <?php endif; ?>
                <?php if ($discussion_id && isset($current_discussion)): ?>
                    <span class="separator">›</span>
                    <span><?php echo htmlspecialchars($current_discussion['titre']); ?></span>
                <?php endif; ?>
            </div>
            
            <?php if ($is_admin): ?>
                <div class="admin-controls">
                    <a href="scripts-php/forum-admin.php" class="btn-forum admin-btn">
                        🛡️ Administration
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'categories'): ?>
            <!-- Affichage des catégories -->
            <div class="forum-categories">
                <?php foreach ($categories as $category): ?>
                    <div class="category-section">
                        <div class="category-header">
                            <div class="category-icon">
                                <?php 
                                if ($category['admin_only']) {
                                    echo '🔒';
                                } elseif ($category['nom'] === 'Global') {
                                    echo '🌍';
                                } else {
                                    echo '📁';
                                }
                                ?>
                            </div>
                            <div class="category-info">
                                <h2>
                                    <a href="forum.php?category=<?php echo $category['id']; ?>" style="color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($category['nom']); ?>
                                    </a>
                                </h2>
                                <p><?php echo htmlspecialchars($category['description']); ?></p>
                            </div>
                            <div class="category-stats">
                                <?php
                                try {
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as discussion_count FROM forum_discussions WHERE category_id = ?");
                                    $stmt->execute([$category['id']]);
                                    $stats = $stmt->fetch();
                                    
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as comment_count FROM forum_comments fc JOIN forum_discussions fd ON fc.discussion_id = fd.id WHERE fd.category_id = ?");
                                    $stmt->execute([$category['id']]);
                                    $comment_stats = $stmt->fetch();
                                } catch (PDOException $e) {
                                    $stats = ['discussion_count' => 0];
                                    $comment_stats = ['comment_count' => 0];
                                }
                                ?>
                                <div class="stat-number"><?php echo $stats['discussion_count']; ?></div>
                                <div>discussions</div>
                                <div class="stat-number"><?php echo $comment_stats['comment_count']; ?></div>
                                <div>commentaires</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($action === 'discussions'): ?>
            <!-- Affichage des discussions d'une catégorie -->
            <div class="forum-actions">
                <h2>Discussions - <?php echo htmlspecialchars($current_category['nom']); ?></h2>
                <?php if ($is_logged_in): ?>
                    <button class="btn-forum" onclick="toggleCreateForm()">
                        ➕ Nouvelle discussion
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($is_logged_in): ?>
                <!-- Formulaire de création de discussion -->
                <div class="forum-form" id="create-form" style="display: none;">
                    <h3>Créer une nouvelle discussion</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="title">Titre de la discussion</label>
                            <input type="text" class="form-control" id="title" name="title" required maxlength="255" placeholder="Entrez le titre de votre discussion">
                        </div>
                        <div class="form-group">
                            <label for="content">Contenu</label>
                            <textarea class="form-control" id="content" name="content" required placeholder="Décrivez votre sujet de discussion..."></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="create_discussion" class="btn-forum">Créer la discussion</button>
                            <button type="button" class="btn-forum secondary" onclick="toggleCreateForm()">Annuler</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Liste des discussions -->
            <div class="comments-section">
                <?php if (empty($discussions)): ?>
                    <div class="discussion-item text-center">
                        <p class="text-muted">Aucune discussion dans cette catégorie pour le moment.</p>
                        <?php if (!$is_logged_in): ?>
                            <p><a href="connexion.php" class="btn-forum">Connectez-vous</a> pour créer la première discussion !</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($discussions as $discussion): ?>
                        <div class="discussion-item">
                            <div class="discussion-icon">
                                <?php if ($discussion['pinned']): ?>
                                    📌
                                <?php elseif ($discussion['locked']): ?>
                                    🔒
                                <?php else: ?>
                                    💬
                                <?php endif; ?>
                            </div>
                            <div class="discussion-content">
                                <a href="forum.php?category=<?php echo $category_id; ?>&discussion=<?php echo $discussion['id']; ?>" class="discussion-title">
                                    <?php echo htmlspecialchars($discussion['titre']); ?>
                                </a>
                                <div class="discussion-meta">
                                    <span>Par <?php echo htmlspecialchars($discussion['author_name'] ?: 'Utilisateur supprimé'); ?></span>
                                    <span>•</span>
                                    <span><?php echo date('d/m/Y à H:i', strtotime($discussion['created_at'])); ?></span>
                                    <?php if ($discussion['last_comment_date']): ?>
                                        <span>•</span>
                                        <span>Dernière activité: <?php echo date('d/m/Y à H:i', strtotime($discussion['last_comment_date'])); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="discussion-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $discussion['comment_count']; ?></span>
                                    <span class="stat-label">réponses</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $discussion['views']; ?></span>
                                    <span class="stat-label">vues</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'discussion' && isset($current_discussion)): ?>
            <!-- Affichage d'une discussion spécifique -->
            <div class="forum-actions">
                <h2><?php echo htmlspecialchars($current_discussion['titre']); ?></h2>
                <?php if ($is_admin): ?>
                    <div class="moderation-actions">
                        <button class="btn-moderation">🔒 Verrouiller</button>
                        <button class="btn-moderation">📌 Épingler</button>
                        <button class="btn-moderation">🗑️ Supprimer</button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Contenu de la discussion -->
            <div class="comments-section">
                <div class="comment-item">
                    <div class="comment-header">
                        <div class="comment-author">
                            <div class="author-info">
                                <h4><?php echo htmlspecialchars($current_discussion['author_name'] ?: 'Utilisateur supprimé'); ?></h4>
                                <span class="author-title">Créateur de la discussion</span>
                            </div>
                        </div>
                        <div class="comment-meta">
                            <span><?php echo date('d/m/Y à H:i', strtotime($current_discussion['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="comment-content">
                        <?php echo nl2br(htmlspecialchars($current_discussion['content'])); ?>
                    </div>
                </div>

                <!-- Commentaires -->
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="comment-header">
                            <div class="comment-author">
                                <div class="author-info">
                                    <h4><?php echo htmlspecialchars($comment['author_name'] ?: 'Utilisateur supprimé'); ?></h4>
                                    <?php if ($comment['author_role'] === 'admin'): ?>
                                        <span class="author-title">Administrateur</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="comment-meta">
                                <span><?php echo date('d/m/Y à H:i', strtotime($comment['created_at'])); ?></span>
                                <?php if ($is_admin): ?>
                                    <button class="comment-action" onclick="moderateComment(<?php echo $comment['id']; ?>)">🗑️ Supprimer</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="comment-content">
                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Formulaire d'ajout de commentaire -->
            <?php if ($is_logged_in && !$current_discussion['is_locked']): ?>
                <div class="forum-form">
                    <h3>Ajouter un commentaire</h3>
                    <form method="POST">
                        <div class="form-group">
                            <textarea class="form-control" name="content" required placeholder="Votre commentaire..."></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="add_comment" class="btn-forum">Publier le commentaire</button>
                        </div>
                    </form>
                </div>
            <?php elseif (!$is_logged_in): ?>
                <div class="forum-form text-center">
                    <p>Vous devez être connecté pour participer à cette discussion.</p>
                    <a href="connexion.php" class="btn-forum">Se connecter</a>
                </div>
            <?php elseif ($current_discussion['is_locked']): ?>
                <div class="forum-form text-center">
                    <p class="text-warning">🔒 Cette discussion est verrouillée. Aucun nouveau commentaire ne peut être ajouté.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
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
        function toggleCreateForm() {
            const form = document.getElementById('create-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function moderateComment(commentId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')) {
                // Rediriger vers le script de modération
                window.location.href = 'scripts-php/moderate-comment.php?id=' + commentId + '&action=delete';
            }
        }

        // Créer une nouvelle discussion
        function createDiscussion() {
            const form = document.getElementById('newDiscussionForm');
            const formData = new FormData(form);
            
            fetch('scripts-php/create-discussion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    if (data.redirect_url) {
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 1500);
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur lors de la création de la discussion.', 'error');
            });
        }
        
        // Ajouter un commentaire
        function addComment() {
            const form = document.getElementById('newCommentForm');
            const formData = new FormData(form);
            
            fetch('scripts-php/add-comment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    // Recharger la page pour afficher le nouveau commentaire
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur lors de l\'ajout du commentaire.', 'error');
            });
        }

        // Mise à jour du compteur de vues
        <?php if ($discussion_id): ?>
            fetch('scripts-php/update-view-count.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    discussion_id: <?php echo $discussion_id; ?>
                })
            });
        <?php endif; ?>
    </script>

    <script src="./scripts-js/profile-image-persistence.js" defer></script>
    <script src="./scripts-js/background.js" defer></script>
    <script src="./scripts-js/notification-badge.js" defer></script>
    
    <!-- Inclusion du système de popup personnalisé -->
    <?php include './scripts-php/popup.php'; ?>
    <script src="./scripts-js/custom-popup.js"></script>
    
    <script>
        // Fonction pour fermer l'avertissement de développement
        function closeDevelopmentWarning() {
            const warning = document.getElementById('dev-warning');
            if (warning) {
                warning.style.opacity = '0';
                warning.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    warning.style.display = 'none';
                    // Sauvegarder dans localStorage que l'utilisateur a fermé l'avertissement
                    localStorage.setItem('forum-dev-warning-closed', 'true');
                }, 300);
            }
        }
        
        // Vérifier si l'avertissement a déjà été fermé
        document.addEventListener('DOMContentLoaded', function() {
            const warningClosed = localStorage.getItem('forum-dev-warning-closed');
            if (warningClosed === 'true') {
                const warning = document.getElementById('dev-warning');
                if (warning) {
                    warning.style.display = 'none';
                }
            }
        });
    </script>
    
    <?php include './scripts-php/scroll-to-top.php'; ?>
</body>
</html>