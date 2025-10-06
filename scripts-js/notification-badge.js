// Gestion des badges de notification
class NotificationBadgeManager {
    constructor() {
        this.profileBadge = document.getElementById('profileNotificationBadge');
        this.menuBadge = document.getElementById('menuNotificationBadge');
        this.updateInterval = 30000; // Mise à jour toutes les 30 secondes
        this.intervalId = null;
        
        this.init();
    }
    
    init() {
        // Charger le nombre de notifications au démarrage
        this.updateNotificationCount();
        
        // Démarrer la mise à jour périodique
        this.startPeriodicUpdate();
        
        // Écouter les événements de focus/blur pour optimiser les requêtes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPeriodicUpdate();
            } else {
                this.updateNotificationCount();
                this.startPeriodicUpdate();
            }
        });
    }
    
    async updateNotificationCount() {
        try {
            const response = await fetch('./scripts-php/get-unread-notifications-count.php');
            const data = await response.json();
            
            if (data.success) {
                this.updateBadges(data.count);
            } else {
                console.error('Erreur lors de la récupération du nombre de notifications:', data.error);
            }
        } catch (error) {
            console.error('Erreur réseau lors de la récupération des notifications:', error);
        }
    }
    
    updateBadges(count) {
        const badges = [this.profileBadge, this.menuBadge];
        
        badges.forEach(badge => {
            if (badge) {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count.toString();
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
        });
    }
    
    startPeriodicUpdate() {
        if (!this.intervalId) {
            this.intervalId = setInterval(() => {
                this.updateNotificationCount();
            }, this.updateInterval);
        }
    }
    
    stopPeriodicUpdate() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }
    
    // Méthode pour forcer une mise à jour (utile après certaines actions)
    forceUpdate() {
        this.updateNotificationCount();
    }
}

// Initialiser le gestionnaire de badges quand le DOM est prêt
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si l'utilisateur est connecté (présence des badges)
    if (document.getElementById('profileNotificationBadge') || document.getElementById('menuNotificationBadge')) {
        window.notificationBadgeManager = new NotificationBadgeManager();
    }
});

// Fonction globale pour forcer la mise à jour des badges (utilisable depuis d'autres scripts)
function updateNotificationBadges() {
    if (window.notificationBadgeManager) {
        window.notificationBadgeManager.forceUpdate();
    }
}