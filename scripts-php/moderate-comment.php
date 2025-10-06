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
    
    if (!$user || $user['titre'] !== 'admin') {
        header('Location: ../forum.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la vérification du rôle admin: " . $e->getMessage());
    header('Location: ../forum.php');
    exit;
}

$comment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (!$comment_id || !$action) {
    header('Location: ../forum.php');
    exit;
}

// Récupérer les informations du commentaire
try {
    $stmt = $pdo->prepare("
        SELECT c.*, d.category_id, d.titre as discussion_title, m.username as author_name
        FROM forum_comments c 
        JOIN forum_discussions d ON c.discussion_id = d.id 
        LEFT JOIN membres m ON c.author_id = m.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();
    
    if (!$comment) {
        header('Location: ../forum.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération du commentaire: " . $e->getMessage());
    header('Location: ../forum.php');
    exit;
}

// Raisons de suppression prédéfinies
$deletion_reasons = [
    'vulgar' => 'Contenu vulgaire ou inapproprié',
    'spam' => 'Spam ou contenu publicitaire',
    'harassment' => 'Harcèlement ou intimidation',
    'off_topic' => 'Hors sujet',
    'duplicate' => 'Contenu dupliqué',
    'misinformation' => 'Désinformation',
    'copyright' => 'Violation de droits d\'auteur',
    'other' => 'Autre raison'
];

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
    $custom_reason = isset($_POST['custom_reason']) ? trim($_POST['custom_reason']) : '';
    $send_warning = isset($_POST['send_warning']) ? true : false;
    
    if (!array_key_exists($reason, $deletion_reasons)) {
        $error_message = "Raison de suppression invalide.";
    } else {
        $final_reason = $reason === 'other' && !empty($custom_reason) ? $custom_reason : $deletion_reasons[$reason];
        
        try {
            $pdo->beginTransaction();
            
            // Enregistrer l'action de modération
            $stmt = $pdo->prepare("
                INSERT INTO forum_moderations (comment_id, moderator_id, action_type, reason, created_at) 
                VALUES (?, ?, 'delete', ?, NOW())
            ");
            $stmt->execute([$comment_id, $user_id, $final_reason]);
            
            // Supprimer le commentaire
            $stmt = $pdo->prepare("DELETE FROM forum_comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            
            // Envoyer une notification à l'auteur du commentaire
            if ($comment['author_id']) {
                include_once './send-notification.php';
                $notification_message = "Votre commentaire dans la discussion \"" . $comment['discussion_title'] . "\" a été supprimé. Raison: " . $final_reason;
                sendNotification($pdo, $comment['author_id'], $notification_message);
                
                // Ajouter un avertissement si demandé
                if ($send_warning && in_array($reason, ['vulgar', 'harassment', 'spam'])) {
                    include_once './add-avertissement.php';
                    $warning_reason = "Commentaire supprimé pour: " . $final_reason;
                    addWarning($pdo, $comment['author_id'], $warning_reason);
                }
            }
            
            $pdo->commit();
            
            // Rediriger vers la discussion
            header("Location: ../forum.php?category=" . $comment['category_id'] . "&discussion=" . $comment['discussion_id'] . "&success=comment_deleted");
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur lors de la suppression du commentaire: " . $e->getMessage());
            $error_message = "Erreur lors de la suppression du commentaire.";
        }
    }
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
    <title>Modération - Forum Lupistar</title>
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
                <a class="btn active" href="../forum.php">Discussion</a>
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
            <h1>🛡️ Modération de commentaire</h1>
            <p>Gestion des contenus inappropriés</p>
        </div>

        <div class="forum-navigation">
            <div class="forum-breadcrumb">
                <a href="../forum.php">🏠 Forum</a>
                <span class="separator">›</span>
                <a href="../forum.php?category=<?php echo $comment['category_id']; ?>&discussion=<?php echo $comment['discussion_id']; ?>">
                    <?php echo htmlspecialchars($comment['discussion_title']); ?>
                </a>
                <span class="separator">›</span>
                <span>Modération</span>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Affichage du commentaire à modérer -->
        <div class="comments-section">
            <div class="comment-item">
                <div class="comment-header">
                    <div class="comment-author">
                        <div class="author-info">
                            <h4><?php echo htmlspecialchars($comment['author_name'] ?: 'Utilisateur supprimé'); ?></h4>
                        </div>
                    </div>
                    <div class="comment-meta">
                        <span><?php echo date('d/m/Y à H:i', strtotime($comment['created_at'])); ?></span>
                    </div>
                </div>
                <div class="comment-content">
                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                </div>
            </div>
        </div>

        <?php if ($action === 'delete'): ?>
            <!-- Formulaire de suppression -->
            <div class="forum-form">
                <h3>🗑️ Supprimer ce commentaire</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="reason">Raison de la suppression *</label>
                        <select class="form-control" id="reason" name="reason" required onchange="toggleCustomReason()">
                            <option value="">Sélectionnez une raison</option>
                            <?php foreach ($deletion_reasons as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" id="custom-reason-group" style="display: none;">
                        <label for="custom_reason">Raison personnalisée</label>
                        <textarea class="form-control" id="custom_reason" name="custom_reason" placeholder="Précisez la raison de la suppression..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="send_warning" value="1" checked>
                            Envoyer un avertissement automatique à l'utilisateur (recommandé pour les contenus vulgaires, harcèlement ou spam)
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn-moderation" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')">
                            🗑️ Supprimer le commentaire
                        </button>
                        <a href="../forum.php?category=<?php echo $comment['category_id']; ?>&discussion=<?php echo $comment['discussion_id']; ?>" class="btn-forum secondary">
                            Annuler
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2025 lupistar.fr — Tous droits réservés.</p>
        <p>Les illustrations sont la propriété de leurs auteurs et éditeurs respectifs.</p>
        <nav>
            <a href="../mentions-legales.php">Mentions légales</a> | 
            <a href="../confidentialite.php">Politique de confidentialité</a>
        </nav>
    </footer>

    <script>
        function toggleCustomReason() {
            const reasonSelect = document.getElementById('reason');
            const customReasonGroup = document.getElementById('custom-reason-group');
            
            if (reasonSelect.value === 'other') {
                customReasonGroup.style.display = 'block';
                document.getElementById('custom_reason').required = true;
            } else {
                customReasonGroup.style.display = 'none';
                document.getElementById('custom_reason').required = false;
            }
        }
    </script>

    <script src="../scripts-js/profile-image-persistence.js" defer></script>
    <script src="../scripts-js/background.js" defer></script>
    <script src="../scripts-js/notification-badge.js" defer></script>
    <?php include './scroll-to-top.php'; ?>
</body>
</html>