<?php
session_start();
include 'co-bdd.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier si c'est une requête POST avec l'image recadrée
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['upload_cropped_photo'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
    exit;
}

// Vérifier si le fichier a été uploadé
if (!isset($_FILES['cropped_image']) || $_FILES['cropped_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload du fichier']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Récupérer les informations de l'utilisateur
    $sql = "SELECT username, photo_profil FROM membres WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit;
    }
    
    $username = $user['username'];
    $target_dir = "../img/img-profile/";
    
    // Créer le dossier s'il n'existe pas
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Obtenir les informations du fichier uploadé
    $uploaded_file = $_FILES['cropped_image'];
    $file_tmp = $uploaded_file['tmp_name'];
    
    // Déterminer l'extension du fichier basée sur le type MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_tmp);
    finfo_close($finfo);
    
    $extension = '';
    switch ($mime_type) {
        case 'image/jpeg':
            $extension = 'jpg';
            break;
        case 'image/png':
            $extension = 'png';
            break;
        case 'image/gif':
            $extension = 'gif';
            break;
        case 'image/webp':
            $extension = 'webp';
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Type de fichier non supporté']);
            exit;
    }
    
    // Créer le nom de fichier selon le format demandé : <username>-profile.<extension>
    $new_filename = $username . "-profile." . $extension;
    $target_file = $target_dir . $new_filename;
    $relative_path = "img/img-profile/" . $new_filename;
    
    // Supprimer l'ancienne photo si elle existe et n'est pas la photo par défaut
    if ($user['photo_profil'] && 
        $user['photo_profil'] !== 'img/img-profile/profil.png' && 
        $user['photo_profil'] !== 'img/profil.png' &&
        file_exists("../" . $user['photo_profil'])) {
        unlink("../" . $user['photo_profil']);
    }
    
    // Déplacer le fichier uploadé vers sa destination finale
    if (move_uploaded_file($file_tmp, $target_file)) {
        // Mettre à jour la base de données
        $update_sql = "UPDATE membres SET photo_profil = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        
        if ($update_stmt->execute([$relative_path, $user_id])) {
            // Mettre à jour la session avec la nouvelle photo de profil
            $_SESSION['photo_profil'] = $relative_path;
            
            // Optimiser l'image seulement pour les JPEG (pas pour PNG avec transparence)
            if ($extension === 'jpg' || $extension === 'jpeg') {
                optimizeImage($target_file, $mime_type);
            }
            // Les PNG ne sont pas optimisés pour préserver la transparence
            
            echo json_encode([
                'success' => true, 
                'message' => 'Photo de profil mise à jour avec succès',
                'image_path' => $relative_path,
                'filename' => $new_filename
            ]);
        } else {
            // Supprimer le fichier si la mise à jour de la BDD a échoué
            if (file_exists($target_file)) {
                unlink($target_file);
            }
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la base de données']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde du fichier']);
    }
    
} catch (Exception $e) {
    error_log("Erreur upload image recadrée: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
} catch (PDOException $e) {
    error_log("Erreur PDO upload image recadrée: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}

/**
 * Optimise une image JPEG en réduisant sa qualité si nécessaire
 * Les PNG ne sont pas optimisés pour préserver la transparence
 */
function optimizeImage($file_path, $mime_type) {
    if ($mime_type !== 'image/jpeg') {
        return;
    }
    
    // Vérifier la taille du fichier
    $file_size = filesize($file_path);
    $max_size = 500 * 1024; // 500KB
    
    if ($file_size > $max_size) {
        $image = imagecreatefromjpeg($file_path);
        if ($image) {
            // Calculer la qualité basée sur la taille
            $quality = max(60, min(90, 90 - (($file_size - $max_size) / ($max_size * 0.5)) * 30));
            
            // Sauvegarder avec la nouvelle qualité
            imagejpeg($image, $file_path, $quality);
            imagedestroy($image);
        }
    }
}

/**
 * Valide et nettoie le nom d'utilisateur pour l'utiliser dans un nom de fichier
 */
function sanitizeUsername($username) {
    // Remplacer les caractères non autorisés par des tirets
    $clean = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $username);
    // Supprimer les tirets multiples
    $clean = preg_replace('/-+/', '-', $clean);
    // Supprimer les tirets en début et fin
    $clean = trim($clean, '-');
    // Limiter la longueur
    $clean = substr($clean, 0, 50);
    
    return $clean ?: 'user';
}
?>