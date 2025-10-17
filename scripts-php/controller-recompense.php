<?php
/**
 * Contrôleur de récompenses automatiques
 * Gère l'attribution des récompenses basées sur l'activité des utilisateurs
 */

include_once 'co-bdd.php';

class RecompenseController {
    private $pdo;
    
    // Paliers pour les films dans la liste personnelle (avec note > 0)
    private $paliers_liste_perso = [
        10 => 1,    // 10 films = 1 récompense
        100 => 1,   // 100 films = 1 récompense
        250 => 1,   // 250 films = 1 récompense
        500 => 1,   // 500 films = 1 récompense
        // Puis +1 récompense tous les 100 films après 500
        // Notification spéciale tous les 1000 films
    ];
    
    // Paliers pour les films approuvés
    private $paliers_films_approuves = [
        10 => 1,    // 10 films = 1 récompense
        20 => 1,    // 20 films = 1 récompense
        35 => 1,    // 35 films = 1 récompense
        50 => 1,    // 50 films = 1 récompense
        75 => 1,    // 75 films = 1 récompense
        100 => 1,   // 100 films = 1 récompense
        150 => 1,   // 150 films = 1 récompense
        200 => 1,   // 200 films = 1 récompense
        300 => 1,   // 300 films = 1 récompense
        400 => 1,   // 400 films = 1 récompense
        500 => 3,   // 500 films = 3 récompenses + notification spéciale
        1000 => 5,  // 1000 films = 5 récompenses + notification spéciale
    ];
    
    // Hiérarchie des titres et coût en récompenses
    private $titres_hierarchie = [
        'Membre' => ['niveau' => 1, 'cout_promotion' => 3],
        'Amateur' => ['niveau' => 2, 'cout_promotion' => 6],  // 3 + 3
        'Fan' => ['niveau' => 3, 'cout_promotion' => 9],      // 6 + 3
        'NoLife' => ['niveau' => 4, 'cout_promotion' => 12],  // 9 + 3
        'Admin' => ['niveau' => 5, 'cout_promotion' => 0],    // Pas de promotion automatique
        'Super-Admin' => ['niveau' => 6, 'cout_promotion' => 0]
    ];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Vérifie et attribue les récompenses pour un utilisateur
     */
    public function verifierRecompenses($user_id) {
        try {
            // Récupérer les informations de l'utilisateur
            $user_info = $this->getUserInfo($user_id);
            if (!$user_info) {
                return false;
            }
            
            // Vérifier les récompenses pour la liste personnelle
            $this->verifierRecompensesListePerso($user_id, $user_info);
            
            // Vérifier les récompenses pour les films approuvés
            $this->verifierRecompensesFilmsApprouves($user_id, $user_info);
            
            // Mettre à jour la date de dernière vérification
            $this->updateLastVerification($user_id);
            
            return true;
        } catch (Exception $e) {
            error_log("Erreur lors de la vérification des récompenses: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifie les récompenses pour la liste personnelle
     */
    private function verifierRecompensesListePerso($user_id, $user_info) {
        // Compter les films dans la liste personnelle avec note > 0
        $count_query = "SELECT COUNT(*) as total FROM ma_liste WHERE user_id = ? AND note > 0";
        $stmt = $this->pdo->prepare($count_query);
        $stmt->execute([$user_id]);
        $current_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $max_atteint = $user_info['max_films_liste_atteint'];
        
        // Vérifier les paliers standards
        foreach ($this->paliers_liste_perso as $palier => $recompenses) {
            if ($current_count >= $palier && $max_atteint < $palier) {
                $this->attribuerRecompense($user_id, $recompenses, "liste_perso", $palier);
                $max_atteint = $palier;
            }
        }
        
        // Vérifier les paliers après 500 (tous les 100 films)
        if ($current_count >= 500) {
            $paliers_supplementaires = floor(($current_count - 500) / 100);
            $max_paliers_supplementaires = floor(($max_atteint - 500) / 100);
            
            if ($max_atteint < 500) {
                $max_paliers_supplementaires = -1;
            }
            
            for ($i = $max_paliers_supplementaires + 1; $i <= $paliers_supplementaires; $i++) {
                $palier_actuel = 500 + ($i * 100);
                $this->attribuerRecompense($user_id, 1, "liste_perso_bonus", $palier_actuel);
                $max_atteint = $palier_actuel;
            }
        }
        
        // Notification spéciale tous les 1000 films
        $milliers_actuels = floor($current_count / 1000);
        $milliers_max = floor($max_atteint / 1000);
        
        if ($milliers_actuels > $milliers_max && $current_count >= 1000) {
            for ($i = $milliers_max + 1; $i <= $milliers_actuels; $i++) {
                $this->envoyerNotificationSpeciale($user_id, "liste_perso_1000", $i * 1000);
            }
        }
        
        // Mettre à jour le maximum atteint
        if ($max_atteint > $user_info['max_films_liste_atteint']) {
            $this->updateMaxAtteint($user_id, 'max_films_liste_atteint', $max_atteint);
        }
    }
    
    /**
     * Vérifie les récompenses pour les films approuvés
     */
    private function verifierRecompensesFilmsApprouves($user_id, $user_info) {
        // Compter les films approuvés de l'utilisateur
        $count_query = "SELECT COUNT(*) as total FROM films WHERE proposer_par = ?";
        $stmt = $this->pdo->prepare($count_query);
        $stmt->execute([$user_id]);
        $current_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $max_atteint = $user_info['max_films_approuves_atteint'];
        
        // Vérifier chaque palier
        foreach ($this->paliers_films_approuves as $palier => $recompenses) {
            if ($current_count >= $palier && $max_atteint < $palier) {
                $this->attribuerRecompense($user_id, $recompenses, "films_approuves", $palier);
                
                // Notifications spéciales pour 500 et 1000 films
                if ($palier == 500 || $palier == 1000) {
                    $this->envoyerNotificationSpeciale($user_id, "films_approuves_special", $palier);
                }
                
                $max_atteint = $palier;
            }
        }
        
        // Mettre à jour le maximum atteint
        if ($max_atteint > $user_info['max_films_approuves_atteint']) {
            $this->updateMaxAtteint($user_id, 'max_films_approuves_atteint', $max_atteint);
        }
    }
    
    /**
     * Attribue des récompenses à un utilisateur
     */
    private function attribuerRecompense($user_id, $nombre_recompenses, $type, $palier) {
        try {
            // Ajouter les récompenses
            $update_query = "UPDATE membres SET recompenses = recompenses + ? WHERE id = ?";
            $stmt = $this->pdo->prepare($update_query);
            $stmt->execute([$nombre_recompenses, $user_id]);
            
            // Envoyer une notification
            $this->envoyerNotificationRecompense($user_id, $nombre_recompenses, $type, $palier);
            
            error_log("Récompense attribuée: User $user_id, $nombre_recompenses récompense(s), type: $type, palier: $palier");
        } catch (Exception $e) {
            error_log("Erreur lors de l'attribution de récompense: " . $e->getMessage());
        }
    }
    
    /**
     * Envoie une notification de récompense
     */
    private function envoyerNotificationRecompense($user_id, $nombre_recompenses, $type, $palier) {
        $messages = [
            'liste_perso' => "🎉 Félicitations ! Vous avez atteint $palier films notés dans votre liste personnelle ! Vous recevez $nombre_recompenses récompense" . ($nombre_recompenses > 1 ? 's' : '') . " !",
            'liste_perso_bonus' => "🌟 Incroyable ! $palier films notés dans votre liste ! Vous recevez 1 récompense bonus !",
            'films_approuves' => "🏆 Bravo ! $palier de vos films proposés ont été approuvés ! Vous recevez $nombre_recompenses récompense" . ($nombre_recompenses > 1 ? 's' : '') . " !"
        ];
        
        $titre = "Nouvelle récompense !";
        $message = $messages[$type] ?? "Vous avez reçu $nombre_recompenses récompense(s) !";
        
        $this->envoyerNotification($user_id, $titre, $message, 'reward');
    }
    
    /**
     * Envoie une notification spéciale
     */
    private function envoyerNotificationSpeciale($user_id, $type, $palier) {
        $messages = [
            'liste_perso_1000' => "🎊 EXPLOIT EXTRAORDINAIRE ! 🎊\n\nVous avez atteint $palier films notés dans votre liste personnelle ! Vous êtes un véritable passionné de cinéma !",
            'films_approuves_special' => $palier == 500 ? 
                "🏅 ACCOMPLISSEMENT LÉGENDAIRE ! 🏅\n\n500 films approuvés ! Vous êtes devenu une référence de la communauté !" :
                "👑 MAÎTRE SUPRÊME DU CINÉMA ! 👑\n\n1000 films approuvés ! Votre contribution à la communauté est exceptionnelle !"
        ];
        
        $titre = $palier >= 1000 ? "Exploit Légendaire !" : "Accomplissement Spécial !";
        $message = $messages[$type] ?? "Félicitations pour cet accomplissement !";
        
        $this->envoyerNotification($user_id, $titre, $message, 'special_achievement');
    }
    
    /**
     * Envoie une notification via le système existant
     */
    private function envoyerNotification($user_id, $titre, $message, $type) {
        try {
            $insert_query = "INSERT INTO notifications (user_id, type, titre, message, date_creation) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $this->pdo->prepare($insert_query);
            $stmt->execute([$user_id, $type, $titre, $message]);
        } catch (Exception $e) {
            error_log("Erreur lors de l'envoi de notification: " . $e->getMessage());
        }
    }
    
    /**
     * Demande de promotion au titre suivant
     */
    public function demanderPromotion($user_id) {
        try {
            $user_info = $this->getUserInfo($user_id);
            if (!$user_info) {
                return ['success' => false, 'message' => 'Utilisateur non trouvé'];
            }
            
            $titre_actuel = $user_info['titre'];
            $recompenses_actuelles = $user_info['recompenses'];
            
            // Vérifier si le titre peut être promu
            if (!isset($this->titres_hierarchie[$titre_actuel])) {
                return ['success' => false, 'message' => 'Titre non reconnu'];
            }
            
            $info_titre = $this->titres_hierarchie[$titre_actuel];
            
            // Vérifier si c'est un titre promotable
            if ($info_titre['cout_promotion'] == 0) {
                return ['success' => false, 'message' => 'Ce titre ne peut pas être promu automatiquement'];
            }
            
            // Vérifier si l'utilisateur a assez de récompenses
            if ($recompenses_actuelles < $info_titre['cout_promotion']) {
                $manquant = $info_titre['cout_promotion'] - $recompenses_actuelles;
                return ['success' => false, 'message' => "Il vous manque $manquant récompense(s) pour demander cette promotion"];
            }
            
            // Vérifier si une demande n'est pas déjà en cours
            if ($user_info['demande_promotion'] == 1) {
                return ['success' => false, 'message' => 'Une demande de promotion est déjà en cours'];
            }
            
            // Marquer la demande de promotion
            $update_query = "UPDATE membres SET demande_promotion = 1 WHERE id = ?";
            $stmt = $this->pdo->prepare($update_query);
            $stmt->execute([$user_id]);
            
            // Envoyer une notification à l'utilisateur
            $titre_suivant = $this->getTitreSuivant($titre_actuel);
            $this->envoyerNotification(
                $user_id, 
                "Demande de promotion", 
                "Votre demande de promotion vers le titre '$titre_suivant' a été soumise. Elle sera examinée par un administrateur.", 
                'promotion_request'
            );
            
            return ['success' => true, 'message' => 'Demande de promotion soumise avec succès'];
        } catch (Exception $e) {
            error_log("Erreur lors de la demande de promotion: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la demande de promotion'];
        }
    }
    
    /**
     * Traite une demande de promotion (pour les administrateurs)
     */
    public function traiterDemandePromotion($user_id, $approuver = true) {
        try {
            $user_info = $this->getUserInfo($user_id);
            if (!$user_info || $user_info['demande_promotion'] != 1) {
                return ['success' => false, 'message' => 'Aucune demande de promotion en cours'];
            }
            
            if ($approuver) {
                $titre_actuel = $user_info['titre'];
                $titre_suivant = $this->getTitreSuivant($titre_actuel);
                $cout = $this->titres_hierarchie[$titre_actuel]['cout_promotion'];
                
                // Déduire les récompenses et changer le titre
                $update_query = "UPDATE membres SET titre = ?, recompenses = recompenses - ?, demande_promotion = 0 WHERE id = ?";
                $stmt = $this->pdo->prepare($update_query);
                $stmt->execute([$titre_suivant, $cout, $user_id]);
                
                // Notification de succès
                $this->envoyerNotification(
                    $user_id, 
                    "Promotion accordée !", 
                    "🎉 Félicitations ! Votre promotion vers le titre '$titre_suivant' a été accordée ! $cout récompense(s) ont été déduites.", 
                    'promotion_approved'
                );
            } else {
                // Refuser la demande
                $update_query = "UPDATE membres SET demande_promotion = 0 WHERE id = ?";
                $stmt = $this->pdo->prepare($update_query);
                $stmt->execute([$user_id]);
                
                // Notification de refus
                $this->envoyerNotification(
                    $user_id, 
                    "Demande de promotion refusée", 
                    "Votre demande de promotion a été refusée par un administrateur.", 
                    'promotion_denied'
                );
            }
            
            return ['success' => true, 'message' => $approuver ? 'Promotion accordée' : 'Demande refusée'];
        } catch (Exception $e) {
            error_log("Erreur lors du traitement de la demande: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du traitement'];
        }
    }
    
    /**
     * Récupère les informations d'un utilisateur
     */
    public function getUserInfo($user_id) {
        $query = "SELECT * FROM membres WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Met à jour le maximum atteint pour éviter les abus
     */
    private function updateMaxAtteint($user_id, $column, $value) {
        $query = "UPDATE membres SET $column = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$value, $user_id]);
    }
    
    /**
     * Met à jour la date de dernière vérification
     */
    private function updateLastVerification($user_id) {
        $query = "UPDATE membres SET date_derniere_verification = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$user_id]);
    }
    
    /**
     * Obtient le titre suivant dans la hiérarchie
     */
    private function getTitreSuivant($titre_actuel) {
        $titres_ordre = ['Membre', 'Amateur', 'Fan', 'NoLife'];
        $index = array_search($titre_actuel, $titres_ordre);
        
        if ($index !== false && $index < count($titres_ordre) - 1) {
            return $titres_ordre[$index + 1];
        }
        
        return $titre_actuel; // Pas de titre suivant
    }
    
    /**
     * Obtient les utilisateurs avec des demandes de promotion en cours
     */
    public function getDemandesPromotion() {
        $query = "SELECT id, username, titre, recompenses FROM membres WHERE demande_promotion = 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Vérifie si un utilisateur peut demander une promotion
     */
    public function peutDemanderPromotion($user_id) {
        $user_info = $this->getUserInfo($user_id);
        if (!$user_info) return false;
        
        $titre_actuel = $user_info['titre'];
        $recompenses = $user_info['recompenses'];
        $demande_en_cours = $user_info['demande_promotion'];
        
        if ($demande_en_cours == 1) return false;
        if (!isset($this->titres_hierarchie[$titre_actuel])) return false;
        
        $cout = $this->titres_hierarchie[$titre_actuel]['cout_promotion'];
        if ($cout == 0) return false;
        
        return $recompenses >= $cout;
    }
    
    /**
     * Approuve une demande de promotion
     */
    public function approuverPromotion($user_id) {
        try {
            $this->pdo->beginTransaction();
            
            // Récupérer les informations de l'utilisateur
            $user_info = $this->getUserInfo($user_id);
            if (!$user_info || $user_info['demande_promotion'] != 1) {
                $this->pdo->rollBack();
                return false;
            }
            
            $titre_actuel = $user_info['titre'];
            $recompenses = $user_info['recompenses'];
            
            // Vérifier si la promotion est possible
            if (!isset($this->titres_hierarchie[$titre_actuel])) {
                $this->pdo->rollBack();
                return false;
            }
            
            $cout = $this->titres_hierarchie[$titre_actuel]['cout_promotion'];
            if ($cout == 0 || $recompenses < $cout) {
                $this->pdo->rollBack();
                return false;
            }
            
            // Obtenir le nouveau titre
            $nouveau_titre = $this->getTitreSuivant($titre_actuel);
            if ($nouveau_titre === $titre_actuel) {
                $this->pdo->rollBack();
                return false;
            }
            
            // Mettre à jour l'utilisateur
            $query = "UPDATE membres SET 
                        titre = ?, 
                        recompenses = recompenses - ?, 
                        demande_promotion = 0 
                      WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$nouveau_titre, $cout, $user_id]);
            
            $this->pdo->commit();
            
            // Envoyer une notification de félicitation
            $titre = "🎉 Promotion acceptée !";
            $message = "Félicitations ! Votre demande de promotion a été acceptée avec succès ! Vous êtes maintenant $nouveau_titre. $cout récompenses ont été déduites de votre compte.";
            $this->envoyerNotification($user_id, $titre, $message, 'promotion');
            
            return [
                'nouveau_titre' => $nouveau_titre,
                'cout' => $cout
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de l'approbation de la promotion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Rejette une demande de promotion
     */
    public function rejeterPromotion($user_id) {
        try {
            // Vérifier que l'utilisateur a bien une demande en cours
            $user_info = $this->getUserInfo($user_id);
            if (!$user_info || $user_info['demande_promotion'] != 1) {
                return false;
            }
            
            // Annuler la demande de promotion
            $query = "UPDATE membres SET demande_promotion = 0 WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$user_id]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur lors du rejet de la promotion: " . $e->getMessage());
            return false;
        }
    }
}

// Fonction utilitaire pour vérifier les récompenses d'un utilisateur
function verifierRecompensesUtilisateur($user_id) {
    global $pdo;
    $controller = new RecompenseController($pdo);
    return $controller->verifierRecompenses($user_id);
}

// Fonction utilitaire pour demander une promotion
function demanderPromotionUtilisateur($user_id) {
    global $pdo;
    $controller = new RecompenseController($pdo);
    return $controller->demanderPromotion($user_id);
}

// Fonction utilitaire pour traiter une demande de promotion
function traiterDemandePromotionUtilisateur($user_id, $approuver = true) {
    global $pdo;
    $controller = new RecompenseController($pdo);
    return $controller->traiterDemandePromotion($user_id, $approuver);
}

// Fonction utilitaire pour obtenir les demandes de promotion
function getDemandesPromotionEnCours() {
    global $pdo;
    $controller = new RecompenseController($pdo);
    return $controller->getDemandesPromotion();
}

// Fonction utilitaire pour vérifier si un utilisateur peut demander une promotion
function utilisateurPeutDemanderPromotion($user_id) {
    global $pdo;
    $controller = new RecompenseController($pdo);
    return $controller->peutDemanderPromotion($user_id);
}
?>