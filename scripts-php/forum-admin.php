<?php
session_start();
require_once './co-bdd.php';

// Vérifier si l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user_id'])) {
    header('Location: ../connexion.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Vérifier le rôle administrateur
try {
    $stmt = $pdo->prepare("SELECT titre FROM membres WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || ($user['titre'] !== 'Admin' && $user['titre'] !== 'Super-Admin')) {
        header('Location: ../forum.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la vérification du rôle admin: " . $e->getMessage());
    header('Location: ../forum.php');
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';
$success_message = '';
$error_message = '';

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'create_category':
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $admin_only = isset($_POST['admin_only']) ? 1 : 0;
            $display_order = (int)$_POST['display_order'];
            
            if (!empty($name)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO forum_categories (nom, description, admin_only, ordre, active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
                    $stmt->execute([$name, $description, $admin_only, $display_order]);
                    $success_message = "Catégorie créée avec succès.";
                } catch (PDOException $e) {
                    error_log("Erreur lors de la création de catégorie: " . $e->getMessage());
                    $error_message = "Erreur lors de la création de la catégorie.";
                }
            } else {
                $error_message = "Le nom de la catégorie est requis.";
            }
            break;
            
        case 'toggle_discussion':
            $discussion_id = (int)$_POST['discussion_id'];
            $field = $_POST['field']; // 'is_pinned' ou 'is_locked'
            
            if (in_array($field, ['pinned', 'locked']) && $discussion_id > 0) {
                try {
                    $stmt = $pdo->prepare("UPDATE forum_discussions SET $field = NOT $field WHERE id = ?");
                    $stmt->execute([$discussion_id]);
                    $success_message = "Discussion mise à jour avec succès.";
                } catch (PDOException $e) {
                    error_log("Erreur lors de la mise à jour de discussion: " . $e->getMessage());
                    $error_message = "Erreur lors de la mise à jour.";
                }
            }
            break;
            
        case 'delete_discussion':
            $discussion_id = (int)$_POST['discussion_id'];
            
            if ($discussion_id > 0) {
                try {
                    $pdo->beginTransaction();
                    
                    // Supprimer les commentaires
                    $stmt = $pdo->prepare("DELETE FROM forum_comments WHERE discussion_id = ?");
                    $stmt->execute([$discussion_id]);
                    
                    // Supprimer la discussion
                    $stmt = $pdo->prepare("DELETE FROM forum_discussions WHERE id = ?");
                    $stmt->execute([$discussion_id]);
                    
                    $pdo->commit();
                    $success_message = "Discussion supprimée avec succès.";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log("Erreur lors de la suppression de discussion: " . $e->getMessage());
                    $error_message = "Erreur lors de la suppression.";
                }
            }
            break;
            
        case 'move_discussion':
            $discussion_id = (int)$_POST['discussion_id'];
            $new_category_id = (int)$_POST['new_category_id'];
            
            if ($discussion_id > 0 && $new_category_id > 0) {
                try {
                    $stmt = $pdo->prepare("UPDATE forum_discussions SET category_id = ? WHERE id = ?");
                    $stmt->execute([$new_category_id, $discussion_id]);
                    $success_message = "Discussion déplacée avec succès.";
                } catch (PDOException $e) {
                    error_log("Erreur lors du déplacement de discussion: " . $e->getMessage());
                    $error_message = "Erreur lors du déplacement.";
                }
            }
            break;
    }
}

// Récupérer les statistiques du forum
try {
    $stats = [];
    
    // Nombre total de catégories
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM forum_categories WHERE active = 1");
    $stats['categories'] = $stmt->fetch()['count'];
    
    // Nombre total de discussions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM forum_discussions");
    $stats['discussions'] = $stmt->fetch()['count'];
    
    // Nombre total de commentaires
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM forum_comments");
    $stats['comments'] = $stmt->fetch()['count'];
    
    // Nombre d'actions de modération aujourd'hui
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM forum_moderations WHERE DATE(created_at) = CURDATE()");
    $stats['moderations_today'] = $stmt->fetch()['count'];
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
    $stats = ['categories' => 0, 'discussions' => 0, 'comments' => 0, 'moderations_today' => 0];
}

// Récupérer les catégories
try {
    $stmt = $pdo->query("SELECT * FROM forum_categories ORDER BY ordre ASC, nom ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des catégories: " . $e->getMessage());
    $categories = [];
}

// Récupérer les discussions récentes
try {
    $stmt = $pdo->query("
        SELECT d.*, c.nom as category_name, m.username as author_name,
               (SELECT COUNT(*) FROM forum_comments WHERE discussion_id = d.id) as comment_count
        FROM forum_discussions d 
        LEFT JOIN forum_categories c ON d.category_id = c.id
        LEFT JOIN membres m ON d.author_id = m.id 
        ORDER BY d.created_at DESC 
        LIMIT 10
    ");
    $recent_discussions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des discussions récentes: " . $e->getMessage());
    $recent_discussions = [];
}

// Récupérer les actions de modération récentes
try {
    $stmt = $pdo->query("
        SELECT fm.*, m.pseudo as moderator_name, fc.content as comment_content
        FROM forum_moderations fm
        LEFT JOIN membres m ON fm.moderator_id = m.id
        LEFT JOIN forum_comments fc ON fm.comment_id = fc.id
        ORDER BY fm.created_at DESC 
        LIMIT 10
    ");
    $recent_moderations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des modérations récentes: " . $e->getMessage());
    $recent_moderations = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style-navigation.css">
    <link rel="stylesheet" href="../css/style-forum.css">
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <title>Administration Forum - Lupistar</title>
    <style>
        .admin-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .admin-card {
            background: var(--secondary-dark);
            border-radius: var(--forum-border-radius);
            padding: 1.5rem;
            border: 1px solid var(--tertiary-dark);
            box-shadow: 0 4px 15px var(--shadow-dark);
        }
        
        .admin-card h3 {
            color: var(--accent-orange);
            margin: 0 0 1rem 0;
            font-size: 1.2rem;
            border-bottom: 2px solid var(--accent-orange);
            padding-bottom: 0.5rem;
        }
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: var(--tertiary-dark);
            border-radius: 8px;
        }
        
        .stat-number {
            display: block;
            font-size: 2rem;
            font-weight: 600;
            color: var(--accent-orange);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-light-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .admin-table th,
        .admin-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--tertiary-dark);
        }
        
        .admin-table th {
            background: var(--tertiary-dark);
            color: var(--accent-orange);
            font-weight: 600;
        }
        
        .admin-table td {
            color: var(--text-light-gray);
        }
        
        .admin-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: var(--forum-transition);
        }
        
        .btn-pin {
            background: #f39c12;
            color: white;
        }
        
        .btn-lock {
            background: #e74c3c;
            color: white;
        }
        
        .btn-move {
            background: #3498db;
            color: white;
        }
        
        .btn-delete {
            background: #c0392b;
            color: white;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            border: 1px solid #2ecc71;
            color: #2ecc71;
        }
        
        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="background"></div>
    
    <header>
        <nav class="navbar">
            <img src="../gif/logogif.GIF" alt="" class="gif">
            <ul class="menu">
                <a class="btn" href="../index.php">Accueil</a>
                <a class="btn" href="../liste.php">Liste</a>
                <a class="btn" href="../ma-liste.php">Ma Liste</a>
                <a class="btn" href="../forum.php">Discussion</a>
            </ul>
            <div class="profil" id="profil">
                <?php 
                $img_id = 'profilImg';
                include './img-profil.php'; 
                ?>
                <div class="menu-deroulant" id="deroulant">
                    <?php include './menu-profil.php'; ?>
                </div>
            </div>
        </nav>
    </header>

    <div class="forum-container">
        <div class="forum-header">
            <h1>🛡️ Administration du Forum</h1>
            <p>Gestion et modération du système de discussion</p>
        </div>

        <div class="forum-navigation">
            <div class="forum-breadcrumb">
                <a href="../forum.php">🏠 Forum</a>
                <span class="separator">›</span>
                <span>Administration</span>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                ❌ <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Tableau de bord -->
        <div class="admin-dashboard">
            <!-- Statistiques -->
            <div class="admin-card">
                <h3>📊 Statistiques</h3>
                <div class="stat-grid">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['categories']; ?></span>
                        <span class="stat-label">Catégories</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['discussions']; ?></span>
                        <span class="stat-label">Discussions</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['comments']; ?></span>
                        <span class="stat-label">Commentaires</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['moderations_today']; ?></span>
                        <span class="stat-label">Modérations aujourd'hui</span>
                    </div>
                </div>
            </div>

            <!-- Création de catégorie -->
            <div class="admin-card">
                <h3>➕ Créer une catégorie</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create_category">
                    <div class="form-group">
                        <label for="name">Nom de la catégorie</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="display_order">Ordre d'affichage</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" value="0">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="admin_only" value="1">
                            Réservée aux administrateurs
                        </label>
                    </div>
                    <button type="submit" class="btn-forum">Créer la catégorie</button>
                </form>
            </div>
        </div>

        <!-- Gestion des catégories -->
        <div class="admin-card">
            <h3>📁 Gestion des catégories</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Ordre</th>
                        <th>Admin uniquement</th>
                        <th>Discussions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['nom']); ?></td>
                            <td><?php echo htmlspecialchars($category['description']); ?></td>
                            <td><?php echo $category['ordre']; ?></td>
                            <td><?php echo $category['admin_only'] ? '🔒 Oui' : '🌍 Non'; ?></td>
                            <td>
                                <?php
                                try {
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM forum_discussions WHERE category_id = ?");
                                    $stmt->execute([$category['id']]);
                                    echo $stmt->fetch()['count'];
                                } catch (PDOException $e) {
                                    echo '0';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="../forum.php?category=<?php echo $category['id']; ?>" class="btn-small btn-move">Voir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Discussions récentes -->
        <div class="admin-card">
            <h3>💬 Discussions récentes</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Catégorie</th>
                        <th>Auteur</th>
                        <th>Commentaires</th>
                        <th>Créée le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_discussions as $discussion): ?>
                        <tr>
                            <td>
                                <a href="../forum.php?category=<?php echo $discussion['category_id']; ?>&discussion=<?php echo $discussion['id']; ?>" style="color: var(--text-light-gray);">
                                    <?php if ($discussion['is_pinned']): ?>📌<?php endif; ?>
                                    <?php if ($discussion['is_locked']): ?>🔒<?php endif; ?>
                                    <?php echo htmlspecialchars($discussion['title']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($discussion['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($discussion['author_name'] ?: 'Supprimé'); ?></td>
                            <td><?php echo $discussion['comment_count']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($discussion['created_at'])); ?></td>
                            <td>
                                <div class="admin-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_discussion">
                                        <input type="hidden" name="discussion_id" value="<?php echo $discussion['id']; ?>">
                                        <input type="hidden" name="field" value="is_pinned">
                                        <button type="submit" class="btn-small btn-pin">
                                            <?php echo $discussion['is_pinned'] ? 'Désépingler' : 'Épingler'; ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_discussion">
                                        <input type="hidden" name="discussion_id" value="<?php echo $discussion['id']; ?>">
                                        <input type="hidden" name="field" value="is_locked">
                                        <button type="submit" class="btn-small btn-lock">
                                            <?php echo $discussion['is_locked'] ? 'Déverrouiller' : 'Verrouiller'; ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette discussion ?')">
                                        <input type="hidden" name="action" value="delete_discussion">
                                        <input type="hidden" name="discussion_id" value="<?php echo $discussion['id']; ?>">
                                        <button type="submit" class="btn-small btn-delete">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Actions de modération récentes -->
        <div class="admin-card">
            <h3>🛡️ Modérations récentes</h3>
            <?php if (empty($recent_moderations)): ?>
                <p class="text-muted">Aucune action de modération récente.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Modérateur</th>
                            <th>Action</th>
                            <th>Raison</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_moderations as $moderation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($moderation['moderator_name'] ?: 'Système'); ?></td>
                                <td><?php echo ucfirst($moderation['action_type']); ?></td>
                                <td><?php echo htmlspecialchars($moderation['reason']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($moderation['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 lupistar.fr — Tous droits réservés.</p>
        <p>Les illustrations sont la propriété de leurs auteurs et éditeurs respectifs.</p>
        <nav>
            <a href="../mentions-legales.php">Mentions légales</a> | 
            <a href="../confidentialite.php">Politique de confidentialité</a>
        </nav>
    </footer>

    <script src="../scripts-js/profile-image-persistence.js" defer></script>
    <script src="../scripts-js/background.js" defer></script>
    <script src="../scripts-js/notification-badge.js" defer></script>
    <?php include './scroll-to-top.php'; ?>
</body>
</html>