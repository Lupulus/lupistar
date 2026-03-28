/**
 * Gestion de la persistance de l'image de profil entre les pages
 * Évite le "flash" lors des changements de page en utilisant localStorage
 * Ne recharge l'image que si elle a été réellement modifiée
 */

class ProfileImagePersistence {
    constructor() {
        this.storageKey = 'profile_image_timestamp';
        this.sessionKey = 'profile_image_changed_this_session';
        this.init();
    }

    init() {
        // Appliquer le timestamp persisté seulement si l'image a été changée dans cette session
        this.applyPersistedTimestampIfChanged();
        
        // Écouter les événements de mise à jour de l'image de profil
        document.addEventListener('profileImageUpdated', (event) => {
            this.saveTimestamp(event.detail.timestamp);
            this.markImageAsChanged();
        });
    }

    /**
     * Marquer que l'image a été changée dans cette session
     */
    markImageAsChanged() {
        try {
            sessionStorage.setItem(this.sessionKey, 'true');
        } catch (error) {
            console.warn('Impossible de marquer l\'image comme changée:', error);
        }
    }

    /**
     * Vérifier si l'image a été changée dans cette session
     */
    wasImageChangedThisSession() {
        try {
            return sessionStorage.getItem(this.sessionKey) === 'true';
        } catch (error) {
            console.warn('Impossible de vérifier si l\'image a été changée:', error);
            return false;
        }
    }

    /**
     * Sauvegarder le timestamp dans localStorage
     */
    saveTimestamp(timestamp) {
        try {
            localStorage.setItem(this.storageKey, timestamp.toString());
        } catch (error) {
            console.warn('Impossible de sauvegarder le timestamp de l\'image de profil:', error);
        }
    }

    /**
     * Récupérer le timestamp depuis localStorage
     */
    getPersistedTimestamp() {
        try {
            return localStorage.getItem(this.storageKey);
        } catch (error) {
            console.warn('Impossible de récupérer le timestamp de l\'image de profil:', error);
            return null;
        }
    }

    /**
     * Appliquer le timestamp persisté seulement si l'image a été changée
     */
    applyPersistedTimestampIfChanged() {
        // Ne pas appliquer le timestamp si l'image n'a pas été changée dans cette session
        if (!this.wasImageChangedThisSession()) {
            return;
        }

        const timestamp = this.getPersistedTimestamp();
        
        if (!timestamp) {
            return; // Pas de timestamp persisté
        }

        // Sélecteurs pour toutes les images de profil
        const profileSelectors = [
            '.profile-photo',
            '#profilImg', 
            'img[src*="img-profile"]',
            'img[src*="profil"]',
            '.profil img',
            '.profil-default',
            '.profil-custom'
        ];

        profileSelectors.forEach(selector => {
            const images = document.querySelectorAll(selector);
            images.forEach(img => {
                // Vérifier si l'image n'a pas déjà un timestamp
                if (!img.src.includes('?v=')) {
                    const baseSrc = img.src.split('?')[0];
                    img.src = baseSrc + '?v=' + timestamp + '&cache=' + Math.random();
                }
            });
        });

        // Mettre à jour également les attributs data-src si présents
        document.querySelectorAll('[data-src*="img-profile"], [data-src*="profil"]').forEach(element => {
            if (!element.dataset.src.includes('?v=')) {
                const baseSrc = element.dataset.src.split('?')[0];
                element.dataset.src = baseSrc + '?v=' + timestamp + '&cache=' + Math.random();
            }
        });
    }

    /**
     * Nettoyer le timestamp persisté et la marque de changement (utile lors de la déconnexion)
     */
    clearPersistedTimestamp() {
        try {
            localStorage.removeItem(this.storageKey);
            sessionStorage.removeItem(this.sessionKey);
        } catch (error) {
            console.warn('Impossible de supprimer le timestamp de l\'image de profil:', error);
        }
    }

    /**
     * Réinitialiser la marque de changement pour la session suivante
     */
    resetSessionChange() {
        try {
            sessionStorage.removeItem(this.sessionKey);
        } catch (error) {
            console.warn('Impossible de réinitialiser la marque de changement:', error);
        }
    }
}

// Initialiser dès que le DOM est prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new ProfileImagePersistence();
    });
} else {
    new ProfileImagePersistence();
}

// Exporter pour utilisation dans d'autres scripts si nécessaire
window.ProfileImagePersistence = ProfileImagePersistence;